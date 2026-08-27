#!/bin/sh
set -e

echo "==> Kairo CORE — Railway Deployment"
echo "    TENANCY_MODE=$(php -r "echo config('tenancy.mode') ?? 'unset';" 2>/dev/null || echo 'config not ready')"

# ── Generate APP_KEY if missing ────────────────────────────────────────
if [ -z "$APP_KEY" ]; then
    echo "==> Generating APP_KEY..."
    php artisan key:generate --force
fi

# ── Build .env from Railway env vars ───────────────────────────────────
# Railway sets env vars directly; .env file is a fallback for artisan
if [ ! -f .env ]; then
    cp .env.railway .env 2>/dev/null || true
fi

# ── Link storage ───────────────────────────────────────────────────────
php artisan storage:link --force 2>/dev/null || true

# ── Database migrations ───────────────────────────────────────────────
echo "==> Running database migrations..."
php artisan migrate --force

# ── Seed roles/permissions if tables are empty ─────────────────────────
ROLE_COUNT=$(php artisan tinker --execute="echo \Spatie\Permission\Models\Role::count();" 2>/dev/null || echo "0")
if [ "$ROLE_COUNT" = "0" ]; then
    echo "==> Seeding roles & permissions..."
    php artisan permission:cache-reset 2>/dev/null || true
fi

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
