import StatusBadge from '../common/StatusBadge';
import Alert from '../common/Alert';

export default function ImportResultSummary({ result }) {
  if (!result) return null;

  const hasErrors = result.error_log && result.error_log.length > 0;

  return (
    <div className="card" style={{ marginTop: 24 }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 12 }}>
        <StatusBadge status={result.status} />
        <span className="mono" style={{ fontSize: 'var(--fs-sm)' }}>
          {result.processed_rows} / {result.total_rows} rows processed
        </span>
      </div>

      {hasErrors && (
        <Alert variant="error">
          <div>
            <strong>{result.error_log.length} row(s) could not be imported:</strong>
            <ul style={{ margin: '8px 0 0', paddingLeft: 20 }}>
              {result.error_log.map((entry, index) => (
                <li key={index}>
                  Row {entry.row}: {Object.values(entry.errors).flat().join(' ')}
                </li>
              ))}
            </ul>
          </div>
        </Alert>
      )}

      {!hasErrors && result.status === 'completed' && (
        <Alert variant="success">All rows were imported successfully.</Alert>
      )}
    </div>
  );
}