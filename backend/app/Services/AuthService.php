<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Env;
use App\Exceptions\AuthenticationException;
use App\Models\User;
use App\Repositories\UserRepository;

/**
 * Handles credential verification and JWT issuing/verification.
 *
 * JWT is implemented by hand (HS256) rather than pulling in
 * firebase/php-jwt, to keep this project dependency-free per the
 * "pure custom PHP" architecture decision. It implements exactly the
 * subset of the JWT spec this app needs: HS256 signing, `exp` claim.
 */
final class AuthService
{
    private UserRepository $users;
    private string $secret;
    private int $ttlSeconds;

    public function __construct(?UserRepository $users = null)
    {
        $this->users = $users ?? new UserRepository();
        $this->secret = (string) Env::get('JWT_SECRET', '');
        $this->ttlSeconds = (int) Env::get('JWT_TTL', 3600);

        if ($this->secret === '' || $this->secret === 'change_me_to_a_long_random_secret') {
            // Not fatal in local/dev, but callers relying on real security
            // should set a strong JWT_SECRET before deploying.
        }
    }

    /**
     * @return array{user: User, token: string, expires_at: int}
     */
    public function attempt(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, $user->passwordHash)) {
            throw new AuthenticationException('Invalid email or password.');
        }

        $expiresAt = time() + $this->ttlSeconds;
        $token = $this->issueToken($user, $expiresAt);

        return ['user' => $user, 'token' => $token, 'expires_at' => $expiresAt];
    }

    public function issueToken(User $user, ?int $expiresAt = null): string
    {
        $expiresAt ??= time() + $this->ttlSeconds;

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = [
            'sub'   => $user->id,
            'email' => $user->email,
            'role'  => $user->role,
            'iat'   => time(),
            'exp'   => $expiresAt,
        ];

        $segments = [
            self::base64UrlEncode(json_encode($header)),
            self::base64UrlEncode(json_encode($payload)),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), $this->secret, true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * @return array<string,mixed> the decoded payload
     */
    public function verifyToken(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new AuthenticationException('Malformed authentication token.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $expectedSignature = hash_hmac(
            'sha256',
            "{$encodedHeader}.{$encodedPayload}",
            $this->secret,
            true
        );

        $providedSignature = self::base64UrlDecode($encodedSignature);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            throw new AuthenticationException('Invalid authentication token.');
        }

        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);

        if (!is_array($payload)) {
            throw new AuthenticationException('Invalid authentication token.');
        }

        if (isset($payload['exp']) && time() >= (int) $payload['exp']) {
            throw new AuthenticationException('Authentication token has expired.');
        }

        return $payload;
    }

    public function userFromToken(string $token): User
    {
        $payload = $this->verifyToken($token);
        $user = isset($payload['sub']) ? $this->users->findById((int) $payload['sub']) : null;

        if ($user === null) {
            throw new AuthenticationException('The user for this token no longer exists.');
        }

        return $user;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $padded = str_pad($data, strlen($data) % 4 === 0 ? strlen($data) : strlen($data) + (4 - strlen($data) % 4), '=');

        return (string) base64_decode(strtr($padded, '-_', '+/'));
    }
}