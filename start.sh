#!/bin/sh
set -e

echo "==> Kairo CORE — Railway Deployment"

# ── Vite build placeholder (must exist before ANY artisan command) ──────
mkdir -p public/build
echo '{}' > public/build/manifest.json
# Vite also checks for this directory structure
mkdir -p public/build/.vite

# ── Generate APP_KEY if missing ────────────────────────────────────────
if [ -z "$APP_KEY" ]; then
    echo "==> Generating APP_KEY..."
    php artisan key:generate --force
fi

# ── Ensure .env exists ─────────────────────────────────────────────────
if [ ! -f .env ]; then
    cp .env.railway .env 2>/dev/null || touch .env
fi

# ── Link storage ───────────────────────────────────────────────────────
php artisan storage:link --force 2>/dev/null || true

# ── Package discovery + Filament setup ─────────────────────────────────
php artisan package:discover --ansi 2>/dev/null || true
php artisan filament:upgrade --force 2>/dev/null || true

# ── Database migrations ───────────────────────────────────────────────
echo "==> Running database migrations..."
php artisan migrate --force

# ── Seed roles/permissions cache ───────────────────────────────────────
php artisan permission:cache-reset 2>/dev/null || true

# ── Build module navigation cache ─────────────────────────────────────
php artisan module-nav:build 2>/dev/null || true

# ── Clear & cache everything for production ────────────────────────────
echo "==> Building production caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true
php artisan icons:cache 2>/dev/null || true

# ── Fix permissions ────────────────────────────────────────────────────
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "==> Starting services on port ${PORT:-8080}..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
