import { useEffect, useState } from 'react';
import { reportService } from '../api/reportService';
import Table from '../components/common/Table';
import Pagination from '../components/common/Pagination';
import Alert from '../components/common/Alert';
import { PageLoader } from '../components/common/Spinner';
import { formatDateTime } from '../utils/formatters';
import { apiErrorMessage } from '../utils/helpers';

export default function Logs() {
  const [items, setItems] = useState([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const perPage = 50;

  async function load(targetPage) {
    setIsLoading(true);
    try {
      const result = await reportService.logs({ page: targetPage, perPage });
      setItems(result.items);
      setTotal(result.total);
      setPage(targetPage);
    } catch (err) {
      setError(apiErrorMessage(err, 'Unable to load the audit log.'));
    } finally {
      setIsLoading(false);
    }
  }

  useEffect(() => {
    load(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const columns = [
    { key: 'created_at', header: 'When', render: (row) => <span className="mono">{formatDateTime(row.created_at)}</span> },
    { key: 'action', header: 'Action', render: (row) => <span className="mono">{row.action}</span> },
    { key: 'entity_type', header: 'Entity', render: (row) => `${row.entity_type}${row.entity_id ? ` #${row.entity_id}` : ''}` },
    { key: 'user_id', header: 'User', render: (row) => (row.user_id ? `#${row.user_id}` : 'System') },
  ];

  return (
    <div>
      <h1>Audit log</h1>
      <p>A record of significant actions taken across the system.</p>

      {error && <Alert variant="error">{error}</Alert>}

      {isLoading ? (
        <PageLoader />
      ) : (
        <>
          <Table columns={columns} rows={items} emptyMessage="No activity recorded yet." />
          <Pagination page={page} totalPages={Math.max(1, Math.ceil(total / perPage))} total={total} onChange={load} />
        </>
      )}
    </div>
  );
}