import { Outlet } from 'react-router-dom';
import '../assets/styles/layout.css';

export default function AuthLayout() {
  return (
    <div className="auth-shell">
      <div className="auth-card">
        <Outlet />
      </div>
    </div>
  );
}