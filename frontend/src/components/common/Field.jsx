import { cx } from '../../utils/helpers';
import './common.css';

export function Field({ label, htmlFor, hint, error, children }) {
  return (
    <div className="field">
      {label && (
        <label className="field-label" htmlFor={htmlFor}>
          {label}
        </label>
      )}
      {children}
      {error ? <span className="field-error">{error}</span> : hint ? <span className="field-hint">{hint}</span> : null}
    </div>
  );
}

export function Input({ error, className, ...rest }) {
  return <input className={cx('input', error && 'has-error', className)} {...rest} />;
}

export function Select({ error, className, children, ...rest }) {
  return (
    <select className={cx('select', error && 'has-error', className)} {...rest}>
      {children}
    </select>
  );
}

export function Textarea({ error, className, ...rest }) {
  return <textarea className={cx('textarea', error && 'has-error', className)} {...rest} />;
}