FROM php:8.1 AS builder
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y libcurl4-openssl-dev unzip
RUN docker-php-ext-install curl

COPY . /app
WORKDIR /app

RUN composer install --no-dev

FROM php:8.1-apache
COPY --from=builder /app /var/www/html/
RUN apt-get update && apt-get install -y libcurl4-openssl-dev
RUN docker-php-ext-install curl
