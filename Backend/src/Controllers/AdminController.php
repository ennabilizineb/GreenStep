<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\JsonResponse;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Administrator-only operations. Every route using this controller is wrapped with
 * JwtAuthMiddleware('admin'), so reaching these methods already implies a verified
 * admin token.
 *
 * Schema notes (ERD-aligned):
 *   - tips.category_id  : FK to categories (resolved by category name string from request)
 *   - tips.added_by     : FK to users (taken from JWT sub claim)
 *   - activity_types PK : activity_type_id
 */
final class AdminController
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * POST /api/admin/tips -> add a tip to the library -> 201
     *
     * Expected body: { title, body, category }
     * "category" is the human-readable category name (e.g. "transport").
     * The backend resolves it to category_id; if not found, the tip is stored
     * with category_id = NULL rather than rejecting the request.
     */
    public function createTip(Request $request, Response $response): Response
    {
        $adminId = (int) ($request->getAttribute('user')['sub'] ?? 0);
        $body    = (array) $request->getParsedBody();

        $title = trim((string) ($body['title'] ?? ''));
        $tbody = trim((string) ($body['body'] ?? ''));

        if ($title === '' || $tbody === '') {
            return JsonResponse::error($response, 'Tip title and body are required.', 400);
        }

        // Resolve category name → category_id (nullable — unknown categories stored as NULL)
        $categoryId = null;
        $categoryName = trim((string) ($body['category'] ?? ''));
        if ($categoryName !== '') {
            $catStmt = $this->db->prepare(
                'SELECT category_id FROM categories WHERE name = :name LIMIT 1'
            );
            $catStmt->execute([':name' => $categoryName]);
            $catId = $catStmt->fetchColumn();
            if ($catId !== false) {
                $categoryId = (int) $catId;
            }
        }

        $stmt = $this->db->prepare(
            'INSERT INTO tips (category_id, added_by, title, body, source_url)
             VALUES (:category_id, :added_by, :title, :body, :source_url)'
        );
        $stmt->execute([
            ':category_id' => $categoryId,
            ':added_by'    => $adminId,
            ':title'       => $title,
            ':body'        => $tbody,
            ':source_url'  => trim((string) ($body['source_url'] ?? '')) ?: null,
        ]);

        return JsonResponse::success($response, ['id' => (int) $this->db->lastInsertId()], 201);
    }

    /**
     * GET /api/admin/factors -> list all emission factors with category names.
     * Lets the admin see every factor ID and current value before updating.
     */
    public function listFactors(Request $request, Response $response): Response
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

    /** PUT /api/admin/factors/{id} -> update one emission factor */
    public function updateFactor(Request $request, Response $response, array $args): Response
    {
        $id    = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT);
        $value = filter_var(
            ((array) $request->getParsedBody())['kg_co2_per_unit'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        if ($id === false || $value === false) {
            return JsonResponse::error($response, 'Valid factor id and numeric kg_co2_per_unit are required.', 400);
        }

        // ERD PK is activity_type_id
        $stmt = $this->db->prepare(
            'UPDATE activity_types SET kg_co2_per_unit = :v WHERE activity_type_id = :id'
        );
        $stmt->execute([':v' => $value, ':id' => $id]);

        if ($stmt->rowCount() === 0) {
            return JsonResponse::error($response, 'Emission factor not found.', 404);
        }

        return JsonResponse::success($response, ['id' => $id, 'kg_co2_per_unit' => $value], 200);
    }
}
