<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\JsonResponse;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Activity Types — the emission-factor catalogue.
 * Read-only for any authenticated user (any role).
 * The frontend log-creation form uses this list to populate the activity-type dropdown.
 * Admin writes to this catalogue via PUT /api/admin/factors/{id} (AdminController).
 *
 * Schema notes (ERD-aligned):
 *   - activity_types PK  : activity_type_id  (aliased as "id" in response)
 *   - category name      : resolved via JOIN categories on activity_types.category_id
 */
final class ActivityTypeController
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * GET /api/activity-types
     *
     * Returns all activity types ordered by category then name, e.g.:
     * [
     *   { "id": 1, "category": "energy",    "name": "Electricity", "unit": "kWh",  "kg_co2_per_unit": "0.2120" },
     *   { "id": 2, "category": "food",      "name": "Mixed Meal",  "unit": "meal", "kg_co2_per_unit": "1.2000" },
     *   ...
     * ]
     */
    public function index(Request $request, Response $response): Response
    {
        $stmt = $this->db->query(
            'SELECT at.activity_type_id AS id,
                    c.name              AS category,
                    at.name,
                    at.unit,
                    at.kg_co2_per_unit
             FROM   activity_types at
             JOIN   categories c ON c.category_id = at.category_id
             ORDER  BY c.name, at.name'
        );

        return JsonResponse::success($response, $stmt->fetchAll(), 200);
    }
}
