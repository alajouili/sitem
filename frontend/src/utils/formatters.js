export function formatDate(value, options = {}) {
  if (!value) return '—';
  const date = new Date(value.includes('T') ? value : value.replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return value;

  return date.toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    ...options,
  });
}

export function formatDateTime(value) {
  return formatDate(value, { hour: '2-digit', minute: '2-digit' });
}

export function formatFileSize(bytes) {
  if (!bytes || bytes <= 0) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB'];
  const power = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
  const value = bytes / 1024 ** power;

  return `${value.toFixed(power === 0 ? 0 : 1)} ${units[power]}`;
}

export function truncate(text, length = 100) {
  if (!text) return '';
  return text.length > length ? `${text.slice(0, length)}…` : text;
}

export function catalogNumber(id) {
  return `AR-${String(id).padStart(5, '0')}`;
}

export function titleCase(value) {
  return String(value)
    .split(/[\s_-]+/)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}