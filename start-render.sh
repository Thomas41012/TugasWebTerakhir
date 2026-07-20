#!/bin/bash

# Modify Nginx configuration to listen on the port provided by Render
sed -i "s/listen 80;/listen ${PORT:-80};/g" /etc/nginx/sites-available/default

# Run database migrations
php artisan migrate --force

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
