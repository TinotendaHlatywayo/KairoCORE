# Deploy Kairo CORE on DigitalOcean (via GitHub Education)

## What You Get Free
- **DigitalOcean $200 credit** from GitHub Student Developer Pack
- Runs a 4GB/2CPU droplet for ~8 months, or 2GB for ~20 months

## Step 1: Claim DigitalOcean Credit

1. Go to https://education.github.com/pack
2. Find "DigitalOcean" → click "Get this offer"
3. Create a DigitalOcean account (or log in)
4. $200 credit is applied automatically

## Step 2: Create a Droplet

1. DigitalOcean Dashboard → "Create" → "Droplets"
2. **Image**: Ubuntu 24.04 (LTS)
3. **Plan**: Basic → Regular → 4GB RAM / 2 CPUs ($24/mo)
4. **Datacenter**: Choose closest to your users (e.g., NYC, SFO, London)
5. **Authentication**: SSH keys (recommended) or Password
6. **Hostname**: `kairocore-prod`
7. Click "Create Droplet"

## Step 3: Point Your Domain DNS

At your domain registrar (Namecheap, Name.com, or wherever you bought the domain):

| Type | Host | Value | TTL |
|---|---|---|---|
| A | `@` | `YOUR_DROPLET_IP` | 600 |
| A | `*` | `YOUR_DROPLET_IP` | 600 |

The wildcard record (`*`) is critical — it routes all subdomains (chiwariraprimary.kairocore.com, tinwayacademy.kairocore.com) to your server.

**Wait 10-30 minutes for DNS propagation**, then verify:
```bash
dig +short kairocore.com
dig +short chiwariraprimary.kairocore.com
```
Both should return your droplet IP.

## Step 4: Run the Setup Script

```bash
ssh root@YOUR_DROPLET_IP

# Download and run the setup script
curl -sL https://raw.githubusercontent.com/TinotendaHlatywayo/KairoCORE/main/deploy/setup-droplet.sh | bash -s -- kairocore.com
```

Or clone manually:
```bash
ssh root@YOUR_DROPLET_IP
git clone https://github.com/TinotendaHlatywayo/KairoCORE.git /var/www/kairocore
cd /var/www/kairocore
bash deploy/setup-droplet.sh kairocore.com
```

The script:
- Installs PHP 8.3, MySQL 8, Nginx, Composer, Node.js
- Creates the database + user with a secure random password
- Clones the repo and installs dependencies
- Runs migrations, builds frontend, caches everything
- Configures Nginx with wildcard subdomain support
- Saves DB credentials to `.env.production.local`

## Step 5: Enable SSL (After DNS Propagates)

```bash
ssh root@YOUR_DROPLET_IP
certbot --nginx -d kairocore.com -d *.kairocore.com
```

Certbot auto-renews via cron. SSL is free via Let's Encrypt.

## Step 6: Set Up Auto-Updates (Optional)

Pull from GitHub on every push:
```bash
cd /var/www/kairocore
git pull origin main
composer install --no-dev --optimize-autoloader
npm install --ignore-scripts && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## Multi-Tenant Flow

1. **Register a new school**: `https://kairocore.com/register`
2. **School gets subdomain**: e.g., `chiwariraprimary.kairocore.com`
3. **Admin approves**: Platform admin sets status to `active`
4. **School activates**: Admin clicks activation link
5. **School logs in**: At `https://chiwariraprimary.kairocore.com/workspace`

Each school's data is isolated via `school_id` column (BelongsToTenant trait).

## Architecture

```
DNS: *.kairocore.com → YOUR_DROPLET_IP
Nginx: wildcard server block → Laravel
ResolveTenant middleware: subdomain → School model → current_tenant
All queries: WHERE school_id = current_tenant.id (via TenantScope)
```

## Cost

| Item | Cost | Duration |
|---|---|---|
| DigitalOcean Droplet (4GB) | $24/mo | $200 credit = ~8 months free |
| DigitalOcean Droplet (2GB) | $12/mo | $200 credit = ~16 months free |
| Domain (Namecheap .me) | Free 1 year | Via GitHub Education |
| SSL (Let's Encrypt) | Free forever | Auto-renews |
| **Total** | **$0** for 8-16 months | Then $12-24/mo |

## Troubleshooting

| Issue | Fix |
|---|---|
| 502 Bad Gateway | PHP-FPM not running: `systemctl restart php8.3-fpm` |
| 404 on subdomain | DNS not propagated: `dig +short subdomain.domain.com` |
| Migration fails | Check DB credentials in `.env` |
| Permission denied | `chown -R www-data:www-data /var/www/kairocore/storage` |
| Nginx config error | `nginx -t` then `systemctl reload nginx` |
