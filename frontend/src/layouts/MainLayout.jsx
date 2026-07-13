import { Outlet } from 'react-router-dom';
import Sidebar from '../components/layout/Sidebar';
import '../assets/styles/layout.css';

export default function MainLayout() {
  return (
    <div className="app-shell">
      <Sidebar />
      <div className="app-main">
        <div className="content-area">
          <Outlet />
        </div>
      </div>
    </div>
  );
}