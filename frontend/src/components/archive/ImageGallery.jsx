import { formatFileSize } from '../../utils/formatters';
import EmptyState from '../common/EmptyState';

export default function ImageGallery({ images }) {
  if (!images || images.length === 0) {
    return <EmptyState title="No images" description="This archive has no attached images yet." />;
  }

  return (
    <div
      style={{
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))',
        gap: '16px',
      }}
    >
      {images.map((image) => (
        <figure
          key={image.id}
          style={{
            margin: 0,
            border: '1px solid var(--color-paper-line)',
            borderRadius: 'var(--radius-md)',
            overflow: 'hidden',
            background: 'var(--color-surface)',
          }}
        >
          <img
            src={image.url}
            alt={image.original_name}
            style={{ width: '100%', height: 120, objectFit: 'cover', display: 'block' }}
          />
          <figcaption style={{ padding: '8px 10px', fontSize: 'var(--fs-xs)', color: 'var(--color-ink-soft)' }}>
            <div className="mono">{image.original_name}</div>
            <div>
              {image.width}×{image.height} · {formatFileSize(image.size)}
            </div>
          </figcaption>
        </figure>
      ))}
    </div>
  );
}