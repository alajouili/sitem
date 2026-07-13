import { useEffect, useState } from 'react';
import { reportService } from '../api/reportService';
import StatCard from '../components/dashboard/StatCard';
import Button from '../components/common/Button';
import Alert from '../components/common/Alert';
import { PageLoader } from '../components/common/Spinner';
import { ARCHIVE_STATUS_LABELS } from '../utils/constants';
import { apiErrorMessage } from '../utils/helpers';

export default function Reports() {
  const [summary, setSummary] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isExporting, setIsExporting] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    reportService
      .summary()
      .then(setSummary)
      .catch((err) => setError(apiErrorMessage(err, 'Unable to load report data.')))
      .finally(() => setIsLoading(false));
  }, []);

  async function handleExport() {
    setIsExporting(true);
    try {
      const blob = await reportService.exportCsv();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'archives-export.csv';
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      setError(apiErrorMessage(err, 'The export could not be generated.'));
    } finally {
      setIsExporting(false);
    }
  }

  if (isLoading) return <PageLoader />;

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 16 }}>
        <div>
          <h1>Reports</h1>
          <p>Snapshot of the archive collection.</p>
        </div>
        <Button variant="secondary" onClick={handleExport} isLoading={isExporting}>
          Export CSV
        </Button>
      </div>

      {error && <Alert variant="error">{error}</Alert>}

      <div style={{ display: 'flex', gap: 16, flexWrap: 'wrap' }}>
        <StatCard label="Total archives" value={summary?.archives_total ?? 0} />
        {Object.entries(summary?.archives_by_status || {}).map(([status, count]) => (
          <StatCard key={status} label={ARCHIVE_STATUS_LABELS[status] || status} value={count} accent="brass" />
        ))}
      </div>

      <p className="mono" style={{ marginTop: 24, fontSize: 'var(--fs-xs)', color: 'var(--color-ink-soft)' }}>
        Generated {summary?.generated_at}
      </p>
    </div>
  );
}