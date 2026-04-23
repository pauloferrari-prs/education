# syntax=docker/dockerfile:1

FROM composer:2.8.6 AS vendor
WORKDIR /app
COPY app/composer.json ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-ansi \
    --no-progress \
    --optimize-autoloader
COPY app/ ./
RUN composer dump-autoload --optimize --no-dev

FROM php:8.3-fpm-bookworm AS runtime
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates \
    && docker-php-ext-install pdo_mysql opcache \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
RUN chown -R www-data:www-data /var/www/html
USER www-data
EXPOSE 9000
CMD ["php-fpm"]
