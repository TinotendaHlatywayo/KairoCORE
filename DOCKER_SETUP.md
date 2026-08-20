# Docker Setup Guide (Option 2 — Multi-Container)

This project runs as three Docker containers that work identically on Windows,
macOS, and Linux:

| Service    | Container       | Image / Build        | Role                                   | Ports          |
|------------|-----------------|----------------------|----------------------------------------|----------------|
| `app`      | `laravel_app`   | local `Dockerfile`   | PHP-FPM 8.3 + Composer                 | internal 9000  |
| `webserver`| `laravel_webserver` | `nginx:alpine`   | Serves the public app, proxies to FPM  | **8080 → 80**  |
| `db`       | `laravel_db`    | `mysql:8.0`          | MySQL database (persistent `dbdata` volume) | internal 3306 |

All three connect through a shared bridge network named `laravel_network`.

---

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) (or Docker Desktop) installed
  and running.
- The `docker` (and optionally `docker compose`) command available in your
  terminal.

Verify Docker is ready:

```bash
docker --version
docker compose version
```

---

## 1. Configure the environment file

Copy the environment template and open `.env`:

```bash
cp .env.example .env
```

> If a `.env` already exists (e.g. copied earlier from an XAMPP / native
> setup), edit it — `docker-compose.yml` reads `DB_DATABASE`, `DB_USERNAME`,
> `DB_PASSWORD` from the same `.env` for both the MySQL container and the app,
> so the two must agree.

Set the values for the Docker stack so Laravel connects to the `db` container:

```dotenv
APP_NAME="School Management System"
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=schoolcore
DB_USERNAME=schooluser
DB_PASSWORD=secret
```

> - `DB_HOST` **must** be `db` (the MySQL service name on the `laravel_network`
>   network) — never `127.0.0.1`, which would point back into the app
>   container itself.
> - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` are injected into the MySQL
>   container via `docker-compose.yml` (with the same fallbacks), so keep them
>   in sync. If you leave them blank/unset, the fallbacks
>   `schoolcore` / `schooluser` / `secret` are used.
> - The `app` service also passes `DB_CONNECTION=mysql` and `DB_HOST=db`
>   directly, so the container always targets the database service.

> ### ⚠️ `MYSQL_USER` and the "root" crash
> The official MySQL image aborts startup if `MYSQL_USER=root` (it only creates
> a **regular, non-root** user). `docker-compose.yml` therefore wires
> `MYSQL_USER` to its **own** variable and defaults it to `schooluser`, so a
> leftover `DB_USERNAME=root` in `.env` (e.g. from an old XAMPP setup) can
> never crash MySQL.
>
> - To connect as the regular user, use `DB_USERNAME=schooluser`.
> - To connect as `root` instead, set `DB_USERNAME=root` in `.env` **without**
>   adding `MYSQL_USER` to `docker-compose.yml` — the root account always
>   exists and uses `MYSQL_ROOT_PASSWORD`.

---

## 2. Build and start the containers

```bash
docker compose up -d --build
```

This builds the `app` image, pulls `nginx:alpine` and `mysql:8.0`, starts all
three services and creates the `dbdata` volume + `laravel_network`.

Check that everything is up:

```bash
docker compose ps
```

Wait until the database reports healthy:

```bash
docker compose ps db
```

---

## 3. Install Composer dependencies

```bash
docker compose exec app composer install
```

> The code is bind-mounted from your machine, so if a `vendor/` directory
> already exists (e.g. installed under a different PHP version like XAMPP's
> 8.4), run `composer update` once instead so packages and the optimized
> autoloader are rebuilt for the container's PHP 8.3:

```bash
docker compose exec app composer update
```

> Afterwards, regenerate the optimized autoloader (this also re-discovers
> package providers):

```bash
docker compose exec app composer dump-autoload
```

---

## 4. Generate the application key

```bash
docker compose exec app php artisan key:generate
```

> This writes `APP_KEY` into `.env`. If you already have a key, skip this step.

---

## 5. Create the database schema

```bash
docker compose exec app php artisan migrate
```

Optionally seed the database:

```bash
docker compose exec app php artisan db:seed
```

> Fresh migrations on a clean database may need a couple of minutes (the suite
> is ~120 migrations). Two fixes shipped for strict-mode MySQL 8: the
> `report_dashboard_widgets.position` JSON default now uses MySQL expression
> syntax (`DEFAULT (...)`), and `add_title_to_application_documents_table`
> guards against a duplicate column — so a `migrate:fresh` run completes
> cleanly. If a run is interrupted, simply re-run `migrate` (already-applied
> tables are skipped).

---

## 6. Open the application

The site is served on host port **8080**:

```
http://localhost:8080
```

> - Make sure `APP_URL` in `.env` matches this URL (otherwise generated links
>   / redirects may point elsewhere).
> - If port 8080 is already in use on your machine, change the `webserver`
>   mapping in `docker-compose.yml` (e.g. `"8081:80"`), update `APP_URL`
>   accordingly, and run `docker compose up -d` again.

### Domain-based routing (`lvh.me`)

This app routes by hostname: the platform portal lives on `lvh.me/platform`
and each school tenant on `<subdomain>.lvh.me`. `lvh.me` is a free DNS alias
that resolves to `127.0.0.1`, so plain `localhost:8080` returns a 404 for
routes that expect the `lvh.me` host. Access it via:

```
http://lvh.me:8080/platform/login        # Platform / Founder portal
http://<school-subdomain>.lvh.me:8080/dashboard
```

The fresh database is seeded with a single founder account:

- URL: `http://lvh.me:8080/platform/login`
- Email: `founder@schoolcore.test`
- Password: `securepassword`

