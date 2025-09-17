#!/bin/bash

# Production Database Setup Script for Railway
echo "🚀 Starting Abbrevio Production Database Setup..."

# Wait for database to be ready
echo "⏳ Waiting for database connection..."
until php artisan db:monitor; do
  echo "Database not ready, waiting 5 seconds..."
  sleep 5
done

echo "✅ Database connected successfully!"

# Run migrations
echo "📊 Running database migrations..."
php artisan migrate --force

# Seed demo data for production
echo "🌱 Seeding demo data..."
php artisan db:seed --force --class=ProductionSeeder

# Generate application key if not set
echo "🔑 Checking application key..."
if [ -z "$APP_KEY" ]; then
  echo "Generating application key..."
  php artisan key:generate --force
fi

# Generate JWT secret if not set
echo "🔐 Checking JWT secret..."
php artisan jwt:secret --force

# Clear and cache config for production
echo "🗂️  Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate Swagger documentation
echo "📚 Generating API documentation..."
php artisan l5-swagger:generate

echo "✅ Production database setup completed!"
echo "🎉 Abbrevio is ready to serve!"