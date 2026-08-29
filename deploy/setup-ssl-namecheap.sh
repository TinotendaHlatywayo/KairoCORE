#!/usr/bin/env bash
# =====================================================================
# Kairo CORE - Azure VM automatic wildcard SSL via Let's Encrypt (DNS-01)
#
#   sudo NC_API_USER=youruser NC_API_KEY=yourkey bash deploy/setup-ssl-namecheap.sh
#
# Obtains + auto-renews a wildcard cert for:
#     kairocore.me   and   *.kairocore.me
#
# Uses the certbot-dns-namecheap plugin (knoxell/certbot-dns-namecheap)
# which creates/removes the required TXT records via the Namecheap API,
# so the wildcard cert renews automatically every 60 days with no manual
# intervention.
#
# PREREQUISITES (do these in the Namecheap dashboard first):
#   1. Enable API access: Settings -> Tools -> API Access -> ENABLE
#   2. Whitelist the VM public IP (102.37.136.65) in that API settings page.
#      Namecheap ONLY accepts API calls from whitelisted IPs.
#   3. Grab your API key from the same page.
#
# Replace kairocore.me / *.kairocore.me below if your domain differs.
# =====================================================================
set -euo pipefail

DOMAIN="${1:-kairocore.me}"
SLD="${DOMAIN%%.*}"
TLD="${DOMAIN#*.}"
CLIENT_IP="${CLIENT_IP:-102.37.136.65}"

if [[ -z "${NC_API_USER:-}" || -z "${NC_API_KEY:-}" ]]; then
    echo "ERROR: NC_API_USER and NC_API_KEY env vars are required." >&2
    echo "  sudo NC_API_USER=yours NC_API_KEY=yourkey bash deploy/setup-ssl-namecheap.sh" >&2
    exit 1
fi

echo "==> 1/6 Installing certbot-dns-namecheap plugin"
apt-get update -y
apt-get install -y certbot python3-pip
pip3 install certbot-dns-namecheap

echo "==> 2/6 Writing Namecheap credentials file"
umask 0077
mkdir -p /etc/letsencrypt/namecheap
cat > /etc/letsencrypt/namecheap/namecheap.ini <<INI
# Namecheap API credentials for certbot dns-namecheap plugin
dns_namecheap_api_user = ${NC_API_USER}
dns_namecheap_api_key = ${NC_API_KEY}
dns_namecheap_client_ip = ${CLIENT_IP}
INI

echo "==> 3/6 Verifying plugin is visible to certbot"
certbot plugins 2>/dev/null | grep -q "dns-namecheap" \
    && echo "    -> plugin found" \
    || { echo "ERROR: dns-namecheap plugin not detected" >&2; exit 1; }

echo "==> 4/6 Obtaining wildcard certificate (dry-run first)"
certbot certonly \
    --authenticator dns-namecheap \
    --dns-namecheap-credentials /etc/letsencrypt/namecheap/namecheap.ini \
    --agree-tos \
    --no-eff-email \
    --register-unsafely-without-email \
    -d "${DOMAIN}" -d "*.${DOMAIN}" \
    --dry-run

echo "==> 5/6 Obtaining the real certificate"
certbot install --cert-name "${DOMAIN}" \
    --authenticator dns-namecheap \
    --dns-namecheap-credentials /etc/letsencrypt/namecheap/namecheap.ini \
    -d "${DOMAIN}" -d "*.${DOMAIN}" \
    || \
certbot certonly \
    --authenticator dns-namecheap \
    --dns-namecheap-credentials /etc/letsencrypt/namecheap/namecheap.ini \
    --agree-tos \
    --no-eff-email \
    --register-unsafely-without-email \
    -d "${DOMAIN}" -d "*.${DOMAIN}"

echo "==> 6/6 Confirming renewal is wired up (certbot renew --dry-run)"
certbot renew --dry-run

echo ""
echo "Done! Wildcard certificate issued and auto-renews."
echo "    Domain:  ${DOMAIN} / *.${DOMAIN}"
echo "    Cert:    /etc/letsencrypt/live/${DOMAIN}/"
echo ""
echo "Next: point Nginx at the cert (see deploy/nginx/kairocore-ssl.conf) and reload Nginx."
