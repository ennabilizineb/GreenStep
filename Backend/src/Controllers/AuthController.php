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
 * Schema: users.role_id (FK → roles). Role name is resolved via JOIN so the
 * JWT payload always carries the human-readable role string ('user'|'leader'|'admin'),
 * keeping JwtAuthMiddleware unchanged.
 */
final class AuthController
{
    /** @param array<string,mixed> $jwtConfig */
    public function __construct(
        private PDO $db,
        private array $jwtConfig
    ) {
    }

    /** POST /api/auth/register -> 201 Created */
    public function register(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $name     = trim((string) ($body['name'] ?? ''));
        $email    = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            return JsonResponse::error(
                $response,
                'Invalid input: name required, valid email required, password min 8 chars.',
                400
            );
        }

        // Reject duplicate email
        $check = $this->db->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            return JsonResponse::error($response, 'Email is already registered.', 409);
        }

        // Resolve the default 'user' role_id from the roles table
        $roleStmt = $this->db->prepare('SELECT role_id FROM roles WHERE name = :name LIMIT 1');
        $roleStmt->execute([':name' => 'user']);
        $roleId = (int) ($roleStmt->fetchColumn() ?? 0);

        if ($roleId === 0) {
            return JsonResponse::error($response, 'Server configuration error: roles not seeded.', 500);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $insert = $this->db->prepare(
            'INSERT INTO users (role_id, name, email, password_hash, joined_at)
             VALUES (:role_id, :name, :email, :hash, NOW())'
        );
        $insert->execute([
            ':role_id' => $roleId,
            ':name'    => $name,
            ':email'   => $email,
            ':hash'    => $hash,
        ]);

        return JsonResponse::success(
            $response,
            ['id' => (int) $this->db->lastInsertId(), 'name' => $name, 'email' => $email, 'role' => 'user'],
            201
        );
    }

    /** POST /api/auth/login -> 200 OK with JWT */
    public function login(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $email    = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $password === '') {
            return JsonResponse::error($response, 'Email and password are required.', 400);
        }

        // JOIN roles to resolve the role name string for the JWT payload
        $stmt = $this->db->prepare(
            'SELECT u.user_id       AS id,
                    u.name,
                    u.email,
                    u.password_hash,
                    r.name          AS role
             FROM   users u
             JOIN   roles r ON r.role_id = u.role_id
             WHERE  u.email = :email
             LIMIT  1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Same generic message for wrong email or wrong password (prevents user enumeration)
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
