#!/bin/bash
set -euo pipefail

log() { echo -e "[SETUP] $1"; }

log "🚀 Setting up Abbrevio in GitHub Codespaces..."

# Wait for services to be ready
log "⏳ Waiting for services to start..."
sleep 10

# Check if services are running
log "📊 Checking service status..."
docker compose ps || docker-compose ps || true

# Install frontend dependencies and build
cd frontend
if [ -f package-lock.json ]; then
    log "📦 Installing frontend dependencies (npm ci)..."
    npm ci --no-audit --no-fund
else
    log "📦 Installing frontend dependencies (npm install)..."
    npm install --no-audit --no-fund
fi
if [ ! -d dist/frontend/browser ]; then
    log "🔨 Building frontend (production)..."
    npm run build:prod
else
    log "✅ Frontend build already present, skipping rebuild"
fi
cd ..

# Setup backend
log "⚙️ Setting up Laravel backend..."
cd backend

# Generate app key if not exists
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    log "🔑 Generating Laravel app key..."
    php artisan key:generate
fi

# Generate JWT secret if not exists  
if ! grep -q "^JWT_SECRET=" .env 2>/dev/null; then
    log "🔒 Generating JWT secret..."
    php artisan jwt:secret --force
fi

# Run migrations and seeders
if php artisan migrate:status > /dev/null 2>&1; then
    log "📊 Running database migrations (fresh seed)..."
    php artisan migrate:fresh --seed --force
else
    log "⚠️  Skipping migrations (artisan not ready?)"
fi

# Clear caches
log "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

cd ..

# Setup ML service
log "🤖 Setting up ML service..."
cd ml-service
pip install --no-cache-dir -r requirements.txt
cd ..

echo ""
log "✅ Abbrevio setup complete!"
echo ""
log "🌐 Your application URLs:"
echo "   Frontend: https://$CODESPACE_NAME-4200.app.github.dev"
echo "   Backend API: https://$CODESPACE_NAME-8000.app.github.dev/api"
echo "   ML Service: https://$CODESPACE_NAME-5001.app.github.dev"
echo "   phpMyAdmin: https://$CODESPACE_NAME-8080.app.github.dev"
echo "   Mailpit: https://$CODESPACE_NAME-8025.app.github.dev"
echo ""
log "🎯 Next steps:"
echo "1. Update frontend environment for Codespaces URLs"
echo "2. Test the application"
echo "3. Share the public URLs!"
echo ""
