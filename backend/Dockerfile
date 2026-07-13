# syntax=docker/dockerfile:1

FROM composer:2 AS builder

WORKDIR /app

COPY composer.json composer.lock* ./

RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

COPY . .

RUN composer dump-autoload --optimize --no-dev


FROM php:8.3-fpm-alpine AS runtime

RUN apk add --no-cache \
        libzip \
        libpng \
        libjpeg-turbo \
        freetype \
        file \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        zip \
        gd \
    && apk del .build-deps \
    && docker-php-source delete

WORKDIR /var/www/html

COPY --from=builder /app /var/www/html

RUN mkdir -p storage/uploads/excel storage/uploads/images storage/logs \
    && chown -R www-data:www-data storage

USER www-data

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s \
    CMD php -r "exit(0);" || exit 1

CMD ["php-fpm"]