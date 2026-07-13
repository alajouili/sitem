/**
 * Thin localStorage wrapper for non-sensitive UI state (view preferences,
 * last-used filters, etc). Never store the auth token here — see
 * tokenService for that.
 */
export const storageService = {
  get(key, fallback = null) {
    try {
      const raw = localStorage.getItem(key);
      return raw === null ? fallback : JSON.parse(raw);
    } catch {
      return fallback;
    }
  },

  set(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch {
      // Storage full or disabled — fail silently, it's only preferences.
    }
  },

  remove(key) {
    localStorage.removeItem(key);
  },
};