# syntax=docker/dockerfile:1

# ---- PHP dependencies -------------------------------------------------------
FROM composer:2.8 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
# The composer image doesn't have ext-gd, which phpspreadsheet declares as a requirement —
# skip the platform check here; the actual gd extension is installed in the runtime stage below.
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress --ignore-platform-req=ext-gd

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# ---- Runtime ----------------------------------------------------------------
FROM php:8.3-fpm-alpine AS app

RUN apk add --no-cache nginx supervisor libpng libjpeg-turbo freetype libzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath opcache gd zip \
    && apk del .build-deps

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/nginx.conf /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache /var/lib/nginx

ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["entrypoint"]
