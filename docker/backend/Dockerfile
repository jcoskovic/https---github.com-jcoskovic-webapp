# Multi-stage build for Laravel backend
FROM php:8.2-fpm as builder

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Optional: Enable coverage driver (uncomment for CI/dev images)
RUN pecl install pcov \
    && docker-php-ext-enable pcov

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files from backend directory
COPY backend/composer.json backend/composer.lock ./

# Copy application from backend directory  
COPY backend/ /var/www/

# Copy php.ini configuration
COPY docker/backend/php.ini /usr/local/etc/php/conf.d/custom.ini

# Create required directories and set permissions
RUN mkdir -p bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache \
    && chmod -R 775 bootstrap/cache storage

# Create minimal .env file for package discovery
RUN echo "APP_ENV=production" > .env \
    && echo "APP_DEBUG=false" >> .env \
    && echo "APP_KEY=base64:$(openssl rand -base64 32)" >> .env \
    && echo "DB_CONNECTION=sqlite" >> .env \
    && echo "DB_DATABASE=/tmp/database.sqlite" >> .env

# Install PHP dependencies (include dev dependencies for development)
RUN composer install --optimize-autoloader --no-scripts

# Production stage
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Optional: Enable coverage driver (uncomment for CI/dev images)
RUN pecl install pcov \
    && docker-php-ext-enable pcov

# Set working directory
WORKDIR /var/www

# Copy application and vendor from builder stage
COPY --from=builder /var/www /var/www

# Copy php.ini configuration
COPY docker/backend/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod +x /var/www/artisan

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
