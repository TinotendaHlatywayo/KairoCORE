#!/bin/sh
# Kairo CORE container entrypoint.
#   web role  (CMD apache2-foreground): migrate (optional), link storage, cache, serve
#   other roles (queue worker, scheduler): wait for env, cache config, exec the command
set -e
cd /var/www/html

# Run artisan as www-data when we start as root, so files created in the shared
# storage volume stay writable by the Apache workers.
art() {
    if [ "$(id -u)" = "0" ]; then
        runuser -u www-data -- php artisan "$@"
    else
        php artisan "$@"
    fi
}

if [ -z "$APP_KEY" ]; then
    echo "FATAL: APP_KEY is not set. Generate one with:  echo \"base64:\$(openssl rand -base64 32)\"" >&2
    exit 1
fi

# Named volume may be empty / root-owned on first start
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

if [ "$1" = "apache2-foreground" ]; then
    # Ensure public/storage symlink exists so uploaded media (videos, images,
    # school logos) are served at /storage/... . Remove any stale directory or
    # broken symlink first so storage:link succeeds.
    if [ -e public/storage ] && [ ! -L public/storage ]; then
        rm -rf public/storage
    fi
    [ -L public/storage ] && unlink public/storage
    php artisan storage:link --force || echo "WARN: storage:link failed; uploaded files will 404" >&2

    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        echo "==> Running migrations and seeding database"
        art migrate --force
        art db:seed --force
    fi

    echo "==> Building production caches"
    art config:cache
    art route:cache
    art view:cache
    art event:cache || true
    art icons:cache || true
    art permission:cache-reset || true
else
    # Queue worker / scheduler: only the config cache is needed
    art config:cache
fi

# The shared storage volume is written by all three containers, so a file that
# was created by a root process (a `docker compose exec php artisan ...` run
# as root, for instance) would leave the Apache workers unable to touch it —
# and Blade touches a compiled view whenever its source is newer, which turns
# into a 500 on every page that uses that view. Hand the volume back to
# www-data so the workers keep the last word on their own files.
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

exec "$@"
