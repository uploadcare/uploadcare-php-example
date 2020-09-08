FROM php:7.4.10-fpm-alpine

COPY . /var/www/app
WORKDIR /var/www/app

RUN echo "post_max_size = 32M" > /usr/local/etc/php/conf.d/post_max_size.ini
RUN echo "upload_max_filesize = 24M" > /usr/local/etc/php/conf.d/upload_max_filesize.ini

CMD php -S 0.0.0.0:8000 public/index.php
