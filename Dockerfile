# Base image with PHP-FPM
FROM php:8.2-fpm

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Install necessary utilities if needed (optional, but good practice)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Create web root
RUN mkdir -p /var/www/html

# Copy application files (optional if using volumes, but helpful for a clean build)
# COPY . /var/www/html 

# Configure PHP-FPM to listen on port 9000 (accessible by the 'web' service)
RUN sed -i 's|listen = /run/php/php8.2-fpm.sock|listen = 9000|g' /usr/local/etc/php-fpm.d/www.conf

# Start PHP-FPM
CMD ["php-fpm"]
# Note: Since we are using volumes, the COPY . /var/www/html line in your original 
# Dockerfile is not strictly necessary for running, but is left in the YAML 
# for consistency if you prefer to build the files into the image.