#!/usr/bin/env bash
# =====================================================================
# SchoolCore - Switch XAMPP Apache to system PHP 8.4-FPM
#
# XAMPP bundles PHP 8.2 (modules/libphp.so) but Laravel 13 / this app
# requires PHP >= 8.4.1. This script:
#   1. Installs php8.4-fpm (+ common extensions)
#   2. Starts/enables the FPM service on a dedicated 127.0.0.1:9001 pool
#   3. Disables the bundled mod_php in /opt/lampp/etc/httpd.conf
#   4. Routes every .php request to PHP 8.4-FPM
#   5. Restarts XAMPP
#
# Run with sudo:   sudo bash deploy/enable-php84-fpm.sh
# =====================================================================
set -euo pipefail

HTTPD_CONF="/opt/lampp/etc/httpd.conf"
FPM_CONF="/opt/lampp/etc/extra/php84-fpm.conf"
POOL_FILE="/etc/php/8.4/fpm/pool.d/schoolcore.conf"

echo "==> 1/5 Installing php8.4-fpm and extensions"
apt-get update -qq
DEBIAN_FRONTEND=noninteractive apt-get install -y \
    php8.4-fpm \
    php8.4-gd php8.4-zip php8.4-intl php8.4-bcmath php8.4-mbstring php8.4-mysql

echo "==> 2/5 Creating dedicated FPM pool on 127.0.0.1:9001"
cat > "$POOL_FILE" <<'EOF'
; SchoolCore dedicated pool - PHP 8.4 FPM for XAMPP Apache
[schoolcore]
user = www-data
group = www-data
listen = 127.0.0.1:9001
listen.allowed_clients = 127.0.0.1
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500
request_terminate_timeout = 120
catch_workers_output = yes
EOF

echo "==> 3/5 Enabling and restarting php8.4-fpm"
systemctl enable php8.4-fpm
systemctl restart php8.4-fpm

echo "==> 4/5 Reconfiguring XAMPP Apache"
# Disable the bundled PHP 8.2 module
sed -i 's|^LoadModule php_module        modules/libphp.so|# LoadModule php_module modules/libphp.so (disabled by deploy/enable-php84-fpm.sh - using system PHP 8.4-FPM)|' "$HTTPD_CONF"

# Add the FPM handler include (idempotent)
cat > "$FPM_CONF" <<'EOF'
# Route all .php requests to system PHP 8.4-FPM
<IfModule mod_proxy_fcgi.c>
    <FilesMatch \.php$>
        SetHandler "proxy:fcgi://127.0.0.1:9001"
    </FilesMatch>
</IfModule>
EOF

if ! grep -q 'php84-fpm.conf' "$HTTPD_CONF"; then
    sed -i 's|^Include etc/extra/httpd-ssl.conf|Include etc/extra/httpd-ssl.conf\nInclude etc/extra/php84-fpm.conf|' "$HTTPD_CONF"
fi

echo "==> 5/5 Restarting XAMPP"
/opt/lampp/xampp restart

echo ""
echo "Done. Verify with:"
echo "    curl -sk https://lvh.me/                 (expect 200)"
echo "    curl -sk https://lvh.me/build/manifest.json (expect JSON)"
echo ""
echo "Note: phpMyAdmin and other htdocs PHP apps will now also run on PHP 8.4."
