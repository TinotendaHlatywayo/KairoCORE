#!/bin/bash

PROJECT_DIR="/opt/lampp/htdocs/SchoolManagementSystem/schoolcore"

# Change directory (use return so sourcing doesn't close active terminal on failure)
cd "$PROJECT_DIR" || return 1

echo "🔐 Enter sudo password once for all commands:"
sudo -v || return 1

echo "🛑 Killing anything on ports 80/443..."
sudo fuser -k 80/tcp 443/tcp 2>/dev/null
sudo systemctl stop apache2 2>/dev/null
sudo systemctl disable apache2 2>/dev/null

echo "🧹 Cleaning stale XAMPP locks..."
sudo rm -f /opt/lampp/var/mysql/*.pid /opt/lampp/logs/httpd.pid 2>/dev/null

echo "🚀 Starting XAMPP..."
sudo /opt/lampp/lampp start

echo "🌐 Starting Laravel Artisan (background)..."
pkill -f "artisan serve" 2>/dev/null
php artisan serve --host=0.0.0.0 --port=8000 &

echo ""
echo "=================================================="
echo "✅ Kairo CORE running!"
echo "=================================================="
echo "• Marketing Site:       https://lvh.me"
echo "• Admin Panel:          https://lvh.me/platform"
echo "• Staff Workspace:      https://tinwayacademy.lvh.me/workspace"
echo "• Student Portal:       https://chiwariraprimary.lvh.me/student"
echo "• Artisan Server:       http://localhost:8000"
echo "=================================================="