> To use a different domain, set `APP_URL` and the `{tenant}`/host routing
> config in `routes/web.php` and the tenant model accordingly.

---

## 7. Recovering from a failed MySQL initialization

If MySQL crashed during a previous run (e.g. the `MYSQL_USER=root` bug), its
data volume holds a partial/failed initialization and **must be wiped** so the
startup scripts can run cleanly:

```bash
# 1. Tear down containers and delete the (corrupted) database volume
docker-compose down -v

# 2. Spin the containers back up
docker-compose up -d

# 3. Wait 10-15 seconds for MySQL to initialize, then check status
docker-compose ps
```

Once `laravel_db` shows `Up` (not `Restarting`), run the Laravel setup:

```bash
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
```

---

## 8. Cache / permissions notes

### PHP-FPM runs as your host user

The `app` service runs PHP-FPM as the **host user** so the bind-mounted
`storage/` and `bootstrap/cache/` (which are owned by your user, not by
`www-data`) stay writable:

```yaml
# docker-compose.yml (app service)
user: "${USER_ID:-1000}:${GROUP_ID:-1000}"
```

- On **Linux**, set `USER_ID` / `GROUP_ID` in your shell if your UID is not
  1000: `export USER_ID=$(id -u) GROUP_ID=$(id -g)` before `docker compose up`.
- On **Docker Desktop** (Windows/macOS) the UID/GID are virtualized, so the
  `1000` defaults are correct and no export is needed.

> If you still hit a "Permission denied" writing to `storage/` or
> `bootstrap/cache/` (e.g. after running artisan as a different user), fix
> ownership from inside the container:

```bash
docker compose exec -u root app chown -R 1000:1000 storage bootstrap/cache
```

Clear any stale caches:

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan route:clear
```

---

## Everyday commands

```bash
# View live logs of all services
docker compose logs -f

# Run an artisan command inside the app container
docker compose exec app php artisan about

# Open an interactive shell inside the app container
docker compose exec app bash

# Restart the stack
docker compose restart

# Stop the containers (data is kept in the dbdata volume)
docker compose down

# Stop and DELETE the database volume as well (destructive)
docker compose down -v
```

---

## File layout

```
Dockerfile                          # PHP-FPM 8.3 + extensions + Composer
docker-compose.yml                  # app / webserver / db + volume + network
docker/nginx/conf.d/app.conf        # Nginx vhost (proxy .php to app:9000)
```