#!/bin/bash
# =============================================================================
# Kairo CORE — Production Deployment Script for Hetzner VPS
# =============================================================================
set -e

echo "==> Pulling latest changes from GitHub (main)..."
git pull origin main

echo "==> Rebuilding and restarting Docker containers..."
# `up -d --build` recreates the containers whose image changed and leaves the
# database and redis alone. `docker compose down` would take the whole stack
# offline for the length of the rebuild.
docker compose up -d --build

echo "==> Waiting for containers to initialize and run migrations/seeding..."
sleep 5

# The three app containers share one storage volume. A file written by a root
# process (a `docker compose exec php artisan ...` run as root) would leave the
# Apache workers unable to touch it, and Blade touches a compiled view whenever
# its source is newer — a 500 on every page that uses that view. Hand the
# volume back to www-data.
echo "==> Repairing storage ownership..."
docker compose exec -T kairocore-web sh -c \
    'chown -R www-data:www-data storage bootstrap/cache'

echo "==> Checking the site is up..."
docker compose exec -T kairocore-web curl -fsS http://127.0.0.1/up >/dev/null

echo "==> Deployment complete successfully!"
echo "    You can now log in at https://kairo.co.zw/platform/login"
echo "    Email: twaynehlatywayo09@gmail.com"
echo "    Password: securepassword"
echo
echo "    NOTE: run artisan inside the container as the web user, or it writes"
echo "    root-owned files into the shared volume:"
echo "      docker compose exec -T --user www-data kairocore-web php artisan ..."
