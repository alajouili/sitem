import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';

import AuthLayout from '../layouts/AuthLayout';
import MainLayout from '../layouts/MainLayout';
import AdminLayout from '../layouts/AdminLayout';

import PrivateRoute from './PrivateRoute';
import RoleGuard from './RoleGuard';

import Login from '../pages/Login';
import Dashboard from '../pages/Dashboard';
import ArchiveList from '../pages/ArchiveList';
import ArchiveDetail from '../pages/ArchiveDetail';
import ArchiveEdit from '../pages/ArchiveEdit';
import ImportPage from '../pages/ImportPage';
import UserManagement from '../pages/UserManagement';
import Reports from '../pages/Reports';
import Logs from '../pages/Logs';
import Profile from '../pages/Profile';
import NotFound from '../pages/NotFound';

export default function AppRouter() {
  return (
    <BrowserRouter>
      <Routes>
        {/* Public */}
        <Route element={<AuthLayout />}>
          <Route path="/login" element={<Login />} />
        </Route>

        {/* Authenticated */}
        <Route element={<PrivateRoute />}>
          <Route element={<MainLayout />}>
            <Route path="/" element={<Dashboard />} />
            <Route path="/archives" element={<ArchiveList />} />
            <Route path="/archives/new" element={<ArchiveEdit />} />
            <Route path="/archives/:id" element={<ArchiveDetail />} />
            <Route path="/archives/:id/edit" element={<ArchiveEdit />} />
            <Route path="/reports" element={<Reports />} />
            <Route path="/profile" element={<Profile />} />

            <Route element={<RoleGuard permission="import:create" />}>
              <Route path="/import" element={<ImportPage />} />
            </Route>
          </Route>

          {/* Admin-only area, distinct shell */}
          <Route element={<RoleGuard permission="user:manage" />}>
            <Route element={<AdminLayout />}>
              <Route path="/users" element={<UserManagement />} />
            </Route>
          </Route>

          <Route element={<RoleGuard permission="log:view" />}>
            <Route element={<MainLayout />}>
              <Route path="/logs" element={<Logs />} />
            </Route>
          </Route>
        </Route>

        <Route path="/404" element={<NotFound />} />
        <Route path="*" element={<Navigate to="/404" replace />} />
      </Routes>
    </BrowserRouter>
  );
}