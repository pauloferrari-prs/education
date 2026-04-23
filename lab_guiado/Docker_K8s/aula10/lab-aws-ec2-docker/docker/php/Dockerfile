FROM composer:2.8 AS composer_bin

FROM php:8.3-cli-bookworm-slim AS build
WORKDIR /app

COPY --from=composer_bin /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY app/composer.json app/composer.lock* ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-ansi \
    --no-progress \
    --optimize-autoloader

COPY app/ ./

FROM php:8.3-fpm-bookworm-slim AS runtime
WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates libzip-dev \
    && docker-php-ext-install pdo_mysql opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=build /app /var/www/html

EXPOSE 9000
CMD ["php-fpm"]