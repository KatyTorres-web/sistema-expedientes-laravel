#!/usr/bin/env bash
set -o errexit

echo "🚀 Instalando dependencias..."
composer install --no-dev --prefer-dist --optimize-autoloader

echo "📁 Creando carpetas necesarias..."
mkdir -p storage/framework/cache \
storage/framework/sessions \
storage/framework/views \
bootstrap/cache

echo "🔐 Ajustando permisos..."
chmod -R 775 storage bootstrap/cache


npm install
npm run build

echo "🧹 Clear caches"
php artisan config:clear
php artisan cache:clear || true
php artisan route:clear
php artisan view:clear

echo "📦 Run migrations"
php artisan migrate --force --no-interaction

echo "⚙️ Re-cache app"
php artisan config:cache
php artisan route:cache
php artisan view:cache