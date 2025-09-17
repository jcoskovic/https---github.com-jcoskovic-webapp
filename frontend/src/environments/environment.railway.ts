export const environment = {
  production: true,
  apiUrl: 'https://abbrevio-backend-docker.onrender.com/api', // Updated: Render backend URL
  appName: 'Abbrevio Demo',
  version: '1.0.0-railway-render-v2', // Force cache invalidation
  demoMode: false, // Full functionality with Render backend
  features: {
    auth: true,
    ml: true,
    pdf: true,
    comments: true,
    voting: true
  },
  api: {
    timeout: 30000, // 30 seconds timeout for production
    retries: 3
  }
};