// Railway Environment Configuration - Updated for Render Backend
export const environment = {
  production: true,
  apiUrl: 'https://abbrevio-backend-docker.onrender.com/api', // RENDER BACKEND URL - FORCE UPDATE
  appName: 'Abbrevio Demo',
  version: '1.0.0-railway-render-FORCE-UPDATE', // Cache buster
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
  },
  // Added timestamp to force cache invalidation
  buildTimestamp: '2025-09-17T16:35:00Z'
};