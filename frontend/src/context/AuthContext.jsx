import { createContext, useCallback, useEffect, useMemo, useState } from 'react';
import { authService } from '../api/authService';
import { tokenService } from '../services/tokenService';
import { setUnauthorizedHandler } from '../api/axiosClient';

export const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  const clearSession = useCallback(() => {
    tokenService.clear();
    setUser(null);
  }, []);

  // On first load, if a (non-expired) token exists, hydrate the user by
  // calling /auth/me rather than trusting anything cached client-side.
  useEffect(() => {
    let cancelled = false;

    async function hydrate() {
      const token = tokenService.getToken();

      if (!token || tokenService.isExpired()) {
        clearSession();
        setIsLoading(false);
        return;
      }

      try {
        const me = await authService.me();
        if (!cancelled) setUser(me);
      } catch {
        if (!cancelled) clearSession();
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    }

    hydrate();

    return () => {
      cancelled = true;
    };
  }, [clearSession]);

  // Any 401 from the API (expired/invalid token mid-session) drops the
  // user back to a logged-out state.
  useEffect(() => {
    setUnauthorizedHandler(() => clearSession());
  }, [clearSession]);

  const login = useCallback(async (email, password) => {
    const result = await authService.login(email, password);
    tokenService.setToken(result.token, result.expires_at);
    setUser(result.user);
    return result.user;
  }, []);

  const logout = useCallback(async () => {
    try {
      await authService.logout();
    } finally {
      clearSession();
    }
  }, [clearSession]);

  const value = useMemo(
    () => ({
      user,
      isAuthenticated: Boolean(user),
      isLoading,
      login,
      logout,
    }),
    [user, isLoading, login, logout]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}