#!/bin/bash
# =============================================================================
# Kairo CORE — Microsoft Azure Free-Tier VM Setup Script
#
# Run this ONCE on a fresh Ubuntu 24.04 Azure VM (free B-series):
#   ssh azureuser@YOUR_AZURE_PUBLIC_IP
#   bash deploy/setup-azure-vm.sh kairocore.me
#
# Prerequisites:
#   1. Azure VM: Ubuntu 22.04 or 24.04 LTS (x64), Standard SSD disk, public IP
#      (free B-series: B1s / B2ats_v2 — smaller B tiers may be region-restricted)
#   2. DNS: A record + wildcard *.domain pointed to the VM public IP
#   3. Ports 22 (SSH), 80 (HTTP), 443 (HTTPS) open in the VM's NSG
#   4. SSH access configured (username from the VM creation, e.g. azureuser)
#
# This script is tuned for low-memory free-tier VMs:
#   - Adds a 4GB swap file (essential for Filament on 1-2GB RAM)
#   - Uses MariaDB (lighter than MySQL 8)
#   - Tunes MariaDB + PHP-FPM for low memory
#   - Queues run in-process (QUEUE_CONNECTION=sync) to avoid a worker process
# =============================================================================
set -euo pipefail

DOMAIN="${1:-kairocore.me}"
APP_DIR="/var/www/kairocore"
DB_NAME="schoolcore"
DB_USER="kairo"
DB_PASS="$(openssl rand -base64 24)"
PHP_VERSION="8.3"

echo "============================================"
echo " Kairo CORE — Azure Free-Tier VM Setup"
echo " Domain: $DOMAIN"
echo "============================================"
echo ""

# ── 1. System updates ─────────────────────────────────────────────────
echo "[1/13] Updating system packages..."
apt-get update -y
apt-get upgrade -y
apt-get install -y software-properties-common curl git unzip

# ── 2. Add a swap file (small VMs need it) ─────────────────────────────
echo "[2/13] Adding 4GB swap file (for low-memory free-tier VM)..."
if [ ! -f /swapfile ]; then
    fallocate -l 4G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi
sysctl vm.swappiness=20 || true
echo "vm.swappiness=20" > /etc/sysctl.d/99-swp.conf || true
echo "    Swap active: $(swapon --show 2>/dev/null | tail -1 | awk '{print $1, $2}')"

# ── 3. Add PHP 8.3 repository ─────────────────────────────────────────
echo "[3/13] Installing PHP $PHP_VERSION..."
add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-opcache \
    php${PHP_VERSION}-readline

# ── 4. Install MariaDB ────────────────────────────────────────────────
echo "[4/13] Installing MariaDB (lighter than MySQL, ideal for 1-2GB VMs)..."
apt-get install -y mariadb-server
systemctl enable mariadb
systemctl start mariadb

# Tune MariaDB for low memory (shrinks buffer pool from default)
cat > /etc/mysql/mariadb.conf.d/99-kairocore-lowmem.cnf << 'MARIADB'
[mysqld]
innodb_buffer_pool_size = 128M
innodb_log_buffer_size = 4M
innodb_flush_log_at_trx_commit = 2
max_connections = 40
key_buffer_size = 16M
query_cache_type = 0
MARIADB
systemctl restart mariadb

# Create database and user
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
echo "    Database: $DB_NAME"
echo "    User:     $DB_USER"
echo "    Password: $DB_PASS"

# ── 5. Install Nginx ─────────────────────────────────────────────────
echo "[5/13] Installing Nginx..."
apt-get install -y nginx
systemctl enable nginx
systemctl start nginx

# ── 6. Install Composer ──────────────────────────────────────────────
echo "[6/13] Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ── 7. Install Node.js (for frontend assets) ──────────────────────────
echo "[7/13] Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt-get install -y nodejs

# ── 8. Clone repository ──────────────────────────────────────────────
echo "[8/13] Cloning Kairo CORE..."
git clone https://github.com/TinotendaHlatywayo/KairoCORE.git ${APP_DIR}
cd ${APP_DIR}

# ── 9. Configure .env ────────────────────────────────────────────────
echo "[9/13] Configuring .env..."
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
QUEUE_CONNECTION=sync

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

# ── 10. Install dependencies & build ──────────────────────────────────
echo "[10/13] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

echo "    Building frontend assets..."
npm install --ignore-scripts
npm run build

# ── 11. Laravel setup ────────────────────────────────────────────────
echo "[11/13] Running Laravel setup..."
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

# ── 12. Configure Nginx + PHP-FPM for low memory ─────────────────────
echo "[12/13] Configuring Nginx & PHP-FPM..."

# PHP-FPM low-memory tuning (prevent OOM on small VMs)
sed -i 's/^pm = .*/pm = ondemand/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
sed -i 's/^pm.max_children = .*/pm.max_children = 8/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
sed -i 's/^;pm.max_requests = .*/pm.max_requests = 100/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
systemctl restart php${PHP_VERSION}-fpm

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

# ── 13. Install Certbot for SSL ──────────────────────────────────────
echo "[13/13] Installing Certbot for SSL..."
apt-get install -y certbot python3-certbot-nginx

# Save credentials
cat > ${APP_DIR}/.env.production.local << EOF
# Database credentials (SAVE THESE)
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
EOF
chmod 600 ${APP_DIR}/.env.production.local

# Reminder for Azure auto-shutdown (keeps VM from running 24/7 on credit)
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
echo "  1. Ensure DNS A record + wildcard *.${DOMAIN} point to your VM public IP"
echo "  2. Run SSL setup (after DNS propagates, ~10 min):"
echo "     certbot --nginx -d ${DOMAIN} -d *.${DOMAIN}"
echo "  3. Log in with your super admin account"
echo "  4. In Azure, set AUTO-SHUTDOWN to save your $100 credit"
echo ""
echo "To add your first tenant school, register at:"
echo "  http://${DOMAIN}/register"
echo ""
echo "============================================"
