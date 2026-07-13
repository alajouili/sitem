/**
 * Joins truthy class name fragments — a tiny classnames() alternative so
 * we don't need an extra dependency.
 */
export function cx(...parts) {
  return parts.filter(Boolean).join(' ');
}

/**
 * Extracts a human-readable message from an API error response, matching
 * the { success, message, errors } shape returned by Core\Response::error().
 */
export function apiErrorMessage(error, fallback = 'Something went wrong. Please try again.') {
  return error?.response?.data?.message || error?.message || fallback;
}

/**
 * Extracts field-level validation errors ({ field: [messages] }) from a
 * 422 response, matching ValidationException's error shape.
 */
export function apiFieldErrors(error) {
  const errors = error?.response?.data?.errors;
  return errors && typeof errors === 'object' ? errors : {};
}

export function firstFieldError(fieldErrors, field) {
  const messages = fieldErrors?.[field];
  return Array.isArray(messages) ? messages[0] : messages;
}

export function debounce(fn, delayMs = 300) {
  let timeoutId;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delayMs);
  };
}