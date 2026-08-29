#!/usr/bin/env bash
# =====================================================================
# Kairo CORE - incremental deploy to the Azure VM (idempotent)
#
# Run ON the VM (from the repo checkout at /var/www/kairocore):
#
#     sudo bash deploy/update-vm.sh
#
# - Pulls latest code from the configured git remote (origin)
# - Installs any new composer deps (no-dev) + rebuilds the Vite bundle
# - Re-runs package discovery + Filament asset publish
# - Clears and re-caches config/routes/views/events
# - Runs any pending migrations
# - Ensures the queue worker systemd service is up
# =====================================================================
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/kairocore}"
PHP_USER="${PHP_USER:-www-data}"
REMOTE_BRANCH="${REMOTE_BRANCH:-main}"

if [[ ! -d "$APP_DIR/.git" ]]; then
    echo "ERROR: $APP_DIR is not a git checkout." >&2
    exit 1
fi

echo "==> 0/10 Setting app ownership + git safe.directory for $PHP_USER"
# Nginx/PHP-FPM run as $PHP_USER and must own the whole app to run composer/artisan.
# Running this every deploy is idempotent and fixes storage/vendor ownership drift.
sudo chown -R "$PHP_USER":"$PHP_USER" "$APP_DIR"
# Write safe.directory to the repo-local config (survives regardless of HOME).
cd "$APP_DIR"
sudo -u "$PHP_USER" git config --local --add safe.directory "$APP_DIR"

# Optional maintenance window for deploys that touch a busy live system.
# Enable with: MAINTENANCE=1 sudo bash deploy/update-vm.sh
# This puts the site into Laravel maintenance mode for the duration, so in-flight
# users get a friendly 503 "back shortly" page instead of a half-deployed state.
RESTORE_MAINTENANCE=0
if [[ "${MAINTENANCE:-0}" == "1" ]]; then
    echo "(MAINTENANCE mode enabled — queuing restore on exit)"
    RESTORE_MAINTENANCE=1
    sudo -u "$PHP_USER" php artisan down --retry=10 --render="errors.503" 2>/dev/null || sudo -u "$PHP_USER" php artisan down --retry=10
fi

# Ensure maintenance mode is always brought back up, even on failure.
restore() {
    if [[ "$RESTORE_MAINTENANCE" == "1" ]]; then
        sudo -u "$PHP_USER" php artisan up
        echo "(Site is back up)"
    fi
}
trap restore EXIT

echo "==> 1/10 Fetching + checking out latest ${REMOTE_BRANCH}"
# Safer than `reset --hard`: leaves ignored/storage files alone and aborts if there
# are unexpected local edits to tracked files (so we never silently wipe live changes).
sudo -u "$PHP_USER" git fetch origin
sudo -u "$PHP_USER" git checkout --force "origin/${REMOTE_BRANCH}"

echo "==> 2/10 Installing composer dependencies (no-dev)"
sudo -u "$PHP_USER" HOME=/var/www COMPOSER_HOME=/var/www/.composer composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

echo "==> 3/10 Building frontend assets (Vite)"
sudo -u "$PHP_USER" HOME=/var/www npx vite build 2>/dev/null || sudo -u "$PHP_USER" HOME=/var/www npm run build

echo "==> 4/10 Package discovery + Filament asset publish"
sudo -u "$PHP_USER" HOME=/var/www php artisan package:discover --ansi
sudo -u "$PHP_USER" HOME=/var/www php artisan filament:assets --ansi || true
sudo -u "$PHP_USER" HOME=/var/www composer dump-autoload --no-dev --optimize

echo "==> 5/10 Clearing stale caches"
sudo -u "$PHP_USER" HOME=/var/www php artisan config:clear
sudo -u "$PHP_USER" HOME=/var/www php artisan route:clear
sudo -u "$PHP_USER" HOME=/var/www php artisan view:clear
sudo -u "$PHP_USER" HOME=/var/www php artisan event:clear

echo "==> 6/10 Running migrations"
sudo -u "$PHP_USER" HOME=/var/www php artisan migrate --force

echo "==> 7/10 Re-caching config/routes/views/events"
sudo -u "$PHP_USER" HOME=/var/www php artisan config:cache
sudo -u "$PHP_USER" HOME=/var/www php artisan route:cache
sudo -u "$PHP_USER" HOME=/var/www php artisan view:cache
sudo -u "$PHP_USER" HOME=/var/www php artisan event:cache

echo "==> 8/10 Storage link (idempotent)"
sudo -u "$PHP_USER" HOME=/var/www php artisan storage:link 2>/dev/null || true

echo "==> 9/10 Ensuring queue worker is running"
sudo install -m 644 "$APP_DIR/deploy/queue-worker.service" /etc/systemd/system/kairocore-queue.service
sudo systemctl daemon-reload
sudo systemctl enable kairocore-queue.service 2>/dev/null || true
sudo systemctl restart kairocore-queue.service
sudo systemctl --no-pager --lines=3 status kairocore-queue.service || true

echo ""
echo "Deploy complete. Reloading PHP-FPM + Nginx for safety..."
sudo systemctl reload php8.3-fpm 2>/dev/null || true
sudo nginx -t && sudo systemctl reload nginx
echo "Done."
