import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { archiveService } from '../api/archiveService';
import ArchiveForm from '../components/archive/ArchiveForm';
import Alert from '../components/common/Alert';
import { PageLoader } from '../components/common/Spinner';
import { apiErrorMessage, apiFieldErrors } from '../utils/helpers';

export default function ArchiveEdit() {
  const { id } = useParams();
  const isEditing = Boolean(id);
  const navigate = useNavigate();

  const [archive, setArchive] = useState(null);
  const [isLoading, setIsLoading] = useState(isEditing);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState(null);
  const [fieldErrors, setFieldErrors] = useState({});

  useEffect(() => {
    if (!isEditing) return;

    let cancelled = false;
    archiveService
      .get(id)
      .then((result) => {
        if (!cancelled) setArchive(result);
      })
      .catch((err) => {
        if (!cancelled) setError(apiErrorMessage(err, 'This archive could not be loaded.'));
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [id, isEditing]);

  async function handleSubmit(values) {
    setIsSubmitting(true);
    setError(null);
    setFieldErrors({});

    try {
      const saved = isEditing ? await archiveService.update(id, values) : await archiveService.create(values);
      navigate(`/archives/${saved.id}`);
    } catch (err) {
      setError(apiErrorMessage(err, 'This archive could not be saved.'));
      setFieldErrors(apiFieldErrors(err));
    } finally {
      setIsSubmitting(false);
    }
  }

  if (isLoading) return <PageLoader />;

  return (
    <div style={{ maxWidth: 560 }}>
      <Link to={isEditing ? `/archives/${id}` : '/archives'}>&larr; Back</Link>

      <h1 style={{ marginTop: 16 }}>{isEditing ? 'Edit archive' : 'New archive'}</h1>

      {error && (
        <div style={{ marginBottom: 16 }}>
          <Alert variant="error">{error}</Alert>
        </div>
      )}

      <div className="card">
        <ArchiveForm
          initialValues={archive}
          onSubmit={handleSubmit}
          isSubmitting={isSubmitting}
          serverErrors={fieldErrors}
        />
      </div>
    </div>
  );
}