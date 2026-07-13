import { useAuth } from '../hooks/useAuth';
import { ROLE_LABELS } from '../utils/constants';

export default function Profile() {
  const { user } = useAuth();

  if (!user) return null;

  return (
    <div style={{ maxWidth: 480 }}>
      <h1>Profile</h1>
      <p>Your account details.</p>

      <div className="card">
        <dl style={{ margin: 0 }}>
          <Row label="Name" value={user.name} />
          <Row label="Email" value={user.email} />
          <Row label="Role" value={ROLE_LABELS[user.role] || user.role} />
          <Row label="Member since" value={user.created_at} />
        </dl>
      </div>
    </div>
  );
}

function Row({ label, value }) {
  return (
    <div style={{ display: 'flex', justifyContent: 'space-between', padding: '10px 0', borderBottom: '1px solid var(--color-paper-line)' }}>
      <dt style={{ color: 'var(--color-ink-soft)', fontSize: 'var(--fs-sm)' }}>{label}</dt>
      <dd style={{ margin: 0, fontWeight: 600 }}>{value}</dd>
    </div>
  );
}