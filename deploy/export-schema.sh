#!/bin/bash
# Export database schema + seed data for Railway deployment
# Usage: bash deploy/export-db.sh > deploy/schoolcore.sql
set -e

DB="schoolcore"
MYSQL="/opt/lampp/bin/mysql -u root"

echo "-- Kairo CORE Database Export"
echo "-- $(date)"
echo "--"

# Schema only (no data)
$MYSQL --skip-column-names -e "
SELECT CONCAT('DROP TABLE IF EXISTS `', table_name, '`;')
FROM information_schema.tables
WHERE table_schema = '$DB' AND table_type = 'BASE TABLE'
ORDER BY table_name;
" 2>/dev/null

$MYSQL --skip-column-names -e "
SELECT TABLE_NAME
FROM information_schema.tables
WHERE table_schema = '$DB' AND table_type = 'BASE TABLE'
ORDER BY table_name;
" 2>/dev/null | while read table; do
    $MYSQL --skip-column-names -e "SHOW CREATE TABLE \`$table\`;" 2>/dev/null | awk '{print $2, $3, ";"}'
done

echo ""
echo "-- Schema complete. Run this SQL on Railway's MySQL, then run:"
echo "--   php artisan migrate --force"
echo "--   php artisan permission:cache-reset"
