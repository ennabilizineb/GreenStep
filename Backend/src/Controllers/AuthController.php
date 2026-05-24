<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\JsonResponse;
use Firebase\JWT\JWT;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Authentication: registration, login, JWT issuance.
 * This is the first end-to-end feature and the template the other controllers follow.
 */
final class AuthController
{
    /** @param array<string,mixed> $jwtConfig */
    public function __construct(
        private PDO $db,
        private array $jwtConfig
    ) {
    }

    /** POST /api/auth/register  -> 201 Created */
    public function register(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $name     = trim((string) ($body['name'] ?? ''));
        $email    = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        // --- Server-side validation (never trust the client) ---
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            return JsonResponse::error(
                $response,
                'Invalid input: name required, valid email required, password min 8 chars.',
                400
            );
        }

        // --- Reject duplicate email (prepared statement) ---
        $check = $this->db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            return JsonResponse::error($response, 'Email is already registered.', 409);
        }

        // --- Hash with PHP default (bcrypt/Argon2), then persist ---
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $insert = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, role, joined_at)
             VALUES (:name, :email, :hash, :role, NOW())'
        );
        $insert->execute([
            ':name'  => $name,
            ':email' => $email,
            ':hash'  => $hash,
            ':role'  => 'user',
        ]);

        return JsonResponse::success(
            $response,
            ['id' => (int) $this->db->lastInsertId(), 'name' => $name, 'email' => $email, 'role' => 'user'],
            201
        );
    }

    /** POST /api/auth/login  -> 200 OK with JWT */
    public function login(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $email    = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $password === '') {
            return JsonResponse::error($response, 'Email and password are required.', 400);
        }

        $stmt = $this->db->prepare(
            'SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Same generic message whether the email or the password is wrong (no user enumeration).
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return JsonResponse::error($response, 'Invalid credentials.', 401);
        }

        $token = $this->issueToken((int) $user['id'], $user['role']);

        return JsonResponse::success($response, [
            'token' => $token,
            'user'  => [
                'id'    => (int) $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ], 200);
    }

    private function issueToken(int $userId, string $role): string
    {
        $now = time();
        $payload = [
            'iss'  => $this->jwtConfig['issuer'],
            'iat'  => $now,
            'exp'  => $now + $this->jwtConfig['ttl'],
            'sub'  => $userId,
            'role' => $role,
        ];

        return JWT::encode($payload, $this->jwtConfig['secret'], $this->jwtConfig['alg']);
    }
}
