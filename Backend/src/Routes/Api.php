<?php

declare(strict_types=1);

namespace App\Routes;

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ChallengeController;
use App\Controllers\LogController;
use App\Controllers\TipController;
use App\Middleware\JwtAuthMiddleware;
use PDO;
use Slim\App;

/**
 * Defines every route. This file IS the API contract from the proposal (Section III.2),
 * expressed in code. Read top-to-bottom it doubles as living documentation.
 *
 * Role gates:
 *   (none)            -> public
 *   JwtAuthMiddleware -> any authenticated user
 *   JwtAuthMiddleware($jwt, 'leader') -> Community Leader only
 *   JwtAuthMiddleware($jwt, 'admin')  -> Administrator only
 */
final class Api
{
    /** @param array<string,mixed> $settings full settings array */
    public function __construct(
        private PDO $db,
        private array $settings
    ) {
    }

    public function __invoke(App $app): void
    {
        $db  = $this->db;
        $jwt = $this->settings['jwt'];

        $auth      = new AuthController($db, $jwt);
        $logs      = new LogController($db);
        $challenge = new ChallengeController($db);
        $tips      = new TipController($db);
        $admin     = new AdminController($db);

        // --- Health check (handy for deployment verification) ---
        $app->get('/api/health', function ($req, $res) {
            $res->getBody()->write('{"success":true,"data":"ok"}');
            return $res->withHeader('Content-Type', 'application/json')->withStatus(200);
        });

        // --- Public: authentication ---
        $app->post('/api/auth/register', [$auth, 'register']);
        $app->post('/api/auth/login', [$auth, 'login']);

        // --- Authenticated user: activity logs (CRUD #1) ---
        $app->get('/api/logs', [$logs, 'index'])->add(new JwtAuthMiddleware($jwt));
        $app->post('/api/logs', [$logs, 'store'])->add(new JwtAuthMiddleware($jwt));
        $app->put('/api/logs/{id}', [$logs, 'update'])->add(new JwtAuthMiddleware($jwt));
        $app->delete('/api/logs/{id}', [$logs, 'destroy'])->add(new JwtAuthMiddleware($jwt));

        // --- Authenticated user: dashboard + tips ---
        $app->get('/api/dashboard', [$logs, 'dashboard'])->add(new JwtAuthMiddleware($jwt));
        $app->get('/api/tips/daily', [$tips, 'daily'])->add(new JwtAuthMiddleware($jwt));

        // --- Challenges (CRUD #2): list/join = any user; create/edit/delete = leader ---
        $app->get('/api/challenges', [$challenge, 'index'])->add(new JwtAuthMiddleware($jwt));
        $app->post('/api/challenges/{id}/join', [$challenge, 'join'])->add(new JwtAuthMiddleware($jwt));
        $app->post('/api/challenges', [$challenge, 'store'])->add(new JwtAuthMiddleware($jwt, 'leader'));
        $app->put('/api/challenges/{id}', [$challenge, 'update'])->add(new JwtAuthMiddleware($jwt, 'leader'));
        $app->delete('/api/challenges/{id}', [$challenge, 'destroy'])->add(new JwtAuthMiddleware($jwt, 'leader'));

        // --- Administrator: tip library + emission factors ---
        $app->post('/api/admin/tips', [$admin, 'createTip'])->add(new JwtAuthMiddleware($jwt, 'admin'));
        $app->put('/api/admin/factors/{id}', [$admin, 'updateFactor'])->add(new JwtAuthMiddleware($jwt, 'admin'));

        // --- CORS preflight: answer OPTIONS for any path ---
        $app->options('/{routes:.+}', fn ($req, $res) => $res);
    }
}
