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

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Copy entire application
COPY . ./

# Create directories and set basic permissions
RUN mkdir -p bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache

# Create temporary .env for production build if not exists
RUN if [ ! -f .env ]; then \
    echo "APP_ENV=production" > .env && \
    echo "APP_DEBUG=false" >> .env && \
    echo "APP_KEY=" >> .env && \
    echo "DB_CONNECTION=mysql" >> .env && \
    echo "DB_HOST=mysql" >> .env && \
    echo "DB_PORT=3306" >> .env && \
    echo "DB_DATABASE=abbrevio" >> .env && \
    echo "DB_USERNAME=abbrevio_user" >> .env && \
    echo "DB_PASSWORD=abbrevio_password" >> .env && \
    echo "JWT_SECRET=W3a1rAclHlcQcX4rZqMDqK8yI46il886JmdCP8OWqMkw0DQQwNB7rS1I7vuxo3OS" >> .env; \
    fi

# Install PHP dependencies (include dev dependencies for development)
RUN composer install --optimize-autoloader --no-scripts

# Run post-install scripts
RUN composer run-script post-install-cmd || true

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

# Set working directory
WORKDIR /var/www

# Copy application and vendor from builder stage
COPY --from=builder /var/www /var/www

# Copy custom PHP configuration
COPY docker/backend/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set permissions and clear caches
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache \
    && chmod +x /var/www/artisan \
    && php artisan cache:clear || true \
    && php artisan config:clear || true \
    && php artisan view:clear || true \
    && php artisan route:clear || true

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
