import { createContext, useContext, useMemo } from 'react';
import { AuthContext } from './AuthContext';
import { ROLES } from '../utils/constants';

/**
 * Central permission table. Keeping this as data (rather than scattering
 * role checks through components) means adding a new permission or role
 * only touches one place.
 */
const PERMISSIONS = {
  'archive:view': [ROLES.ADMIN, ROLES.EDITOR, ROLES.VIEWER],
  'archive:create': [ROLES.ADMIN, ROLES.EDITOR],
  'archive:update': [ROLES.ADMIN, ROLES.EDITOR],
  'archive:delete': [ROLES.ADMIN],
  'import:create': [ROLES.ADMIN, ROLES.EDITOR],
  'user:manage': [ROLES.ADMIN],
  'report:view': [ROLES.ADMIN, ROLES.EDITOR, ROLES.VIEWER],
  'log:view': [ROLES.ADMIN],
};

export const RBACContext = createContext(null);

export function RBACProvider({ children }) {
  const { user } = useContext(AuthContext);

  const value = useMemo(() => {
    const role = user?.role ?? null;

    return {
      role,
      hasRole: (...roles) => role !== null && roles.includes(role),
      isAdmin: role === ROLES.ADMIN,
      can: (permission) => role !== null && (PERMISSIONS[permission] || []).includes(role),
    };
  }, [user]);

  return <RBACContext.Provider value={value}>{children}</RBACContext.Provider>;
}