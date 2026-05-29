#!/usr/bin/env bash
set -o errexit

# 1. Instalar dependencias
composer install --no-dev --prefer-dist --optimize-autoloader

# 2. Solo limpiar cache (seguro en build)
php artisan optimize:clear