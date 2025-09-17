export const environment = {
  production: true,
  apiUrl: 'https://abbrevio-backend.up.railway.app/api', // Railway backend URL
  appName: 'Abbrevio Demo',
  version: '1.0.0-railway',
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