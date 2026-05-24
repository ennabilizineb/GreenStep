<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\JsonResponse;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Activity Logs — primary CRUD entity #1.
 * Every query is scoped to the authenticated user (from the JWT) so users only
 * ever touch their own rows. Carbon calculation is server-side (see calc note).
 */
final class LogController
{
    public function __construct(private PDO $db)
    {
    }

    /** GET /api/logs -> list the current user's logs */
    public function index(Request $request, Response $response): Response
    {
        $userId = (int) ($request->getAttribute('user')['sub'] ?? 0);

        $stmt = $this->db->prepare(
            'SELECT id, activity_type_id, amount, logged_on
             FROM activity_logs
             WHERE user_id = :uid
             ORDER BY logged_on DESC'
        );
        $stmt->execute([':uid' => $userId]);

        return JsonResponse::success($response, $stmt->fetchAll(), 200);
    }

    /** POST /api/logs -> create a log -> 201 */
    public function store(Request $request, Response $response): Response
    {
        $userId = (int) ($request->getAttribute('user')['sub'] ?? 0);
        $body   = (array) $request->getParsedBody();

        $activityTypeId = filter_var($body['activity_type_id'] ?? null, FILTER_VALIDATE_INT);
        $amount         = filter_var($body['amount'] ?? null, FILTER_VALIDATE_FLOAT);
        $loggedOn       = (string) ($body['logged_on'] ?? date('Y-m-d'));

        if ($activityTypeId === false || $amount === false) {
            return JsonResponse::error($response, 'activity_type_id (int) and amount (number) are required.', 400);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO activity_logs (user_id, activity_type_id, amount, logged_on)
             VALUES (:uid, :type, :amount, :on)'
        );
        $stmt->execute([
            ':uid'    => $userId,
            ':type'   => $activityTypeId,
            ':amount' => $amount,
            ':on'     => $loggedOn,
        ]);

        return JsonResponse::success($response, ['id' => (int) $this->db->lastInsertId()], 201);
    }

    /** PUT /api/logs/{id} -> update one of the user's logs */
    public function update(Request $request, Response $response, array $args): Response
    {
        // TODO (Phase 3 - CRUD): UPDATE ... WHERE id = :id AND user_id = :uid
        //   -> 404 if no row matched (either absent or not owned by this user).
        return JsonResponse::error($response, 'Not implemented yet.', 501);
    }

    /** DELETE /api/logs/{id} -> delete one of the user's logs */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        // TODO (Phase 3 - CRUD): DELETE ... WHERE id = :id AND user_id = :uid
        return JsonResponse::error($response, 'Not implemented yet.', 501);
    }

    /** GET /api/dashboard -> aggregated footprint + streak/badge summary */
    public function dashboard(Request $request, Response $response): Response
    {
        // TODO (Phase 4): join logs x activity_types, multiply amount by kg_co2_per_unit
        //   server-side, group by week/month, attach streak + badge state.
        return JsonResponse::error($response, 'Not implemented yet.', 501);
    }
}
