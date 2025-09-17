# 🚂 Railway Deployment Guide for Abbrevio

Kompletna instrukcija za deployment Abbrevio aplikacije na Railway.

## 📋 Prerequisites

1. **Railway account**: https://railway.app (registracija s GitHub-om)
2. **GitHub repository**: Push your code to GitHub
3. **API keys**: 
   - GROQ API key (https://console.groq.com/)
   - Email service credentials (optional)

## 🚀 Deployment Steps

### 1️⃣ Create Railway Project

1. Go to Railway dashboard: https://railway.app/new  
2. Click **"New Project"**
3. Select **"Empty Project"**
4. Name your project (e.g., "abbrevio-demo")

### 2️⃣ Deploy Services

Deploy each service separately:

#### A) Backend Service (Laravel)
1. In project dashboard: **"New Service"** → **"GitHub Repo"**
2. Select your repository
3. **Set Root Directory**: `backend`
4. Railway will auto-detect PHP/Laravel and use nixpacks

#### B) Frontend Service (Angular)  
1. **"New Service"** → **"GitHub Repo"**
2. Select same repository  
3. **Set Root Directory**: `frontend`
4. Railway will auto-detect Node.js/Angular

#### C) ML Service (Python)
1. **"New Service"** → **"GitHub Repo"**
2. Select same repository
3. **Set Root Directory**: `ml-service`  
4. Railway will auto-detect Python/Flask

#### D) MySQL Database
1. **"New Service"** → **"Database"** → **"MySQL"**
2. Railway will create managed MySQL instance
3. Connection variables will be auto-generated

### 3️⃣ Configure Environment Variables

#### Backend Service Variables:
```env
# Required
GROQ_API_KEY=your-groq-api-key-here
APP_KEY=base64:your-generated-32-char-key
JWT_SECRET=your-generated-64-char-secret

# Optional (for email functionality)
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-key
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

#### Frontend Service Variables:
```env
NODE_ENV=production
```

#### ML Service Variables:
```env
GROQ_API_KEY=your-groq-api-key-here
FLASK_ENV=production
```

### 4️⃣ Generate Required Keys

Use these commands to generate secure keys:

```bash
# APP_KEY (Laravel)
openssl rand -base64 32

# JWT_SECRET  
openssl rand -base64 64
```

### 5️⃣ Run Database Migrations

After backend deployment:
1. Go to backend service in Railway
2. Open **"Deployments"** tab  
3. Click latest deployment → **"View Logs"**
4. Once deployed, use Railway CLI or dashboard to run:
   ```bash
   php artisan migrate --force
   php artisan db:seed --force --class=ProductionSeeder
   ```

## ✅ Final Result

After successful deployment you'll have:

- **Frontend**: `https://your-frontend-xyz.up.railway.app`
- **Backend API**: `https://your-backend-xyz.up.railway.app`  
- **ML Service**: `https://your-ml-xyz.up.railway.app`
- **MySQL Database**: Managed by Railway

## 👥 Demo Users

After running the seeder:
- **Admin**: `admin@abbrevio.demo` / `admin123`
- **User**: `user@abbrevio.demo` / `user123`
- **Moderator**: `moderator@abbrevio.demo` / `moderator123`

## 💰 Cost Estimation

- **Hobby Plan**: $5/month covers all services
- **Free Trial**: Available for testing

## 🔧 Troubleshooting

**Common Issues:**

1. **Build Failures**: Check logs in Railway dashboard
2. **Database Connection**: Verify MySQL service is running  
3. **CORS Issues**: Add frontend URL to backend CORS settings
4. **Environment Variables**: Double-check all required variables are set

**Support**: Railway documentation at https://docs.railway.app

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