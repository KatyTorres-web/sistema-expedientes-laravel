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

echo "🧹 Limpiando caches viejos..."
php artisan optimize:clear

npm install
npm run build

echo "⚙️ Generando cache de producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
