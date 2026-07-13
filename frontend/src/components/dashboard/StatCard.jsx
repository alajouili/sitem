export default function StatCard({ label, value, accent = 'verdigris' }) {
  return (
    <div className="card" style={{ flex: 1, minWidth: 160 }}>
      <div
        style={{
          fontFamily: 'var(--font-mono)',
          fontSize: 'var(--fs-xs)',
          textTransform: 'uppercase',
          letterSpacing: '0.06em',
          color: 'var(--color-ink-soft)',
          marginBottom: 8,
        }}
      >
        {label}
      </div>
      <div
        style={{
          fontFamily: 'var(--font-display)',
          fontSize: 'var(--fs-2xl)',
          fontWeight: 600,
          color: `var(--color-${accent}-dark, var(--color-ink))`,
        }}
      >
        {value}
      </div>
    </div>
  );
}