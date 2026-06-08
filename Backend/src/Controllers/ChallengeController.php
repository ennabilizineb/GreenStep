<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\JsonResponse;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Community Challenges — primary CRUD entity #2.
 *
 * Schema notes (ERD-aligned):
 *   - challenges PK       : challenge_id  (aliased as "id" in all responses)
 *   - challenge_members   : composite PK (challenge_id, user_id) — no separate id column
 *
 * Create/update/delete are gated to the Community Leader role at the route layer;
 * any authenticated user may list and join.
 */
final class ChallengeController
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * GET /api/challenges -> list all challenges.
     *
     * Each row includes:
     *   - member_count : total participants
     *   - is_joined    : 1 if the calling user has joined, 0 otherwise
     */
    public function index(Request $request, Response $response): Response
    {
        $userId = (int) ($request->getAttribute('user')['sub'] ?? 0);

        $stmt = $this->db->prepare(
            'SELECT c.challenge_id                                                           AS id,
                    c.name,
                    c.description,
                    c.start_date,
                    c.end_date,
                    c.target_co2_reduction,
                    COUNT(cm.user_id)                                                        AS member_count,
                    CAST(MAX(CASE WHEN cm.user_id = :uid THEN 1 ELSE 0 END) AS UNSIGNED)     AS is_joined
             FROM   challenges c
             LEFT   JOIN challenge_members cm ON cm.challenge_id = c.challenge_id
             GROUP  BY c.challenge_id
             ORDER  BY c.start_date DESC'
        );
        $stmt->execute([':uid' => $userId]);

        return JsonResponse::success($response, $stmt->fetchAll(), 200);
    }

    /** POST /api/challenges -> create (Community Leader) -> 201 */
    public function store(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            return JsonResponse::error($response, 'Challenge name is required.', 400);
        }

        $target = filter_var($body['target_co2_reduction'] ?? 0, FILTER_VALIDATE_FLOAT);
        if ($target === false) {
            return JsonResponse::error($response, 'target_co2_reduction must be a number.', 400);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO challenges (name, description, start_date, end_date, target_co2_reduction)
             VALUES (:name, :desc, :start, :end, :target)'
        );
        $stmt->execute([
            ':name'   => $name,
            ':desc'   => (string) ($body['description'] ?? ''),
            ':start'  => (string) ($body['start_date'] ?? date('Y-m-d')),
            ':end'    => (string) ($body['end_date'] ?? date('Y-m-d')),
            ':target' => $target,
        ]);

        return JsonResponse::success($response, ['id' => (int) $this->db->lastInsertId()], 201);
    }

    /** PUT /api/challenges/{id} -> partial update (Community Leader) */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false) {
            return JsonResponse::error($response, 'Invalid challenge ID.', 400);
        }

        $body    = (array) $request->getParsedBody();
        $sets    = [];
        $params  = [':id' => $id];

        if (array_key_exists('name', $body)) {
            $name = trim((string) $body['name']);
            if ($name === '') {
                return JsonResponse::error($response, 'Challenge name cannot be empty.', 400);
            }
            $sets[':name'] = $name;
        }

        if (array_key_exists('description', $body)) {
            $sets[':desc'] = (string) $body['description'];
        }

        if (array_key_exists('target_co2_reduction', $body)) {
            $target = filter_var($body['target_co2_reduction'], FILTER_VALIDATE_FLOAT);
            if ($target === false) {
                return JsonResponse::error($response, 'target_co2_reduction must be a number.', 400);
            }
            $sets[':target'] = $target;
        }

        if (empty($sets)) {
            return JsonResponse::error($response, 'Provide at least one of: name, description, target_co2_reduction.', 400);
        }

        $columnMap = [':name' => 'name', ':desc' => 'description', ':target' => 'target_co2_reduction'];
        $setClauses = [];
        foreach ($sets as $placeholder => $value) {
            $setClauses[]        = $columnMap[$placeholder] . ' = ' . $placeholder;
            $params[$placeholder] = $value;
        }

        $stmt = $this->db->prepare(
            'UPDATE challenges SET ' . implode(', ', $setClauses) . ' WHERE challenge_id = :id'
        );
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            return JsonResponse::error($response, 'Challenge not found.', 404);
        }

        return JsonResponse::success($response, ['id' => $id], 200);
    }

    /** DELETE /api/challenges/{id} -> delete (Community Leader) */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $id = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false) {
            return JsonResponse::error($response, 'Invalid challenge ID.', 400);
        }

        $stmt = $this->db->prepare('DELETE FROM challenges WHERE challenge_id = :id');
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            return JsonResponse::error($response, 'Challenge not found.', 404);
        }

        return JsonResponse::success($response, null, 200);
    }

    /** POST /api/challenges/{id}/join -> current user joins -> 201 */
    public function join(Request $request, Response $response, array $args): Response
    {
        $userId = (int) ($request->getAttribute('user')['sub'] ?? 0);
        $id     = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT);

        if ($id === false) {
            return JsonResponse::error($response, 'Invalid challenge ID.', 400);
        }

        // Verify challenge exists before attempting to join
        $check = $this->db->prepare('SELECT 1 FROM challenges WHERE challenge_id = :id LIMIT 1');
        $check->execute([':id' => $id]);
        if (!$check->fetch()) {
            return JsonResponse::error($response, 'Challenge not found.', 404);
        }

        try {
            // Composite PK (challenge_id, user_id) enforces uniqueness at the DB level
            $stmt = $this->db->prepare(
                'INSERT INTO challenge_members (challenge_id, user_id) VALUES (:cid, :uid)'
            );
            $stmt->execute([':cid' => $id, ':uid' => $userId]);
        } catch (PDOException $e) {
            // SQLSTATE 23000 = integrity constraint violation (duplicate PK)
            if ((string) $e->getCode() === '23000') {
                return JsonResponse::error($response, 'You have already joined this challenge.', 409);
            }
            throw $e;
        }

        return JsonResponse::success($response, ['challenge_id' => $id], 201);
    }
}
