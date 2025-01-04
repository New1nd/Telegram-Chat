FROM php:8.3-fpm

WORKDIR /var/www/chat

#RUN apt update \
#    && apt install postgresql-dev \
#    && docker-php-ext-install pgsql pdo_pgsql pdo

RUN apt-get update \
  && apt-get install -y postgresql build-essential zlib1g-dev default-mysql-client curl gnupg procps vim git unzip libzip-dev libpq-dev \
  && docker-php-ext-install zip pdo_mysql pdo_pgsql pgsql pdo

# Установите Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
