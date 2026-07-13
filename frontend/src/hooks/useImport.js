import { useCallback, useState } from 'react';
import { importService } from '../api/importService';
import { apiErrorMessage, apiFieldErrors } from '../utils/helpers';

export function useImport() {
  const [uploadProgress, setUploadProgress] = useState(0);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [result, setResult] = useState(null);
  const [error, setError] = useState(null);
  const [fieldErrors, setFieldErrors] = useState({});

  const upload = useCallback(async (file, label) => {
    setIsSubmitting(true);
    setError(null);
    setFieldErrors({});
    setResult(null);
    setUploadProgress(0);

    try {
      const imported = await importService.upload(file, {
        label,
        onProgress: setUploadProgress,
      });
      setResult(imported);
      return imported;
    } catch (err) {
      setError(apiErrorMessage(err, 'The import could not be processed.'));
      setFieldErrors(apiFieldErrors(err));
      throw err;
    } finally {
      setIsSubmitting(false);
    }
  }, []);

  const reset = useCallback(() => {
    setUploadProgress(0);
    setResult(null);
    setError(null);
    setFieldErrors({});
  }, []);

  return { upload, reset, uploadProgress, isSubmitting, result, error, fieldErrors };
}