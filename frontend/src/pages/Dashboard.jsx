import { useContext, useEffect, useState } from 'react';
import { reportService } from '../api/reportService';
import StatCard from '../components/dashboard/StatCard';
import RecentActivity from '../components/dashboard/RecentActivity';
import { PageLoader } from '../components/common/Spinner';
import Alert from '../components/common/Alert';
import { RBACContext } from '../context/RBACContext';
import { apiErrorMessage } from '../utils/helpers';
import { ARCHIVE_STATUS_LABELS } from '../utils/constants';

export default function Dashboard() {
  const { can } = useContext(RBACContext);
  const [summary, setSummary] = useState(null);
  const [logs, setLogs] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    async function load() {
      try {
        const requests = [reportService.summary()];
        if (can('log:view')) requests.push(reportService.logs({ perPage: 8 }));

        const [summaryResult, logsResult] = await Promise.all(requests);
        setSummary(summaryResult);
        if (logsResult) setLogs(logsResult.items);
      } catch (err) {
        setError(apiErrorMessage(err, 'Unable to load the dashboard.'));
      } finally {
        setIsLoading(false);
      }
    }

    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  if (isLoading) return <PageLoader />;
  if (error) return <Alert variant="error">{error}</Alert>;

  return (
    <div>
      <h1>Dashboard</h1>
      <p>An overview of the archive collection and recent activity.</p>

      <div style={{ display: 'flex', gap: 16, margin: '24px 0' }}>
        <StatCard label="Total archives" value={summary?.archives_total ?? 0} />
        {Object.entries(summary?.archives_by_status || {}).map(([status, count]) => (
          <StatCard key={status} label={ARCHIVE_STATUS_LABELS[status] || status} value={count} accent="brass" />
        ))}
      </div>

      {can('log:view') && (
        <div className="card">
          <h3>Recent activity</h3>
          <RecentActivity logs={logs} />
        </div>
      )}
    </div>
  );
}