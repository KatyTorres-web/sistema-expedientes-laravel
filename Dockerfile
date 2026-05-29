FROM php:8.2-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev

# Instalar extensiones PHP necesarias para Laravel + PostgreSQL
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    zip \
    xml \
    bcmath

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar proyecto
COPY . .

# Instalar dependencias Laravel
RUN composer install --no-interaction --prefer-dist --no-dev --ignore-platform-reqs

# Activar mod_rewrite
RUN a2enmod rewrite

# Evitar warning de ServerName
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 🔥 CONFIGURAR APACHE PARA LARAVEL (/public)
RUN echo '<VirtualHost *:80> \
    DocumentRoot /var/www/html/public \
    <Directory /var/www/html/public> \
        AllowOverride All \
        Require all granted \
    </Directory> \
    ErrorLog ${APACHE_LOG_DIR}/error.log \
    CustomLog ${APACHE_LOG_DIR}/access.log combined \
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Permisos Laravel
RUN chown -R www-data:www-data /var/www/html/storage
RUN chown -R www-data:www-data /var/www/html/bootstrap/cache

# Puerto
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]