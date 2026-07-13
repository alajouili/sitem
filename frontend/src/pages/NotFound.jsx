import { Link } from 'react-router-dom';

export default function NotFound() {
  return (
    <div style={{ textAlign: 'center', padding: '80px 20px' }}>
      <div className="mono" style={{ fontSize: 'var(--fs-sm)', color: 'var(--color-ink-soft)', marginBottom: 8 }}>
        ERROR 404
      </div>
      <h1>Record not found</h1>
       <p>The page you&apos;re looking for doesn&apos;t exist, or may have been moved.</p>
      <Link to="/">Return to dashboard</Link>
    </div>
  );
}