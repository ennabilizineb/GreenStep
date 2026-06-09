<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\BadgeEvaluator;
use App\Support\JsonResponse;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Badge catalogue — the gamification "Badges → View all" screen.
 * Returns every badge with the current user's earned/locked status.
 * Awarding logic lives in App\Support\BadgeEvaluator (shared with the dashboard).
 */
final class BadgeController
{
    public function __construct(private PDO $db)
    {
    }

    /** GET /api/badges -> every badge with this user's earned status */
    public function index(Request $request, Response $response): Response
    {
        $userId    = (int) ($request->getAttribute('user')['sub'] ?? 0);
        $evaluator = new BadgeEvaluator($this->db);

        // Re-evaluate first so a badge the user just qualified for shows as earned,
        // even if they have not opened the dashboard since crossing the threshold.
        $evaluator->evaluateAndAward($userId, $evaluator->currentStreak($userId));

        return JsonResponse::success($response, $evaluator->listForUser($userId), 200);
    }
}
