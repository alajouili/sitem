import { useEffect, useState } from 'react';
import { userService } from '../api/userService';
import Table from '../components/common/Table';
import Button from '../components/common/Button';
import Alert from '../components/common/Alert';
import { PageLoader } from '../components/common/Spinner';
import Modal, { ConfirmModal } from '../components/common/Modal';
import { Field, Input, Select } from '../components/common/Field';
import { ROLES, ROLE_LABELS } from '../utils/constants';
import { formatDate } from '../utils/formatters';
import { apiErrorMessage, apiFieldErrors, firstFieldError } from '../utils/helpers';
import { validate, rules } from '../utils/validators';

const EMPTY_FORM = { name: '', email: '', password: '', role: ROLES.VIEWER };

export default function UserManagement() {
  const [users, setUsers] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  const [editingUser, setEditingUser] = useState(null); // null = closed, {} = new, object = editing
  const [deletingUser, setDeletingUser] = useState(null);
  const [formValues, setFormValues] = useState(EMPTY_FORM);
  const [formErrors, setFormErrors] = useState({});
  const [isSaving, setIsSaving] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);

  async function load() {
    setIsLoading(true);
    try {
      const result = await userService.list({ perPage: 50 });
      setUsers(result.items);
    } catch (err) {
      setError(apiErrorMessage(err, 'Unable to load users.'));
    } finally {
      setIsLoading(false);
    }
  }

  useEffect(() => {
    load();
  }, []);

  function openCreate() {
    setFormValues(EMPTY_FORM);
    setFormErrors({});
    setEditingUser({});
  }

  function openEdit(user) {
    setFormValues({ name: user.name, email: user.email, password: '', role: user.role });
    setFormErrors({});
    setEditingUser(user);
  }

  async function handleSave(event) {
    event.preventDefault();

    const isCreating = !editingUser.id;
    const validationRules = {
      name: [rules.required('A name is required.')],
      email: [rules.required('An email is required.'), rules.email()],
      ...(isCreating ? { password: [rules.required('A password is required.'), rules.minLength(8, 'At least 8 characters.')] } : {}),
    };

    const clientErrors = validate(formValues, validationRules);
    if (Object.keys(clientErrors).length > 0) {
      setFormErrors(clientErrors);
      return;
    }

    setIsSaving(true);
    try {
      const payload = { ...formValues };
      if (!isCreating && !payload.password) delete payload.password;

      if (isCreating) {
        await userService.create(payload);
      } else {
        await userService.update(editingUser.id, payload);
      }

      setEditingUser(null);
      load();
    } catch (err) {
      setFormErrors(apiFieldErrors(err));
      setError(apiErrorMessage(err, 'This user could not be saved.'));
    } finally {
      setIsSaving(false);
    }
  }

  async function handleDelete() {
    setIsDeleting(true);
    try {
      await userService.remove(deletingUser.id);
      setDeletingUser(null);
      load();
    } catch (err) {
      setError(apiErrorMessage(err, 'This user could not be deleted.'));
    } finally {
      setIsDeleting(false);
    }
  }

  const columns = [
    { key: 'name', header: 'Name' },
    { key: 'email', header: 'Email' },
    { key: 'role', header: 'Role', render: (row) => ROLE_LABELS[row.role] || row.role },
    { key: 'created_at', header: 'Joined', render: (row) => <span className="mono">{formatDate(row.created_at)}</span> },
    {
      key: 'actions',
      header: '',
      render: (row) => (
        <div style={{ display: 'flex', gap: 8 }}>
          <Button variant="secondary" size="sm" onClick={() => openEdit(row)}>
            Edit
          </Button>
          <Button variant="danger" size="sm" onClick={() => setDeletingUser(row)}>
            Delete
          </Button>
        </div>
      ),
    },
  ];

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 16 }}>
        <div>
          <h1>Users</h1>
          <p>Manage who can access the archive system and what they can do.</p>
        </div>
        <Button variant="primary" onClick={openCreate}>
          + New user
        </Button>
      </div>

      {error && <Alert variant="error">{error}</Alert>}

      {isLoading ? <PageLoader /> : <Table columns={columns} rows={users} emptyMessage="No users yet." />}

      {editingUser && (
        <Modal
          title={editingUser.id ? 'Edit user' : 'New user'}
          onClose={() => setEditingUser(null)}
        >
          <form onSubmit={handleSave}>
            <Field label="Name" htmlFor="name" error={firstFieldError(formErrors, 'name')}>
              <Input
                id="name"
                value={formValues.name}
                onChange={(e) => setFormValues((v) => ({ ...v, name: e.target.value }))}
              />
            </Field>
            <Field label="Email" htmlFor="email" error={firstFieldError(formErrors, 'email')}>
              <Input
                id="email"
                type="email"
                value={formValues.email}
                onChange={(e) => setFormValues((v) => ({ ...v, email: e.target.value }))}
              />
            </Field>
            <Field
              label={editingUser.id ? 'New password (leave blank to keep current)' : 'Password'}
              htmlFor="password"
              error={firstFieldError(formErrors, 'password')}
            >
              <Input
                id="password"
                type="password"
                value={formValues.password}
                onChange={(e) => setFormValues((v) => ({ ...v, password: e.target.value }))}
              />
            </Field>
            <Field label="Role" htmlFor="role" error={firstFieldError(formErrors, 'role')}>
              <Select
                id="role"
                value={formValues.role}
                onChange={(e) => setFormValues((v) => ({ ...v, role: e.target.value }))}
              >
                {Object.entries(ROLE_LABELS).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </Select>
            </Field>
            <div className="modal-actions">
              <Button type="button" variant="secondary" onClick={() => setEditingUser(null)}>
                Cancel
              </Button>
              <Button type="submit" variant="primary" isLoading={isSaving}>
                Save
              </Button>
            </div>
          </form>
        </Modal>
      )}

      {deletingUser && (
        <ConfirmModal
          title="Delete this user?"
          message={`${deletingUser.name} will lose access immediately. This cannot be undone.`}
          confirmLabel="Delete"
          danger
          isLoading={isDeleting}
          onConfirm={handleDelete}
          onCancel={() => setDeletingUser(null)}
        />
      )}
    </div>
  );
}