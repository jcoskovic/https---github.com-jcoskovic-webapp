# Use official PHP image with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application code
COPY . .

# Create necessary directories and set proper permissions
RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Enable Apache mod_rewrite for Laravel
RUN a2enmod rewrite

# Generate application key and cache config (without migrations yet)
RUN php artisan config:cache && \
    php artisan route:cache

# Expose port 80 (Apache default)
EXPOSE 80

# Create startup script with migrations
RUN echo '#!/bin/bash' > /start.sh && \
    echo 'cd /var/www/html' >> /start.sh && \
    echo 'php artisan migrate --force' >> /start.sh && \
    echo 'apache2-foreground' >> /start.sh && \
    chmod +x /start.sh

# Start with migrations then Apache
CMD ["/start.sh"]