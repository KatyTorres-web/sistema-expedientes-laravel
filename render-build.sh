#!/usr/bin/env bash
set -o errexit

# 1. Instalar dependencias
composer install --no-dev --prefer-dist --optimize-autoloader
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 2. Solo limpiar cache (seguro en build)
php artisan optimize:clear