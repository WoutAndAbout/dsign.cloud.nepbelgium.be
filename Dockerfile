# Base image with PHP-FPM
FROM php:8.2-fpm

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Install Nginx + Supervisor
RUN apt-get update && apt-get install -y nginx supervisor && rm -rf /var/lib/apt/lists/*

# Create web root
RUN mkdir -p /var/www/html

# Copy your application
COPY . /var/www/html

# Copy Nginx configuration
COPY ./nginx.conf /etc/nginx/conf.d/default.conf

# Configure PHP-FPM to listen on a socket
RUN sed -i 's|listen = /run/php/php8.2-fpm.sock|listen = 9000|g' /usr/local/etc/php-fpm.d/www.conf

# Supervisor configuration
RUN mkdir -p /etc/supervisor/conf.d
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose HTTP port
EXPOSE 80

# Start supervisor (which launches PHP-FPM + Nginx)
CMD ["/usr/bin/supervisord", "-n"]
