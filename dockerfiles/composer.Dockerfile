FROM composer:latest

WORKDIR /var/www/pro-traffic-group

ENTRYPOINT ["composer", "--ignore-platform-reqs"]
