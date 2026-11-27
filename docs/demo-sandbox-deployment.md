# Demo Sandbox Deployment Checklist

## DNS and TLS

- [ ] `kairo.co.zw` points to the application host.
- [ ] `demo.kairo.co.zw` resolves to the application host.
- [ ] Wildcard DNS `*.kairo.co.zw` resolves to the application host.
- [ ] Caddy site address includes `kairo.co.zw, *.kairo.co.zw`.
- [ ] Wildcard certificate uses the Cloudflare DNS challenge.
- [ ] Probe `https://demo-deploy-check.kairo.co.zw` reaches Laravel (404 is acceptable; a DNS/TLS error is not).

## Application configuration

- [ ] `APP_URL=https://kairo.co.zw`.
- [ ] `SESSION_DOMAIN` is not set globally; demo sessions stay host-only on each sandbox subdomain.
- [ ] `DEMO_SANDBOX_POOL_ENABLED=true`.
- [ ] `DEMO_SANDBOX_POOL_SIZE=5`.
- [ ] `DEMO_MAX_CONCURRENT=50`.
- [ ] `DEMO_SANDBOX_REFILL_QUEUE=demo-pool`.
- [ ] `DEMO_SANDBOX_REFILL_INTERVAL_SECONDS=60`.
- [ ] `DEMO_SANDBOX_REFILL_WORKERS=2`.
- [ ] `DEMO_SANDBOX_LAST_SEED_MS=7000`.
- [ ] `DEMO_SANDBOX_LOGINS_PER_MINUTE=10`.

## Release steps

- [ ] Take a database backup before release.
- [ ] Deploy code.
- [ ] Run `php artisan migrate` (adds sandbox columns and index to `schools`).
- [ ] Use a cache store writable by the web SAPI (e.g. `CACHE_STORE=database`); demo tickets are short-lived cache entries and fail if file-cache directories are root-owned.
- [ ] Run `php artisan config:cache`.
- [ ] Run `php artisan route:cache` (new `demo.preparing` routes must be cached).
- [ ] Run `php artisan view:cache` if the deployment normally caches Blade views.
- [ ] Restart/reload PHP-FPM and queue workers.

## Queue and scheduler

- [ ] Ensure a worker consumes the dedicated queue (the bundled Docker worker consumes `default,demo-pool`):

  ```bash
  php artisan queue:work --queue=demo-pool --tries=3 --timeout=600
  ```

- [ ] Run at least two dedicated workers when the login rate is high.
- [ ] Supervisor (or the container restart policy) keeps workers alive.
- [ ] Scheduler is installed:

  ```bash
  * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
  ```

- [ ] Scheduler runs `demo:sandbox:refill-pool` every minute without overlap.
- [ ] Scheduler runs `demo:cleanup-sandboxes` as configured.

## Verification

- [ ] `php artisan demo:sandbox:pool-status --json` reports `refill_queue=demo-pool`, `last_seed_ms≈7000`, and `meets_login_rate=true`.
- [ ] `php artisan demo:sandbox:profile-seed --json` reports seed time under `10000ms` and zero side-effect events.
- [ ] Public demo login from an empty pool shows `/demo/preparing`, then redirects into a sandbox.
- [ ] Public demo login with a ready pool redirects immediately.
- [ ] Demo logout deletes only the active sandbox and redirects to `/demo/ended`.
- [ ] Real tenants and the master `demo` tenant are unchanged after demo activity.
- [ ] Sandboxes are absent from platform dashboards and tenant lists.
- [ ] Cleanup deletes expired sandboxes only.

## Rollback

- [ ] Set `DEMO_SANDBOX_POOL_ENABLED=false` to disable pre-provisioning.
- [ ] Stop `demo-pool` workers before rolling back code.
- [ ] Run `php artisan config:clear && php artisan route:clear` if restoring an older release.
- [ ] Keep wildcard DNS/TLS in place; it is harmless when sandbox routes are absent.
