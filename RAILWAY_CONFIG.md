# Railway Environment Variables Configuration

## Database Connection Issue Fix

The Laravel backend is failing to connect to MySQL because it's using the wrong credentials. Railway automatically provides MySQL environment variables when you connect the MySQL service to your Laravel backend service.

## Required Railway Environment Variables

In your Railway Laravel backend service, you need to configure these environment variables:

### Database Configuration
When you connect the MySQL service to your backend service in Railway, these variables should be automatically provided:
- `MYSQLHOST` - The MySQL host (automatically set by Railway)
- `MYSQLPORT` - The MySQL port (automatically set by Railway)
- `MYSQLDATABASE` - The database name (automatically set by Railway)
- `MYSQLUSER` - The MySQL user (automatically set by Railway)
- `MYSQLPASSWORD` - The MySQL password (automatically set by Railway)

### Additional Required Variables
You also need to manually set these in your Railway backend service:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:IOw58afzq10QA1gJBF9xu0J7Q9Fjq6Q4QaavRKaH9IA=
```

### Email Configuration (for user registration)
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-app-specific-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-gmail@gmail.com
```

## Steps to Fix

1. **Connect MySQL Service to Backend:**
   - In Railway dashboard, go to your Laravel backend service
   - Click "Connect" and select your MySQL service
   - This will automatically provide the MYSQL* environment variables

2. **Add Manual Environment Variables:**
   - In your backend service settings, add the APP_* and MAIL_* variables listed above

3. **Redeploy:**
   - The service should automatically redeploy after adding environment variables

4. **Run Migrations:**
   - Once connected, the backend will need to run database migrations to create the required tables

## Current Issue
The error shows Laravel is trying to use `abbrevio_user` instead of the Railway MySQL credentials. This indicates the MySQL service is not properly connected to the backend service in Railway.

## Next Steps
After configuring these environment variables, the API should work at:
https://profound-creation-production-df1a.up.railway.app/api/abbreviations