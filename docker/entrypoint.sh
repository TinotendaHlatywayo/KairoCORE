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
    # Run as the current user (root): www-data cannot write to the root-owned
    # public/ directory, so linking through art() would silently fail and every
    # uploaded file (/storage/...) would 404.
    php artisan storage:link --force || echo "WARN: storage:link failed; uploaded files will 404" >&2

    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        echo "==> Running migrations"
        art migrate --force
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

exec "$@"
