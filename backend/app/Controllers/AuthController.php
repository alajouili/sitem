<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AuthenticationException;
use App\Repositories\AuditLogRepository;
use App\Requests\LoginRequest;
use App\Resources\UserResource;
use App\Services\AuthService;

final class AuthController
{
    private readonly AuthService $auth;
    private readonly AuditLogRepository $auditLogs;

    public function __construct(?AuthService $auth = null, ?AuditLogRepository $auditLogs = null)
    {
        $this->auth = $auth ?? new AuthService();
        $this->auditLogs = $auditLogs ?? new AuditLogRepository();
    }

	public function login(Request $request): Response
	{
		$credentials = LoginRequest::fromRequest($request);
		$result = $this->auth->attempt($credentials->email, $credentials->password);

		$this->auditLogs->create(
			$result['user']->id,
			'auth.login',
			'user',
			$result['user']->id,
			['email' => $result['user']->email]
		);

		return Response::success([
			'token'       => $result['token'],
			'expires_at'  => $result['expires_at'],
			'user'        => UserResource::make($result['user'])->toArray(),
		], 'Login successful.');
	}

	public function logout(Request $request): Response
	{
		$user = $request->getAttribute('user');

		if ($user === null) {
			throw new AuthenticationException('Missing authenticated user.');
		}

		$this->auditLogs->create($user->id, 'auth.logout', 'user', $user->id, ['email' => $user->email]);

		return Response::success(null, 'Logged out.');
	}

	public function me(Request $request): Response
	{
		$user = $request->getAttribute('user');

		if ($user === null) {
			throw new AuthenticationException('Missing authenticated user.');
		}

		return Response::success(UserResource::make($user)->toArray(), 'Authenticated user.');
	}
}
