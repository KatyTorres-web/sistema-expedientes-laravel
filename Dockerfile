FROM php:8.2-apache

# =========================
# DEPENDENCIAS DEL SISTEMA
# =========================
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    libssl-dev

# =========================
# EXTENSIONES PHP (CORRECTAS)
# =========================
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    zip \
    xml \
    intl \
    bcmath \
    curl

# ⚠️ tokenizer NO SE INSTALA (ya viene incluido en PHP)

# =========================
# COMPOSER
# =========================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# =========================
# DEPENDENCIAS LARAVEL
# =========================
RUN composer install --no-interaction --prefer-dist --no-dev

# =========================
# APACHE CONFIG
# =========================
RUN a2enmod rewrite

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]