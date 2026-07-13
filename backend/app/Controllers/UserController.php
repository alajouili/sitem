<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\AuditLogRepository;
use App\Requests\UserRequest;
use App\Resources\UserResource;

final class UserController
{
    private readonly UserRepository $users;
    private readonly AuditLogRepository $auditLogs;

    public function __construct(?UserRepository $users = null, ?AuditLogRepository $auditLogs = null)
    {
        $this->users = $users ?? new UserRepository();
        $this->auditLogs = $auditLogs ?? new AuditLogRepository();
    }

    public function index(Request $request): Response
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 20);
        $role = $request->query('role');

        $result = $this->users->paginate($page, $perPage, $role);

        return Response::success([
            'items' => UserResource::collection($result['data']),
            'total' => $result['total'],
            'page'  => $page,
        ]);
    }

    public function show(Request $request): Response
    {
        $user = $this->findOrFail((int) $request->routeParam('id'));

        return Response::success(UserResource::make($user)->toArray());
    }

    public function store(Request $request): Response
    {
        $validated = UserRequest::forCreate($request);

        if ($this->users->existsByEmail($validated->get('email'))) {
            throw new ValidationException(['email' => ['This email is already registered.']]);
        }

        $user = $this->users->create(
            name: $validated->get('name'),
            email: $validated->get('email'),
            passwordHash: password_hash($validated->get('password'), PASSWORD_BCRYPT),
            role: $validated->get('role'),
        );

        $actor = $request->getAttribute('user');
        $this->auditLogs->create($actor?->id, 'user.created', 'user', $user->id, ['email' => $user->email]);

        return Response::success(UserResource::make($user)->toArray(), 'User created.', 201);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->routeParam('id');
        $this->findOrFail($id);

        $validated = UserRequest::forUpdate($request);
        $fields = $validated->data;

        if (isset($fields['password'])) {
            $fields['password_hash'] = password_hash($fields['password'], PASSWORD_BCRYPT);
            unset($fields['password']);
        }

        $user = $this->users->update($id, $fields);

        $actor = $request->getAttribute('user');
        $this->auditLogs->create($actor?->id, 'user.updated', 'user', $id, ['fields' => array_keys($fields)]);

        return Response::success(UserResource::make($user)->toArray(), 'User updated.');
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->routeParam('id');
        $user = $this->findOrFail($id);

        $this->users->delete($id);

        $actor = $request->getAttribute('user');
        $this->auditLogs->create($actor?->id, 'user.deleted', 'user', $id, ['email' => $user->email]);

        return Response::success(null, 'User deleted.');
    }

    private function findOrFail(int $id): User
    {
        $user = $this->users->findById($id);

        if ($user === null) {
            throw new NotFoundException("User #{$id} not found.");
        }

        return $user;
    }
}