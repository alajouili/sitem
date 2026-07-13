import { formatDateTime } from '../../utils/formatters';
import EmptyState from '../common/EmptyState';

export default function RecentActivity({ logs }) {
  if (!logs || logs.length === 0) {
    return <EmptyState title="No recent activity" description="Actions taken across the system will appear here." />;
  }

  return (
    <ul style={{ listStyle: 'none', margin: 0, padding: 0 }}>
      {logs.map((log) => (
        <li
          key={log.id}
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            gap: 12,
            padding: '10px 0',
            borderBottom: '1px solid var(--color-paper-line)',
            fontSize: 'var(--fs-sm)',
          }}
        >
          <span>
            <strong className="mono">{log.action}</strong> on {log.entity_type}
            {log.entity_id ? ` #${log.entity_id}` : ''}
          </span>
          <span className="mono" style={{ color: 'var(--color-ink-soft)', flexShrink: 0 }}>
            {formatDateTime(log.created_at)}
          </span>
        </li>
      ))}
    </ul>
  );
}