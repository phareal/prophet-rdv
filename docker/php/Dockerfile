FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
      icu-dev libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev \
      $PHPIZE_DEPS \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" mysqli intl gd zip opcache \
 && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /srv
