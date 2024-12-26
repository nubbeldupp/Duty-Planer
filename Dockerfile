# Use official PHP image with MySQL support
FROM php:8.1-fpm-alpine

# Install system dependencies and MySQL extension
RUN apk add --no-cache \
    mysql-client \
    libzip-dev \
    unzip \
    netcat-openbsd \
    && docker-php-ext-install pdo pdo_mysql mysqli zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Setup PHP configuration
COPY docker/php.ini /usr/local/etc/php/php.ini

# Setup nginx configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Setup entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose port
EXPOSE 80

# Set entrypoint
ENTRYPOINT ["/entrypoint.sh"]
