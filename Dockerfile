FROM composer:latest as composer
FROM php:8.1-fpm-alpine

COPY . /var/www/app
WORKDIR /var/www/app

RUN echo "post_max_size = 32M" > /usr/local/etc/php/conf.d/post_max_size.ini
RUN echo "upload_max_filesize = 24M" > /usr/local/etc/php/conf.d/upload_max_filesize.ini

ENV UPLOADCARE_PUBLIC_KEY=demopublickey
ENV UPLOADCARE_PRIVATE_KEY=demoprivatekey

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

#CMD php -S 0.0.0.0:8000 public/index.php
