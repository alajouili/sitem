import { useState } from 'react';
import { Field, Input, Select, Textarea } from '../common/Field';
import Button from '../common/Button';
import { ARCHIVE_STATUS, ARCHIVE_STATUS_LABELS, ARCHIVE_FIELD_LABELS } from '../../utils/constants';
import { validate, rules } from '../../utils/validators';

const VALIDATION_RULES = {
  title: [rules.required('A title is required.'), rules.minLength(2, 'Title must be at least 2 characters.')],
};

export default function ArchiveForm({ initialValues, onSubmit, isSubmitting, serverErrors = {} }) {
  const [values, setValues] = useState({
    title: initialValues?.title || '',
    description: initialValues?.description || '',
    category: initialValues?.category || '',
    status: initialValues?.status || ARCHIVE_STATUS.DRAFT,
  });
  const [clientErrors, setClientErrors] = useState({});

  const errors = { ...clientErrors, ...serverErrors };

  function update(field, value) {
    setValues((prev) => ({ ...prev, [field]: value }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    const validationErrors = validate(values, VALIDATION_RULES);
    setClientErrors(validationErrors);

    if (Object.keys(validationErrors).length === 0) {
      onSubmit(values);
    }
  }

  return (
    <form onSubmit={handleSubmit}>
      <Field label={ARCHIVE_FIELD_LABELS.title} htmlFor="title" error={firstError(errors.title)}>
        <Input
          id="title"
          value={values.title}
          onChange={(e) => update('title', e.target.value)}
          placeholder="e.g. Founding Charter"
        />
      </Field>

      <Field label={ARCHIVE_FIELD_LABELS.category} htmlFor="category" error={firstError(errors.category)}>
        <Input
          id="category"
          value={values.category}
          onChange={(e) => update('category', e.target.value)}
          placeholder="e.g. Legal, Finance, Governance"
        />
      </Field>

      <Field label={ARCHIVE_FIELD_LABELS.description} htmlFor="description" error={firstError(errors.description)}>
        <Textarea
          id="description"
          value={values.description}
          onChange={(e) => update('description', e.target.value)}
          placeholder="A short summary of this record"
        />
      </Field>

      <Field label="Status" htmlFor="status" error={firstError(errors.status)}>
        <Select id="status" value={values.status} onChange={(e) => update('status', e.target.value)}>
          {Object.entries(ARCHIVE_STATUS_LABELS).map(([value, label]) => (
            <option key={value} value={value}>
              {label}
            </option>
          ))}
        </Select>
      </Field>

      <Button type="submit" variant="primary" isLoading={isSubmitting}>
        {initialValues ? 'Save changes' : 'Create archive'}
      </Button>
    </form>
  );
}

function firstError(value) {
  return Array.isArray(value) ? value[0] : value;
}