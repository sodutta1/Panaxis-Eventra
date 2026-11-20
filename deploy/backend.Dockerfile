# Panaxis Eventra backend (Laravel 11 / PHP 8.5 / php-fpm)
FROM composer:2 AS vendor
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

FROM php:8.5-fpm
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libzip-dev libonig-dev libxml2-dev libicu-dev unzip \
    && docker-php-ext-install pdo_mysql mbstring gd zip intl opcache \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY backend/ ./
COPY --from=vendor /app/vendor ./vendor
RUN cp .env.example .env \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache
USER www-data
EXPOSE 9000
CMD ["php-fpm"]
