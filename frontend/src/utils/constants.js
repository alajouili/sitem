// Mirrors backend constants (app/Models/User.php, Archive.php, Import.php)
// so the frontend never hardcodes role/status strings in more than one place.

export const ROLES = {
  ADMIN: 'admin',
  EDITOR: 'editor',
  VIEWER: 'viewer',
};

export const ROLE_LABELS = {
  [ROLES.ADMIN]: 'Administrator',
  [ROLES.EDITOR]: 'Editor',
  [ROLES.VIEWER]: 'Viewer',
};

export const ARCHIVE_STATUS = {
  DRAFT: 'draft',
  PUBLISHED: 'published',
  ARCHIVED: 'archived',
};

export const ARCHIVE_STATUS_LABELS = {
  [ARCHIVE_STATUS.DRAFT]: 'Draft',
  [ARCHIVE_STATUS.PUBLISHED]: 'Published',
  [ARCHIVE_STATUS.ARCHIVED]: 'Archived',
};

export const IMPORT_STATUS = {
  PENDING: 'pending',
  PROCESSING: 'processing',
  COMPLETED: 'completed',
  FAILED: 'failed',
};

export const IMPORT_STATUS_LABELS = {
  [IMPORT_STATUS.PENDING]: 'Pending',
  [IMPORT_STATUS.PROCESSING]: 'Processing',
  [IMPORT_STATUS.COMPLETED]: 'Completed',
  [IMPORT_STATUS.FAILED]: 'Failed',
};

export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

export const PAGE_SIZE_DEFAULT = 20;

/**
 * Display labels for the archive's core fields — separate from the
 * internal field names the API/database use (title, description,
 * category, status), since the import maps whatever spreadsheet column
 * you actually use (e.g. "Code mission") onto those internal names.
 * Change these to match your source data's real column names; nothing
 * else needs to change.
 */
export const ARCHIVE_FIELD_LABELS = {
  title: 'Code mission',
  description: 'Commentaire',
  category: 'Category',
  status: 'Status',
};