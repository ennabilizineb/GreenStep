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
 * admin token. These endpoints control the data that drives carbon calculations,
 * which is why they are the most tightly gated in the system.
 */
final class AdminController
{
    public function __construct(private PDO $db)
    {
    }

    /** POST /api/admin/tips -> add a tip to the library -> 201 */
    public function createTip(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $title = trim((string) ($body['title'] ?? ''));
        $tbody = trim((string) ($body['body'] ?? ''));
        if ($title === '' || $tbody === '') {
            return JsonResponse::error($response, 'Tip title and body are required.', 400);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO tips (title, body, category) VALUES (:title, :body, :category)'
        );
        $stmt->execute([
            ':title'    => $title,
            ':body'     => $tbody,
            ':category' => (string) ($body['category'] ?? 'general'),
        ]);

        return JsonResponse::success($response, ['id' => (int) $this->db->lastInsertId()], 201);
    }

    /** PUT /api/admin/factors/{id} -> update one emission factor */
    public function updateFactor(Request $request, Response $response, array $args): Response
    {
        $id    = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT);
        $value = filter_var(((array) $request->getParsedBody())['kg_co2_per_unit'] ?? null, FILTER_VALIDATE_FLOAT);

        if ($id === false || $value === false) {
            return JsonResponse::error($response, 'Valid factor id and numeric kg_co2_per_unit are required.', 400);
        }

        $stmt = $this->db->prepare(
            'UPDATE activity_types SET kg_co2_per_unit = :v WHERE id = :id'
        );
        $stmt->execute([':v' => $value, ':id' => $id]);

        if ($stmt->rowCount() === 0) {
            return JsonResponse::error($response, 'Emission factor not found.', 404);
        }

        return JsonResponse::success($response, ['id' => $id, 'kg_co2_per_unit' => $value], 200);
    }
}
