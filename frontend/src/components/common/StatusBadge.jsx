import { cx } from '../../utils/helpers';
import { ARCHIVE_STATUS_LABELS, IMPORT_STATUS_LABELS } from '../../utils/constants';
import './common.css';

const LABELS = { ...ARCHIVE_STATUS_LABELS, ...IMPORT_STATUS_LABELS };

export default function StatusBadge({ status }) {
  return (
    <span className={cx('status-stamp', `status-${status}`)}>
      {LABELS[status] || status}
    </span>
  );
}