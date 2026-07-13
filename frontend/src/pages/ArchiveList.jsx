import { useContext, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useArchives } from '../hooks/useArchives';
import { useDebounce } from '../hooks/useDebounce';
import { RBACContext } from '../context/RBACContext';
import Table from '../components/common/Table';
import Pagination from '../components/common/Pagination';
import Button from '../components/common/Button';
import Modal from '../components/common/Modal';
import { Input, Select } from '../components/common/Field';
import StatusBadge from '../components/common/StatusBadge';
import Alert from '../components/common/Alert';
import { PageLoader } from '../components/common/Spinner';
import MetadataDetails from '../components/archive/MetadataDetails';
import { catalogNumber, formatDate, truncate } from '../utils/formatters';
import { ARCHIVE_STATUS_LABELS, ARCHIVE_FIELD_LABELS } from '../utils/constants';

/**
 * Extra columns pulled from `archive.metadata` (i.e. spreadsheet columns
 * that don't map to a core Archive field) worth surfacing directly in
 * the list, rather than only on the detail page. Keyed by the lowercased
 * header as it's stored in metadata; edit this list to match whatever
 * fields matter most for your data.
 */
const HIGHLIGHTED_METADATA_COLUMNS = [
  { key: 'technicien', header: 'Technicien' },
  { key: 'etat', header: 'État' },
  { key: 'motif', header: 'Motif' },
  { key: "date d'intervention", header: "Date d'intervention" },
];

export default function ArchiveList() {
  const { can } = useContext(RBACContext);
  const navigate = useNavigate();

  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [detailsRow, setDetailsRow] = useState(null);
  const debouncedSearch = useDebounce(search, 350);

  const { items, total, page, totalPages, isLoading, error, goToPage } = useArchives({
    search: debouncedSearch || undefined,
    status: status || undefined,
  });

  const columns = [
    { key: 'id', header: 'ID', render: (row) => <span className="mono">{catalogNumber(row.id)}</span> },
    { key: 'title', header: ARCHIVE_FIELD_LABELS.title },
    { key: 'category', header: ARCHIVE_FIELD_LABELS.category, render: (row) => row.category || '—' },
    { key: 'description', header: ARCHIVE_FIELD_LABELS.description, render: (row) => truncate(row.description, 60) || '—' },
    ...HIGHLIGHTED_METADATA_COLUMNS.map((col) => ({
      key: col.key,
      header: col.header,
      render: (row) => row.metadata?.[col.key] ?? '—',
    })),
    { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    { key: 'created_at', header: 'Created', render: (row) => <span className="mono">{formatDate(row.created_at)}</span> },
    {
      key: 'details',
      header: '',
      render: (row) => (
        <Button
          variant="secondary"
          size="sm"
          onClick={(event) => {
            event.stopPropagation(); // don't also trigger the row's "open archive" click
            setDetailsRow(row);
          }}
        >
          More details
        </Button>
      ),
    },
  ];

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 16 }}>
        <div>
          <h1>Archives</h1>
          <p>Browse and manage archived records.</p>
        </div>
        {can('archive:create') && (
          <Button variant="primary" onClick={() => navigate('/archives/new')}>
            + New Archive
          </Button>
        )}
      </div>

      <div style={{ display: 'flex', gap: 12, marginBottom: 16 }}>
        <Input
          placeholder="Search title or description…"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          style={{ maxWidth: 320 }}
        />
        <Select value={status} onChange={(e) => setStatus(e.target.value)} style={{ maxWidth: 200 }}>
          <option value="">All statuses</option>
          {Object.entries(ARCHIVE_STATUS_LABELS).map(([value, label]) => (
            <option key={value} value={value}>
              {label}
            </option>
          ))}
        </Select>
      </div>

      {error && <Alert variant="error">{error}</Alert>}

      {isLoading ? (
        <PageLoader />
      ) : (
        <>
          <Table
            columns={columns}
            rows={items}
            onRowClick={(row) => navigate(`/archives/${row.id}`)}
            emptyMessage="No archives match your filters yet."
          />
          <Pagination page={page} totalPages={totalPages} total={total} onChange={goToPage} />
        </>
      )}

      {detailsRow && (
        <Modal title={`${ARCHIVE_FIELD_LABELS.title}: ${detailsRow.title}`} onClose={() => setDetailsRow(null)}>
          <MetadataDetails metadata={detailsRow.metadata} />
        </Modal>
      )}
    </div>
  );
}