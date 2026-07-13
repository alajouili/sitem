const TOKEN_KEY = 'archive_system_token';
const EXPIRES_KEY = 'archive_system_token_expires_at';

/**
 * Wraps token persistence. Uses sessionStorage (not localStorage) so a
 * token doesn't silently outlive the browser tab/session — reasonable
 * default for an internal admin tool; swap for a different strategy
 * (httpOnly cookie, refresh tokens) if requirements change.
 */
export const tokenService = {
  getToken() {
    return sessionStorage.getItem(TOKEN_KEY);
  },

  setToken(token, expiresAt) {
    sessionStorage.setItem(TOKEN_KEY, token);
    if (expiresAt) {
      sessionStorage.setItem(EXPIRES_KEY, String(expiresAt));
    }
  },

  getExpiresAt() {
    const value = sessionStorage.getItem(EXPIRES_KEY);
    return value ? Number(value) : null;
  },

  isExpired() {
    const expiresAt = tokenService.getExpiresAt();
    if (!expiresAt) return false;
    return Date.now() >= expiresAt * 1000;
  },

  clear() {
    sessionStorage.removeItem(TOKEN_KEY);
    sessionStorage.removeItem(EXPIRES_KEY);
  },
};