FROM php:8.3-fpm

WORKDIR /var/www/chat-tg

RUN docker-php-ext-configure pcntl --enable-pcntl \
  && docker-php-ext-install \
    pcntl

EXPOSE 8080

#CMD [ "php", "artisan", "reverb:start", "--host=0.0.0.0" ]