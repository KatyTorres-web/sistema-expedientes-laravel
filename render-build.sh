#!/usr/bin/env bash
set -o errexit

echo "🚀 Instalando dependencias..."
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan optimize:clear
rm -f bootstrap/cache/config.php

echo "📁 Creando carpetas necesarias..."
mkdir -p storage/framework/cache \
storage/framework/sessions \
storage/framework/views \
bootstrap/cache

echo "🔐 Ajustando permisos..."
chmod -R 775 storage bootstrap/cache


npm install
npm run build

php artisan config:clear
php artisan cache:clear

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache