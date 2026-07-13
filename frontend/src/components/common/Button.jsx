import { cx } from '../../utils/helpers';
import './common.css';

export default function Button({
  variant = 'primary',
  size,
  isLoading = false,
  disabled,
  children,
  className,
  ...rest
}) {
  return (
    <button
      className={cx('btn', `btn-${variant}`, size === 'sm' && 'btn-sm', className)}
      disabled={disabled || isLoading}
      {...rest}
    >
      {isLoading && <span className="spinner" aria-hidden="true" />}
      {children}
    </button>
  );
}