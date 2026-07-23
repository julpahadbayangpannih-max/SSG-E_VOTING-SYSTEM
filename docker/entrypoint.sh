#!/bin/sh
set -e

echo "==> Caching config/routes/views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Running migrations"
php artisan migrate --force

echo "==> Linking public storage (needed for uploaded branding logos)"
php artisan storage:link || true

echo "==> Seeding default admin (no-op if already seeded / env vars unset)"
php artisan db:seed --class=AdminSeeder --force

echo "==> Starting nginx + php-fpm"
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
