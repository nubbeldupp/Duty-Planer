#!/bin/sh
set -e

# Run database migrations or initial setup
php artisan migrate --force

# Start PHP-FPM
php-fpm
