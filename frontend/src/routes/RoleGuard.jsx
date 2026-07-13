import { Navigate, Outlet } from 'react-router-dom';
import { useContext } from 'react';
import { RBACContext } from '../context/RBACContext';

export default function RoleGuard({ permission }) {
  const { can } = useContext(RBACContext);

  if (!can(permission)) {
    return <Navigate to="/" replace />;
  }

  return <Outlet />;
}