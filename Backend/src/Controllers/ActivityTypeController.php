<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\JsonResponse;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Activity Types — the emission-factor catalogue.
 * Read-only for authenticated users (any role).
 * The frontend log form uses this list to populate the activity-type dropdown.
 * Admin writes to this catalogue via PUT /api/admin/factors/{id} (AdminController).
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
     *   { "id": 1, "category": "energy",    "name": "Electricity", "unit": "kWh",  "kg_co2_per_unit": 0.2120 },
     *   { "id": 2, "category": "food",      "name": "Mixed Meal",  "unit": "meal", "kg_co2_per_unit": 1.2000 },
     *   ...
     * ]
     */
    public function index(Request $request, Response $response): Response
    {
        $stmt = $this->db->query(
            'SELECT id, category, name, unit, kg_co2_per_unit
             FROM   activity_types
             ORDER  BY category, name'
        );

        return JsonResponse::success($response, $stmt->fetchAll(), 200);
    }
}
