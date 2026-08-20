#!/usr/bin/env bash
# =====================================================================
# SchoolCore HTTPS bootstrap - run ONCE with sudo:
#
#     sudo bash deploy/setup-https.sh
#
# Installs the self-signed lvh.me certificate, enables the Apache vhosts
# include, and restarts XAMPP so the app is reachable at https://lvh.me
# =====================================================================
set -euo pipefail

APP_DIR="/opt/lampp/htdocs/SchoolManagementSystem/schoolcore"
HTTPD_CONF="/opt/lampp/etc/httpd.conf"
VHOSTS_CONF="/opt/lampp/etc/extra/httpd-vhosts.conf"
CERT_DST="/opt/lampp/etc/ssl.crt"
KEY_DST="/opt/lampp/etc/ssl.key"

echo "==> 1/4 Installing certificate + key"
install -m 644 "$APP_DIR/deploy/certs/lvh.me.crt" "$CERT_DST/lvh.me.crt"
install -m 600 "$APP_DIR/deploy/certs/lvh.me.key" "$KEY_DST/lvh.me.key"

echo "==> 2/4 Installing virtual hosts config"
install -m 644 "$APP_DIR/deploy/httpd-vhosts.conf" "$VHOSTS_CONF"

echo "==> 3/4 Enabling the vhosts include in httpd.conf"
if grep -q '^#Include etc/extra/httpd-vhosts.conf' "$HTTPD_CONF"; then
    sed -i 's|^#Include etc/extra/httpd-vhosts.conf|Include etc/extra/httpd-vhosts.conf|' "$HTTPD_CONF"
    echo "    -> include enabled"
else
    echo "    -> include already enabled (skipped)"
fi

echo "==> 4/4 Restarting XAMPP"
/opt/lampp/xampp restart

echo ""
echo "Done. Verify with:"
echo "    curl -I https://lvh.me  (expect 200 and Location headers over HTTPS)"
echo "    curl -I http://lvh.me   (expect 301 -> https)"
