export default function MetadataDetails({ metadata }) {
  if (!metadata || Object.keys(metadata).length === 0) {
    return <p>No additional details for this record.</p>;
  }

  return (
    <dl
      style={{
        margin: 0,
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))',
        gap: '12px 24px',
      }}
    >
      {Object.entries(metadata).map(([key, value]) => (
        <div key={key}>
          <dt
            className="mono"
            style={{ fontSize: 'var(--fs-xs)', color: 'var(--color-ink-soft)', textTransform: 'capitalize' }}
          >
            {key}
          </dt>
          <dd style={{ margin: 0, fontWeight: 500 }}>{value === null || value === '' ? '—' : String(value)}</dd>
        </div>
      ))}
    </dl>
  );
}