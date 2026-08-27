# Deploy Kairo CORE to Railway.app

## Prerequisites
- GitHub account with push access to `TinotendaHlatywayo/KairoCORE`
- Railway.app account (free tier: $5/month credit)
- This repo pushed to GitHub

## Step 1: Push to GitHub

```bash
cd /opt/lampp/htdocs/SchoolManagementSystem/schoolcore
git remote add kairo https://github.com/TinotendaHlatywayo/KairoCORE.git
git push kairo main
```

## Step 2: Create Railway Project

1. Go to https://railway.app/new
2. Click "Deploy from GitHub Repo"
3. Select `TinotendaHlatywayo/KairoCORE`
4. Railway will detect the Dockerfile and start building

## Step 3: Add MySQL Database

1. In the Railway dashboard, click "+ New" → "Database" → "MySQL"
2. Railway auto-generates `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
3. These are injected as env vars to your app service

## Step 4: Set Environment Variables

Go to your app service → "Variables" tab and set:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=                    (leave blank — Railway generates this, or run php artisan key:generate)
TENANCY_MODE=single
SINGLE_TENANT_ID=4
TENANT_BASE_DOMAIN=your-app.up.railway.app
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=file
MAIL_MAILER=log
```

**Do NOT set `APP_KEY` manually** — let `php artisan key:generate` handle it in the start script.

## Step 5: Import Database Schema

After the first deploy builds but before the app can fully run:

1. Open Railway MySQL shell:
   - Click the MySQL service → "Data" tab → "Query"
2. Paste the contents of `deploy/schoolcore.sql` (or run migrations via Railway shell)
3. OR: SSH into the app container and run:
   ```bash
   railway shell
   php artisan migrate --force
   php artisan permission:cache-reset
   ```

## Step 6: Seed Chiwarira Data

If deploying with existing Chiwarira Primary data:

```bash
railway shell
php artisan migrate --force
php artisan permission:cache-reset
```

The single-tenant mode (SINGLE_TENANT_ID=4) will bind Chiwarira automatically.

## Step 7: Custom Domain (Optional)

1. In Railway dashboard → your app → "Settings" → "Networking"
2. Add your custom domain (e.g., `kairocore.com`)
3. Update DNS to point to Railway's provided CNAME
4. Update `APP_URL` and `TENANT_BASE_DOMAIN` env vars

## How It Works

- **Single mode**: Every request binds Chiwarira Primary (school_id=4) as the tenant
- **No subdomains needed**: Works on `your-app.up.railway.app`
- **To switch to multi-tenant later**: Change `TENANCY_MODE=multi` in env vars, redeploy
- **Database**: Railway MySQL handles all data, no migration files needed

## Cost

- **Free tier**: $5/month credit (covers ~500 hours of a small service + MySQL)
- **Always-on**: Costs ~$5/month after credit
- **Sleep mode**: Free tier apps sleep after 30 min inactivity, wake on first request (takes ~15-30s)

## Troubleshooting

| Issue | Fix |
|---|---|
| App won't start | Check logs: Railway dashboard → Deployments → Latest → Logs |
| 500 error | Ensure `APP_KEY` is set and `php artisan key:generate` runs in start.sh |
| DB connection failed | Verify MySQL add-on is linked and env vars are set |
| Slow cold start | Normal for free tier — first request after sleep takes 15-30s |
