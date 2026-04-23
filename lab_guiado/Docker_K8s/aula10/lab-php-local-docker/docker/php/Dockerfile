# syntax=docker/dockerfile:1

FROM php:8.3-cli-bookworm AS build
WORKDIR /build
COPY app/ /build/
RUN php -l /build/public/index.php     && php -l /build/src/bootstrap.php     && php -l /build/src/Database.php

FROM php:8.3-fpm-bookworm AS runtime
WORKDIR /var/www/html
RUN apt-get update     && apt-get install -y --no-install-recommends curl ca-certificates     && docker-php-ext-install pdo_mysql opcache     && rm -rf /var/lib/apt/lists/*
COPY --from=build /build /var/www/html
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
RUN chown -R www-data:www-data /var/www/html
USER www-data
EXPOSE 9000
CMD ["php-fpm"]
