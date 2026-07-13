import './common.css';

export function Spinner() {
  return <span className="spinner" role="status" aria-label="Loading" />;
}

export function PageLoader() {
  return (
    <div style={{ display: 'flex', justifyContent: 'center', padding: '64px 0' }}>
      <Spinner />
    </div>
  );
}