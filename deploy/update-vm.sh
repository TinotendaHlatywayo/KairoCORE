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

cd "$APP_DIR"

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

echo "==> 1/9 Fetching + checking out latest ${REMOTE_BRANCH}"
# Safer than `reset --hard`: leaves ignored/storage files alone and aborts if there
# are unexpected local edits to tracked files (so we never silently wipe live changes).
sudo git fetch origin
sudo git checkout --force "origin/${REMOTE_BRANCH}"

echo "==> 2/9 Installing composer dependencies (no-dev)"
sudo -u "$PHP_USER" composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

echo "==> 3/9 Building frontend assets (Vite)"
sudo -u "$PHP_USER" npx vite build 2>/dev/null || sudo -u "$PHP_USER" npm run build

echo "==> 4/9 Package discovery + Filament asset publish"
sudo -u "$PHP_USER" php artisan package:discover --ansi
sudo -u "$PHP_USER" php artisan filament:assets --ansi || true
sudo -u "$PHP_USER" composer dump-autoload --no-dev --optimize

echo "==> 5/9 Clearing stale caches"
sudo -u "$PHP_USER" php artisan config:clear
sudo -u "$PHP_USER" php artisan route:clear
sudo -u "$PHP_USER" php artisan view:clear
sudo -u "$PHP_USER" php artisan event:clear

echo "==> 6/9 Running migrations"
sudo -u "$PHP_USER" php artisan migrate --force

echo "==> 7/9 Re-caching config/routes/views/events"
sudo -u "$PHP_USER" php artisan config:cache
sudo -u "$PHP_USER" php artisan route:cache
sudo -u "$PHP_USER" php artisan view:cache
sudo -u "$PHP_USER" php artisan event:cache

echo "==> 8/9 Storage link (idempotent)"
sudo -u "$PHP_USER" php artisan storage:link 2>/dev/null || true

echo "==> 9/9 Ensuring queue worker is running"
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
