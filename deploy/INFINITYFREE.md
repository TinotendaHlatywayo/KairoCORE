# Deploy Kairo CORE to InfinityFree

## Prerequisites
- InfinityFree account (free)
- Git installed locally
- PHP 8.3+ locally (for Composer)
- FileZilla or any FTP client

## Step 1: Create MySQL Database on InfinityFree

1. Login to InfinityFree Control Panel
2. Go to "MySQL Databases"
3. Create a new database
4. **Note down**: DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD

## Step 2: Prepare .env Locally

Edit `.env.infinityfree` with your InfinityFree database credentials:

```
DB_HOST=sql123.infinityfree.com
DB_DATABASE=epiz_XXXXXXXX_schoolcore
DB_USERNAME=epiz_XXXXXXXX
DB_PASSWORD=your_password_here
```

Also update:
```
APP_URL=https://yourdomain.infinityfree.app
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
```

## Step 3: Generate APP_KEY Locally

```bash
cd /opt/lampp/htdocs/SchoolManagementSystem/schoolcore
php artisan key:generate --force
```

Copy the `APP_KEY=` line from `.env` — you'll need it.

## Step 4: Build for Production

```bash
# Install production dependencies only
composer install --no-dev --optimize-autoloader --no-scripts

# Run artisan commands locally BEFORE uploading
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## Step 5: Upload via FTP

1. Connect to InfinityFree FTP (credentials in control panel)
2. Upload EVERYTHING from the project root to `htdocs/`
3. **Exclude** these folders (too large, not needed):
   - `node_modules/`
   - `.git/`
   - `tests/`
   - `deploy/`
   - `docker/`
   - `Dockerfile*`
   - `docker-compose*`
   - `railway.json`

## Step 6: Import Database

1. Go to InfinityFree Control Panel → phpMyAdmin
2. Select your database
3. Click "Import" tab
4. Upload `deploy/schoolcore-full.sql.gz`
5. Click "Go"

## Step 7: Set File Permissions

In InfinityFree File Manager or via FTP:
- `storage/` → 755 (recursive)
- `bootstrap/cache/` → 755 (recursive)
- `public/` → 755

## Step 8: Verify

Visit your domain. You should see the Kairo CORE login page bound to Chiwarira Primary.

---

## Troubleshooting

| Issue | Fix |
|---|---|
| 500 error | Check `.env` has correct DB credentials and APP_KEY |
| "No encryption key" | Set APP_KEY in .env (generate locally: `php artisan key:generate`) |
| White page | Check `storage/logs/laravel.log` via phpMyAdmin or file manager |
| Slow first load | Normal — free hosting cold start takes 10-30s |
| CSS/JS not loading | Ensure `.htaccess` redirect is working, public/ has correct permissions |
