#!/bin/bash

# Railway Deployment Guide for Abbrevio
echo "🚂 Railway Deployment Guide for Abbrevio"
echo "======================================="

echo "📋 Prerequisites:"
echo "1. Install Railway CLI: npm install -g @railway/cli"
echo "2. Create Railway account: https://railway.app"
echo "3. Push code to GitHub repository"
echo ""

echo "🚀 Deployment Steps:"
echo ""

echo "1️⃣  Login to Railway:"
echo "   railway login"
echo ""

echo "2️⃣  Create new project:"
echo "   railway new"
echo "   # Select 'Deploy from GitHub repo' and choose your repository"
echo ""

echo "3️⃣  Add MySQL database:"
echo "   railway add --database mysql"
echo ""

echo "4️⃣  Set environment variables in Railway dashboard:"
echo "   - Copy values from .env.production.template"
echo "   - Update DATABASE_URL with Railway MySQL connection"
echo "   - Add GROQ_API_KEY"
echo "   - Set MAIL_* variables for email functionality"
echo ""

echo "5️⃣  Deploy services:"
echo "   # Backend"
echo "   railway up --service backend"
echo ""
echo "   # Frontend"  
echo "   railway up --service frontend"
echo ""
echo "   # ML Service"
echo "   railway up --service ml-service"
echo ""

echo "6️⃣  Run database migrations:"
echo "   railway run --service backend bash ./scripts/production-setup.sh"
echo ""

echo "✅ Your application will be available at:"
echo "   Frontend: https://your-app.up.railway.app"
echo "   Backend API: https://your-backend.up.railway.app"
echo "   ML Service: https://your-ml.up.railway.app"
echo ""

echo "📚 Demo Users (after seeding):"
echo "   Admin: admin@abbrevio.demo / admin123"
echo "   User: user@abbrevio.demo / user123"  
echo "   Moderator: moderator@abbrevio.demo / moderator123"
echo ""

echo "🔧 Environment Variables to set in Railway:"
echo "   APP_KEY: (generate with: php artisan key:generate --show)"
echo "   JWT_SECRET: (generate with: php artisan jwt:secret --show)"
echo "   DATABASE_URL: (provided by Railway MySQL service)"
echo "   GROQ_API_KEY: (your GROQ API key for ML features)"
echo "   MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD: (email service config)"
echo ""

echo "🏗️  Alternative: One-click deploy:"
echo "   1. Fork the repository on GitHub"
echo "   2. Connect Railway to your GitHub account"
echo "   3. Select 'Deploy from GitHub' in Railway"
echo "   4. Choose your forked repository"
echo "   5. Railway will auto-detect and deploy all services"
echo ""

echo "Need help? Check Railway docs: https://docs.railway.app"