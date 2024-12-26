#!/bin/sh
set -e

# Wait for the database to be ready
while ! nc -z database 3306; do
    echo "Waiting for MySQL database to be ready..."
    sleep 3
done

# Run any necessary database migrations or setup scripts
# Uncomment and modify as needed for your specific project
# php migrate.php
# php setup.php

# Start PHP-FPM
php-fpm
