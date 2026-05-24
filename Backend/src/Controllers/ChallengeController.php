<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\JsonResponse;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Community Challenges — primary CRUD entity #2.
 * Create/update/delete are gated to the Community Leader role at the route layer;
 * any authenticated user may list and join.
 */
final class ChallengeController
{
    public function __construct(private PDO $db)
    {
    }

    /** GET /api/challenges -> list all challenges */
    public function index(Request $request, Response $response): Response
    {
        $stmt = $this->db->query(
            'SELECT id, name, description, start_date, end_date, target_co2_reduction
             FROM challenges ORDER BY start_date DESC'
        );

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

        $stmt = $this->db->prepare(
            'INSERT INTO challenges (name, description, start_date, end_date, target_co2_reduction)
             VALUES (:name, :desc, :start, :end, :target)'
        );
        $stmt->execute([
            ':name'   => $name,
            ':desc'   => (string) ($body['description'] ?? ''),
            ':start'  => (string) ($body['start_date'] ?? date('Y-m-d')),
            ':end'    => (string) ($body['end_date'] ?? date('Y-m-d')),
            ':target' => filter_var($body['target_co2_reduction'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0,
        ]);

        return JsonResponse::success($response, ['id' => (int) $this->db->lastInsertId()], 201);
    }

    /** PUT /api/challenges/{id} -> update (Community Leader) */
    public function update(Request $request, Response $response, array $args): Response
    {
        // TODO (Phase 3 - CRUD)
        return JsonResponse::error($response, 'Not implemented yet.', 501);
    }

    /** DELETE /api/challenges/{id} -> delete (Community Leader) */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        // TODO (Phase 3 - CRUD)
        return JsonResponse::error($response, 'Not implemented yet.', 501);
    }

    /** POST /api/challenges/{id}/join -> current user joins -> 201 */
    public function join(Request $request, Response $response, array $args): Response
    {
        // TODO (Phase 4): INSERT INTO challenge_members (challenge_id, user_id)
        //   guard against duplicate membership (unique key or pre-check).
        return JsonResponse::error($response, 'Not implemented yet.', 501);
    }
}
