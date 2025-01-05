FROM composer:latest

WORKDIR /var/www/chat-tg

ENTRYPOINT ["composer", "--ignore-platform-reqs"]
