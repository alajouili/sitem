import { cx } from '../../utils/helpers';
import './common.css';

export default function Alert({ variant = 'info', children }) {
  if (!children) return null;

  return (
    <div className={cx('alert', `alert-${variant}`)} role={variant === 'error' ? 'alert' : 'status'}>
      {children}
    </div>
  );
}