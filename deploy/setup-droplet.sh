#!/bin/bash
# =============================================================================
# Kairo CORE — DigitalOcean Droplet Setup Script
#
# Run this ONCE on a fresh Ubuntu 24.04 droplet as root:
#   ssh root@YOUR_DROPLET_IP
#   bash deploy/setup-droplet.sh kairocore.com
#
# Prerequisites:
#   1. Fresh Ubuntu 24.04 droplet (4GB RAM / 2CPU recommended)
#   2. Domain pointed to droplet IP (A record + wildcard *.domain)
#   3. SSH key access configured
# =============================================================================
set -euo pipefail

DOMAIN="${1:-kairocore.com}"
APP_DIR="/var/www/kairocore"
DB_NAME="schoolcore"
DB_USER="kairo"
DB_PASS="$(openssl rand -base64 24)"
PHP_VERSION="8.3"

echo "============================================"
echo " Kairo CORE — DigitalOcean Droplet Setup"
echo " Domain: $DOMAIN"
echo "============================================"
echo ""

# ── 1. System updates ─────────────────────────────────────────────────
echo "[1/12] Updating system packages..."
apt-get update -y
apt-get upgrade -y
apt-get install -y software-properties-common curl git unzip

# ── 2. Add PHP 8.3 repository ─────────────────────────────────────────
echo "[2/12] Installing PHP $PHP_VERSION..."
add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-pgsql \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-opcache \
    php${PHP_VERSION}-readline

# ── 3. Install MySQL 8 ───────────────────────────────────────────────
echo "[3/12] Installing MySQL 8..."
apt-get install -y mysql-server
systemctl enable mysql
systemctl start mysql

# Create database and user
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
echo "    Database: $DB_NAME"
echo "    User:     $DB_USER"
echo "    Password: $DB_PASS"

# ── 4. Install Nginx ─────────────────────────────────────────────────
echo "[4/12] Installing Nginx..."
apt-get install -y nginx
systemctl enable nginx
systemctl start nginx

# ── 5. Install Composer ──────────────────────────────────────────────
echo "[5/12] Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ── 6. Install Node.js (for frontend assets) ──────────────────────────
echo "[6/12] Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt-get install -y nodejs

# ── 7. Clone repository ──────────────────────────────────────────────
echo "[7/12] Cloning Kairo CORE..."
git clone https://github.com/TinotendaHlatywayo/KairoCORE.git ${APP_DIR}
cd ${APP_DIR}

# ── 8. Configure .env ────────────────────────────────────────────────
echo "[8/12] Configuring .env..."
APP_KEY=$(php artisan key:generate --show)

cat > .env << EOF
APP_NAME="Kairo CORE"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=https://${DOMAIN}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
SESSION_COOKIE=kairocore_session

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=file

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@${DOMAIN}"
MAIL_FROM_NAME="\${APP_NAME}"

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=/auth/google/callback
GOOGLE_LOGIN_ENABLED=false

PAYNOW_INTEGRATION_ID=
PAYNOW_INTEGRATION_KEY=
PAYNOW_MERCHANT_EMAIL=

TENANCY_MODE=multi
TENANT_BASE_DOMAIN=${DOMAIN}
EOF

# ── 9. Install dependencies & build ──────────────────────────────────
echo "[9/12] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

echo "    Building frontend assets..."
npm install --ignore-scripts
npm run build

# ── 10. Laravel setup ────────────────────────────────────────────────
echo "[10/12] Running Laravel setup..."
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache 2>/dev/null || true
php artisan storage:link --force 2>/dev/null || true
php artisan permission:cache-reset 2>/dev/null || true

# Permissions
chown -R www-data:www-data ${APP_DIR}/storage ${APP_DIR}/bootstrap/cache
chmod -R 775 ${APP_DIR}/storage ${APP_DIR}/bootstrap/cache

# ── 11. Configure Nginx ──────────────────────────────────────────────
echo "[11/12] Configuring Nginx..."
cat > /etc/nginx/sites-available/kairocore << NGINX
server {
    listen 80;
    server_name ${DOMAIN} *.${DOMAIN};

    root ${APP_DIR}/public;
    index index.php;

    charset utf-8;
    client_max_body_size 20M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
        fastcgi_buffering off;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/kairocore /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# ── 12. Install Certbot for SSL ──────────────────────────────────────
echo "[12/12] Installing Certbot for SSL..."
apt-get install -y certbot python3-certbot-nginx

# Save credentials
cat > ${APP_DIR}/.env.production.local << EOF
# Database credentials (SAVE THESE)
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
EOF
chmod 600 ${APP_DIR}/.env.production.local

echo ""
echo "============================================"
echo " SETUP COMPLETE"
echo "============================================"
echo ""
echo "Your Kairo CORE is live at:"
echo "  http://${DOMAIN}"
echo ""
echo "Database credentials (saved to .env AND .env.production.local):"
echo "  Database: ${DB_NAME}"
echo "  User:     ${DB_USER}"
echo "  Password: ${DB_PASS}"
echo ""
echo "NEXT STEPS:"
echo "  1. Point DNS: A record + wildcard *.${DOMAIN} → $(curl -s ifconfig.me)"
echo "  2. Run SSL setup (after DNS propagates, ~10 min):"
echo "     certbot --nginx -d ${DOMAIN} -d *.${DOMAIN}"
echo "  3. Log in with your super admin account"
echo ""
echo "To add your first tenant school, register at:"
echo "  http://${DOMAIN}/register"
echo "  Or use the admin panel to onboard manually"
echo ""
echo "============================================"
