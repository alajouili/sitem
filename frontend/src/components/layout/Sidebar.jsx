import { NavLink } from 'react-router-dom';
import { useContext } from 'react';
import { RBACContext } from '../../context/RBACContext';
import { useAuth } from '../../hooks/useAuth';
import { ROLE_LABELS } from '../../utils/constants';
import '../../assets/styles/layout.css';

const NAV_ITEMS = [
  { to: '/', label: 'Dashboard', icon: '01', end: true },
  { to: '/archives', label: 'Archives', icon: '02' },
  { to: '/import', label: 'Import', icon: '03', permission: 'import:create' },
  { to: '/reports', label: 'Reports', icon: '04' },
  { to: '/logs', label: 'Logs', icon: '05', permission: 'log:view' },
  { to: '/users', label: 'Users', icon: '06', permission: 'user:manage' },
];

export default function Sidebar() {
  const { can } = useContext(RBACContext);
  const { user, logout } = useAuth();

  return (
    <aside className="sidebar">
      <div className="sidebar-brand">
        Archive<span>SITEM</span>
      </div>

      <nav className="sidebar-nav">
        {NAV_ITEMS.filter((item) => !item.permission || can(item.permission)).map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            end={item.end}
            className={({ isActive }) => `sidebar-link${isActive ? ' is-active' : ''}`}
          >
            <span className="sidebar-icon">{item.icon}</span>
            {item.label}
          </NavLink>
        ))}
      </nav>

      <div className="sidebar-footer">
        <div className="sidebar-user">
          <strong>{user?.name}</strong>
          {ROLE_LABELS[user?.role] || user?.role}
        </div>
        <NavLink to="/profile" className="sidebar-link">
          <span className="sidebar-icon">07</span>
          Profile
        </NavLink>
        <button type="button" className="sidebar-link" onClick={logout} style={{ width: '100%', border: 'none', background: 'none', textAlign: 'left' }}>
          <span className="sidebar-icon">⏻</span>
          Sign out
        </button>
      </div>
    </aside>
  );
}