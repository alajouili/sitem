import { Outlet } from 'react-router-dom';
import Sidebar from '../components/layout/Sidebar';
import '../assets/styles/layout.css';

export default function AdminLayout() {
  return (
    <div className="app-shell">
      <Sidebar />
      <div className="app-main">
        <header className="topbar" style={{ background: 'var(--color-paper-dim)' }}>
          <span className="mono" style={{ fontSize: 'var(--fs-xs)', color: 'var(--color-brass-dark)', textTransform: 'uppercase', letterSpacing: '0.08em' }}>
            Administration
          </span>
        </header>
        <div className="content-area">
          <Outlet />
        </div>
      </div>
    </div>
  );
}