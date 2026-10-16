#!/bin/bash
# =============================================================================
# Kairo CORE — Production Deployment Script for Hetzner VPS
# =============================================================================
set -e

echo "==> Pulling latest changes from GitHub (main)..."
git pull origin main

echo "==> Rebuilding and restarting Docker containers..."
docker compose down
docker compose up -d --build

echo "==> Waiting for containers to initialize and run migrations/seeding..."
sleep 5

echo "==> Deployment complete successfully!"
echo "    You can now log in at https://kairo.co.zw/platform/login"
echo "    Email: twaynehlatywayo09@gmail.com"
echo "    Password: securepassword"
