import axios from 'axios';
import { API_BASE_URL } from '../utils/constants';
import { tokenService } from '../services/tokenService';

export const axiosClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

axiosClient.interceptors.request.use((config) => {
  const token = tokenService.getToken();

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  // Let the browser set the multipart boundary itself for FormData bodies.
  if (config.data instanceof FormData) {
    delete config.headers['Content-Type'];
  }

  return config;
});

/**
 * Registered by AuthContext so a 401 (expired/invalid token) can trigger
 * a clean logout + redirect without axiosClient needing to know about
 * React Router or context directly.
 */
let unauthorizedHandler = null;

export function setUnauthorizedHandler(handler) {
  unauthorizedHandler = handler;
}

axiosClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error?.response?.status === 401 && unauthorizedHandler) {
      unauthorizedHandler();
    }

    return Promise.reject(error);
  }
);

export default axiosClient;