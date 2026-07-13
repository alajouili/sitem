export default function ImportProgress({ percent }) {
  return (
    <div style={{ margin: '16px 0' }}>
      <div
        style={{
          height: 8,
          borderRadius: 4,
          background: 'var(--color-paper-dim)',
          overflow: 'hidden',
        }}
      >
        <div
          style={{
            height: '100%',
            width: `${percent}%`,
            background: 'var(--color-verdigris)',
            transition: 'width 150ms ease',
          }}
        />
      </div>
      <div className="mono" style={{ fontSize: 'var(--fs-xs)', color: 'var(--color-ink-soft)', marginTop: 6 }}>
        Uploading… {percent}%
      </div>
    </div>
  );
}