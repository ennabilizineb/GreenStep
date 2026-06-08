<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\JsonResponse;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Eco-tips shown to users. One randomised tip per call.
 * Admin-side curation of the tip library lives in AdminController.
 *
 * Schema notes (ERD-aligned):
 *   - tips.category_id is a FK to categories; category name resolved via LEFT JOIN
 *     (LEFT JOIN in case a tip has no category assigned).
 */
final class TipController
{
    public function __construct(private PDO $db)
    {
    }

    /** GET /api/tips/daily -> one random tip */
    public function daily(Request $request, Response $response): Response
    {
        $stmt = $this->db->query(
            'SELECT t.tip_id        AS id,
                    t.title,
                    t.body,
                    c.name          AS category,
                    t.source_url
             FROM   tips t
             LEFT   JOIN categories c ON c.category_id = t.category_id
             ORDER  BY RAND()
             LIMIT  1'
        );
        $tip = $stmt->fetch();

        if (!$tip) {
            return JsonResponse::error($response, 'No tips available.', 404);
        }

        return JsonResponse::success($response, $tip, 200);
    }
}
