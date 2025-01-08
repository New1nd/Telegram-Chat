FROM composer:latest

WORKDIR /var/www/chat

ENTRYPOINT ["composer", "--ignore-platform-reqs"]
