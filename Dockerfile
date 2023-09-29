FROM php:8.2-fpm

RUN apt-get update
RUN apt-get install -y libzip-dev libpng-dev unzip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /app
WORKDIR /app

CMD ["php-fpm"]