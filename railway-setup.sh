#!/bin/bash

# Railway Database Setup Script
# Run this after connecting MySQL service to Laravel backend in Railway

echo "Running Laravel database migrations..."

# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Run database migrations
php artisan migrate --force

# Create database indexes (if needed)
php artisan db:seed --class=DatabaseSeeder --force

echo "Database setup complete!"
echo "API should now be available at: /api/abbreviations"