import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useImport } from '../hooks/useImport';
import { importService } from '../api/importService';
import FileDropzone from '../components/import/FileDropzone';
import ImportProgress from '../components/import/ImportProgress';
import ImportResultSummary from '../components/import/ImportResultSummary';
import Button from '../components/common/Button';
import Alert from '../components/common/Alert';
import Table from '../components/common/Table';
import StatusBadge from '../components/common/StatusBadge';
import { formatDateTime } from '../utils/formatters';
import { apiErrorMessage } from '../utils/helpers';

export default function ImportPage() {
  const [file, setFile] = useState(null);
  const [fileError, setFileError] = useState(null);
  const { upload, uploadProgress, isSubmitting, result, error, reset } = useImport();

  const [pastImports, setPastImports] = useState([]);
  const [isLoadingHistory, setIsLoadingHistory] = useState(true);
  const [historyError, setHistoryError] = useState(null);

  async function loadHistory() {
    setIsLoadingHistory(true);
    try {
      const data = await importService.list({ perPage: 10 });
      setPastImports(data.items);
    } catch (err) {
      setHistoryError(apiErrorMessage(err, 'Unable to load past imports.'));
    } finally {
      setIsLoadingHistory(false);
    }
  }

  useEffect(() => {
    loadHistory();
  }, []);

  async function handleUpload() {
    if (!file) {
      setFileError('Choose a .xlsx file first.');
      return;
    }

    try {
      await upload(file);
      setFile(null);
      loadHistory();
    } catch {
      // error state is already surfaced by useImport
    }
  }

  const columns = [
    { key: 'filename', header: 'File', render: (row) => <span className="mono">{row.filename.split('/').pop()}</span> },
    { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    { key: 'processed_rows', header: 'Rows', render: (row) => `${row.processed_rows} / ${row.total_rows}` },
    { key: 'created_at', header: 'Uploaded', render: (row) => <span className="mono">{formatDateTime(row.created_at)}</span> },
  ];

  return (
    <div>
      <h1>Import archives</h1>
      <p>Upload an .xlsx workbook. Each row becomes an archive; embedded images are linked to the row they're anchored to.</p>

      <div className="card" style={{ maxWidth: 640, marginBottom: 24 }}>
        <FileDropzone
          file={file}
          onSelect={(f, err) => {
            setFile(f);
            setFileError(err);
            reset();
          }}
          error={fileError}
        />

        {isSubmitting && <ImportProgress percent={uploadProgress} />}
        {error && (
          <div style={{ marginTop: 16 }}>
            <Alert variant="error">{error}</Alert>
          </div>
        )}

        <div style={{ marginTop: 16 }}>
          <Button variant="primary" onClick={handleUpload} isLoading={isSubmitting} disabled={!file}>
            Upload and process
          </Button>
        </div>

        <ImportResultSummary result={result} />
      </div>

      <h3>Recent imports</h3>
      {historyError && <Alert variant="error">{historyError}</Alert>}
      {!isLoadingHistory && <Table columns={columns} rows={pastImports} emptyMessage="No imports yet." />}

      <p style={{ marginTop: 16 }}>
        Looking for the full archive list? <Link to="/archives">View archives</Link>
      </p>
    </div>
  );
}