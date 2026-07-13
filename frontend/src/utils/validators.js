export function isValidEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
}

export function isRequired(value) {
  if (value === null || value === undefined) return false;
  if (typeof value === 'string') return value.trim().length > 0;
  return true;
}

export function minLength(value, length) {
  return String(value || '').length >= length;
}

export function isXlsxFile(file) {
  if (!file) return false;
  const name = file.name || '';
  return name.toLowerCase().endsWith('.xlsx');
}

/**
 * Runs a set of { field: [validatorFns] } rules against a data object.
 * Each validator fn should return a string error message, or null/undefined
 * if valid. Returns { field: message } for the first failing rule per field.
 */
export function validate(data, rules) {
  const errors = {};

  for (const [field, validators] of Object.entries(rules)) {
    for (const validator of validators) {
      const message = validator(data[field], data);
      if (message) {
        errors[field] = message;
        break;
      }
    }
  }

  return errors;
}

export const rules = {
  required:
    (message = 'This field is required.') =>
    (value) =>
      isRequired(value) ? null : message,
  email:
    (message = 'Enter a valid email address.') =>
    (value) =>
      !value || isValidEmail(value) ? null : message,
  minLength:
    (length, message) =>
    (value) =>
      !value || minLength(value, length) ? null : message || `Must be at least ${length} characters.`,
};