export const environment = {
  production: true,
  apiUrl: 'https://abbrevio-backend-docker.onrender.com/api', // Render backend URL
  appName: 'Abbrevio Demo',
  version: '1.0.0-railway-render',
  demoMode: false, // Full functionality with backend
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