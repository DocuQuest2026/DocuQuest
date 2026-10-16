#!/bin/sh

PORT="${PORT:-10000}"
sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan package:discover --ansi

echo "==> Running migrations"
if ! php artisan migrate --force -vvv 2>&1; then
    echo "==> MIGRATION FAILED (continuing so the error stays visible in the logs)"
fi

php artisan config:cache 2>&1 || echo "==> config:cache failed"
php artisan route:cache 2>&1 || echo "==> route:cache failed"
php artisan view:cache 2>&1 || echo "==> view:cache failed"

chown -R www-data:www-data storage bootstrap/cache

echo "==> Starting Apache on port ${PORT}"
exec apache2-foreground
