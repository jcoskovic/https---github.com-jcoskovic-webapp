import { environment } from '../../environments/environment';

export interface ApiConfig {
  baseUrl: string;
  endpoints: {
    // Authentication
    register: string;
    login: string;
    logout: string;
    me: string;
    changePassword: string;
    refresh: string;
    forgotPassword: string;
    resetPassword: string;
    verifyEmail: string;
    resendVerification: string;

    // Abbreviations
    abbreviations: string;
    abbreviationById: (id: number) => string;
    abbreviationSuggestions: string;
    abbreviationVote: (id: number) => string;
    abbreviationComment: (id: number) => string;

    // Categories and Stats
    categories: string;
    stats: string;
    suggestions: string;

    // Export
    exportPdf: string;

    // ML/Recommendations
    mlHealth: string;
    mlRecommendations: (id: number) => string;
    mlPersonalized: (userId: number) => string;
    mlTrending: string;
    mlTrain: string;

    // Admin endpoints
    adminStatistics: string;
    adminUsers: string;
    adminAbbreviations: string;
    adminAbbreviationsPending: string;
    adminUserPromote: (userId: number) => string;
    adminUserDemote: (userId: number) => string;
    adminUserDelete: (userId: number) => string;
    adminAbbreviationApprove: (abbreviationId: number) => string;
    adminAbbreviationReject: (abbreviationId: number) => string;
  };
}

// API configuration using environment
export const apiConfig: ApiConfig = {
  baseUrl: environment.apiUrl,
  endpoints: {
    // Authentication
    register: '/register',
    login: '/login',
    logout: '/logout',
    me: '/me',
    changePassword: '/change-password',
    refresh: '/refresh',
    forgotPassword: '/forgot-password',
    resetPassword: '/reset-password',
    verifyEmail: '/verify-email',
    resendVerification: '/resend-verification',

    // Abbreviations
    abbreviations: '/abbreviations',
    abbreviationById: (id: number) => `/abbreviations/${id}`,
    abbreviationSuggestions: '/abbreviations/suggestions',
    abbreviationVote: (id: number) => `/abbreviations/${id}/vote`,
    abbreviationComment: (id: number) => `/abbreviations/${id}/comments`,

    // Categories and Stats
    categories: '/categories',
    stats: '/stats',
    suggestions: '/suggestions',

    // Export
    exportPdf: '/export/pdf',

    // ML/Recommendations
    mlHealth: '/ml/health',
    mlRecommendations: (id: number) => `/ml/recommendations/${id}`,
    mlPersonalized: (userId: number) =>
      `/ml/recommendations/personalized/${userId}`,
    mlTrending: '/ml/trending',
    mlTrain: '/ml/train',

    // Admin endpoints
    adminStatistics: '/admin/statistics',
    adminUsers: '/admin/users',
    adminAbbreviations: '/admin/abbreviations',
    adminAbbreviationsPending: '/admin/abbreviations/pending',
    adminUserPromote: (userId: number) => `/admin/users/${userId}/promote`,
    adminUserDemote: (userId: number) => `/admin/users/${userId}/demote`,
    adminUserDelete: (userId: number) => `/admin/users/${userId}`,
    adminAbbreviationApprove: (abbreviationId: number) =>
      `/admin/abbreviations/${abbreviationId}/approve`,
    adminAbbreviationReject: (abbreviationId: number) =>
      `/admin/abbreviations/${abbreviationId}/reject`,
  },
};

// Helper function to build full URL
export const buildUrl = (
  endpoint: string,
  params?: Record<string, string | number>,
): string => {
  let url = `${apiConfig.baseUrl}${endpoint}`;

  if (params) {
    const queryParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      queryParams.append(key, value.toString());
    });
    const queryString = queryParams.toString();
    if (queryString) {
      url += `?${queryString}`;
    }
  }

  return url;
};
