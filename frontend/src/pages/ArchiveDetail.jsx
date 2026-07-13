import { useContext, useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { archiveService } from '../api/archiveService';
import { RBACContext } from '../context/RBACContext';
import StatusBadge from '../components/common/StatusBadge';
import Button from '../components/common/Button';
import Alert from '../components/common/Alert';
import { PageLoader } from '../components/common/Spinner';
import { ConfirmModal } from '../components/common/Modal';
import ImageGallery from '../components/archive/ImageGallery';
import MetadataDetails from '../components/archive/MetadataDetails';
import { catalogNumber, formatDateTime } from '../utils/formatters';
import { ARCHIVE_FIELD_LABELS } from '../utils/constants';
import { apiErrorMessage } from '../utils/helpers';

export default function ArchiveDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { can } = useContext(RBACContext);

  const [archive, setArchive] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isDeleting, setIsDeleting] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      setIsLoading(true);
      try {
        const result = await archiveService.get(id);
        if (!cancelled) setArchive(result);
      } catch (err) {
        if (!cancelled) setError(apiErrorMessage(err, 'This archive could not be found.'));
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    }

    load();
    return () => {
      cancelled = true;
    };
  }, [id]);

  async function handleDelete() {
    setIsDeleting(true);
    try {
      await archiveService.remove(id);
      navigate('/archives');
    } catch (err) {
      setError(apiErrorMessage(err, 'This archive could not be deleted.'));
      setShowDeleteConfirm(false);
    } finally {
      setIsDeleting(false);
    }
  }

  if (isLoading) return <PageLoader />;
  if (error && !archive) return <Alert variant="error">{error}</Alert>;
  if (!archive) return null;

  return (
    <div>
      <Link to="/archives">&larr; Back to archives</Link>

      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', margin: '16px 0' }}>
        <div>
          <span className="mono" style={{ color: 'var(--color-ink-soft)', fontSize: 'var(--fs-sm)' }}>
            {catalogNumber(archive.id)}
          </span>
          <h1 style={{ marginTop: 4 }}>{archive.title}</h1>
          <div style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
            <StatusBadge status={archive.status} />
            {archive.category && <span style={{ color: 'var(--color-ink-soft)' }}>{archive.category}</span>}
          </div>
        </div>

        <div style={{ display: 'flex', gap: 8 }}>
          {can('archive:update') && (
            <Button variant="secondary" onClick={() => navigate(`/archives/${archive.id}/edit`)}>
              Edit
            </Button>
          )}
          {can('archive:delete') && (
            <Button variant="danger" onClick={() => setShowDeleteConfirm(true)}>
              Delete
            </Button>
          )}
        </div>
      </div>

      {error && <Alert variant="error">{error}</Alert>}

      <div className="card" style={{ marginBottom: 24 }}>
        <h3>{ARCHIVE_FIELD_LABELS.description}</h3>
        <p>{archive.description || 'No description provided.'}</p>
        <p className="mono" style={{ fontSize: 'var(--fs-xs)' }}>
          Created {formatDateTime(archive.created_at)} · Last updated {formatDateTime(archive.updated_at)}
        </p>
      </div>

      {archive.metadata && Object.keys(archive.metadata).length > 0 && (
        <div className="card" style={{ marginBottom: 24 }}>
          <h3>Additional details</h3>
          <MetadataDetails metadata={archive.metadata} />
        </div>
      )}

      <h3>Attached images</h3>
      <div style={{ display: 'flex', gap: '10px', flexWrap: 'wrap' }}>
        {archive.images?.map(img => (
          <div key={img.id} style={{ border: '1px solid #ccc', padding: '8px', borderRadius: '4px' }}>
            <img 
              src={`http://127.0.0.1:8000/api/images/${img.id}/raw`} 
              alt={img.name || "Attached asset"} 
              style={{ width: '150px', height: '120px', objectFit: 'cover', display: 'block' }} 
            />
            <p style={{ fontSize: '11px', textAlign: 'center', marginTop: '4px', maxWidth: '150px', overflow: 'hidden' }}>
              {img.name}
            </p>
          </div>
        ))}
      </div>

      {showDeleteConfirm && (
        <ConfirmModal
          title="Delete this archive?"
          message="This will permanently remove the archive and any attached images. This cannot be undone."
          confirmLabel="Delete"
          danger
          isLoading={isDeleting}
          onConfirm={handleDelete}
          onCancel={() => setShowDeleteConfirm(false)}
        />
      )}
    </div>
  );
}