FROM php:8.2-fpm

RUN docker-php-ext-install pdo pdo_mysql

# NGINX
FROM nginx:stable
COPY ./nginx.conf /etc/nginx/conf.d/default.conf
COPY . /var/www/html