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

    /** GET /api/logs -> list the current user's logs with calculated kg_co2 per row */
    public function index(Request $request, Response $response): Response
    {
        $userId = (int) ($request->getAttribute('user')['sub'] ?? 0);

        $stmt = $this->db->prepare(
            'SELECT al.id,
                    al.activity_type_id,
                    at.category,
                    at.name                                       AS activity_name,
                    at.unit,
                    al.amount,
                    ROUND(al.amount * at.kg_co2_per_unit, 4)     AS kg_co2,
                    al.logged_on
             FROM   activity_logs al
             JOIN   activity_types at ON at.id = al.activity_type_id
             WHERE  al.user_id = :uid
             ORDER  BY al.logged_on DESC, al.id DESC'
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

        // Verify the activity_type exists before inserting
        $typeCheck = $this->db->prepare('SELECT id FROM activity_types WHERE id = :id LIMIT 1');
        $typeCheck->execute([':id' => $activityTypeId]);
        if (!$typeCheck->fetch()) {
            return JsonResponse::error($response, 'activity_type_id does not reference a known activity type.', 422);
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
        $userId = (int) ($request->getAttribute('user')['sub'] ?? 0);
        $id     = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT);

        if ($id === false) {
            return JsonResponse::error($response, 'Invalid log ID.', 400);
        }

        $body           = (array) $request->getParsedBody();
        $activityTypeId = filter_var($body['activity_type_id'] ?? null, FILTER_VALIDATE_INT);
        $amount         = filter_var($body['amount'] ?? null, FILTER_VALIDATE_FLOAT);
        $loggedOn       = (string) ($body['logged_on'] ?? date('Y-m-d'));

        if ($activityTypeId === false || $amount === false) {
            return JsonResponse::error($response, 'activity_type_id (int) and amount (number) are required.', 400);
        }

        // Ownership check: WHERE id = :id AND user_id = :uid ensures users can only edit their own rows.
        $stmt = $this->db->prepare(
            'UPDATE activity_logs
             SET    activity_type_id = :type,
                    amount           = :amount,
                    logged_on        = :on
             WHERE  id = :id AND user_id = :uid'
        );
        $stmt->execute([
            ':type'   => $activityTypeId,
            ':amount' => $amount,
            ':on'     => $loggedOn,
            ':id'     => $id,
            ':uid'    => $userId,
        ]);

        if ($stmt->rowCount() === 0) {
            return JsonResponse::error($response, 'Log entry not found or access denied.', 404);
        }

        return JsonResponse::success($response, ['id' => $id], 200);
    }

    /** DELETE /api/logs/{id} -> delete one of the user's logs */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $userId = (int) ($request->getAttribute('user')['sub'] ?? 0);
        $id     = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT);

        if ($id === false) {
            return JsonResponse::error($response, 'Invalid log ID.', 400);
        }

        // Ownership check in the WHERE clause — no separate SELECT needed.
        $stmt = $this->db->prepare(
            'DELETE FROM activity_logs WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);

        if ($stmt->rowCount() === 0) {
            return JsonResponse::error($response, 'Log entry not found or access denied.', 404);
        }

        return JsonResponse::success($response, null, 200);
    }

    /**
     * GET /api/dashboard -> aggregated footprint stats for the authenticated user.
     *
     * Response shape (all kg_co2 values are floats, rounded to 4 decimal places):
     * {
     *   "today_kg_co2":      15.2000,
     *   "yesterday_kg_co2":  17.9000,
     *   "streak_days":       7,
     *   "joined_challenges": 2,
     *   "week": [
     *     {"date": "2026-06-01", "kg_co2": 18.3},
     *     ...                                          // up to 7 entries (last 7 days)
     *   ],
     *   "by_category": [
     *     {"category": "transport", "kg_co2": 80.0},
     *     ...                                          // last 30 days, positive factors only
     *   ]
     * }
     */
    public function dashboard(Request $request, Response $response): Response
    {
        $userId = (int) ($request->getAttribute('user')['sub'] ?? 0);

        // --- 1. Today vs yesterday ---
        $todayStmt = $this->db->prepare(
            'SELECT
                 ROUND(SUM(CASE WHEN al.logged_on = CURDATE()
                                THEN al.amount * at.kg_co2_per_unit ELSE 0 END), 4) AS today_kg,
                 ROUND(SUM(CASE WHEN al.logged_on = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                                THEN al.amount * at.kg_co2_per_unit ELSE 0 END), 4) AS yesterday_kg
             FROM activity_logs al
             JOIN activity_types at ON at.id = al.activity_type_id
             WHERE al.user_id = :uid
               AND al.logged_on >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)'
        );
        $todayStmt->execute([':uid' => $userId]);
        $todayRow = $todayStmt->fetch();

        $todayKg     = (float) ($todayRow['today_kg'] ?? 0);
        $yesterdayKg = (float) ($todayRow['yesterday_kg'] ?? 0);

        // --- 2. Last 7 days for the weekly chart ---
        $weekStmt = $this->db->prepare(
            'SELECT   al.logged_on                                          AS date,
                      ROUND(SUM(al.amount * at.kg_co2_per_unit), 4)        AS kg_co2
             FROM     activity_logs al
             JOIN     activity_types at ON at.id = al.activity_type_id
             WHERE    al.user_id = :uid
               AND    al.logged_on >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY al.logged_on
             ORDER BY al.logged_on ASC'
        );
        $weekStmt->execute([':uid' => $userId]);
        $weekRows = $weekStmt->fetchAll();

        // --- 3. Last 30 days grouped by category (positive emission factors only) ---
        $catStmt = $this->db->prepare(
            'SELECT   at.category,
                      ROUND(SUM(al.amount * at.kg_co2_per_unit), 4) AS kg_co2
             FROM     activity_logs al
             JOIN     activity_types at ON at.id = al.activity_type_id
             WHERE    al.user_id = :uid
               AND    al.logged_on >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
               AND    at.kg_co2_per_unit > 0
             GROUP BY at.category
             ORDER BY kg_co2 DESC'
        );
        $catStmt->execute([':uid' => $userId]);
        $byCategory = $catStmt->fetchAll();

        // --- 4. Number of challenges the user has joined ---
        $challengeStmt = $this->db->prepare(
            'SELECT COUNT(*) AS cnt FROM challenge_members WHERE user_id = :uid'
        );
        $challengeStmt->execute([':uid' => $userId]);
        $joinedChallenges = (int) ($challengeStmt->fetchColumn() ?? 0);

        // --- 5. Consecutive-day streak (today or yesterday counts as day 1) ---
        $streakStmt = $this->db->prepare(
            'SELECT DISTINCT logged_on
             FROM   activity_logs
             WHERE  user_id = :uid
             ORDER  BY logged_on DESC'
        );
        $streakStmt->execute([':uid' => $userId]);
        $loggedDates = $streakStmt->fetchAll(PDO::FETCH_COLUMN);

        $streak   = 0;
        $expected = new \DateTime('today');
        foreach ($loggedDates as $dateStr) {
            $day = new \DateTime($dateStr);
            if ($day->format('Y-m-d') === $expected->format('Y-m-d')) {
                $streak++;
                $expected->modify('-1 day');
            } elseif ($day < $expected) {
                // Gap found — streak is broken.
                break;
            }
        }

        return JsonResponse::success($response, [
            'today_kg_co2'      => $todayKg,
            'yesterday_kg_co2'  => $yesterdayKg,
            'streak_days'       => $streak,
            'joined_challenges' => $joinedChallenges,
            'week'              => $weekRows,
            'by_category'       => $byCategory,
        ], 200);
    }
}
