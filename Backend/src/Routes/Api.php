<?php
// --((This file is the API router. It maps every URL to the right controller function.))
declare(strict_types=1);

namespace App\Routes;

// All the controllers - each one handles a specific group of features
use App\Controllers\ActivityTypeController; // handles the list of activities a user can log (car, bus, meal, etc.)
use App\Controllers\AdminController;        // handles admin-only actions: managing tips and CO2 emission factors
use App\Controllers\AuthController;         // handles register and login
use App\Controllers\BadgeController;        // handles showing the user their earned and locked badges
use App\Controllers\ChallengeController;    // handles community challenges: listing, joining, creating
use App\Controllers\LogController;          // handles activity logs AND the dashboard calculation
use App\Controllers\TipController;          // handles the daily random eco tip
use App\Middleware\JwtAuthMiddleware;        // the security guard - checks if the user has a valid JWT token before allowing access
use PDO;                                    // the database connection object passed into the controllers
use Slim\App;                               // the Slim framework app that we register all routes onto

/**
 *  API contract from the proposal (Section III.2),
 *
 * Role gates:
 *   (none)            -> public
 *   JwtAuthMiddleware -> any authenticated user
 *   JwtAuthMiddleware($jwt, 'leader') -> Community Leader only
 *   JwtAuthMiddleware($jwt, 'admin')  -> Administrator only
 */

// --((This class holds all the routes. When called, it registers every URL the backend listens to.))
final class Api
{
    /** @param array<string,mixed> $settings full settings array */
    // 1. I receive the database connection and the app settings (including JWT secret) when this class is created.
    public function __construct(
        private PDO $db,
        private array $settings
    ) {
    }

    // --((This method is called from index.php. It registers all the routes onto the Slim app.))
    public function __invoke(App $app): void
    {
        // 2. I grab the database connection and the JWT settings to pass them into the controllers.
        $db  = $this->db;
        $jwt = $this->settings['jwt'];

        // 3. I create one instance of each controller, giving them the database connection they need.
        $auth          = new AuthController($db, $jwt);         // needs jwt too, to issue tokens on login
        $logs          = new LogController($db);                // handles logs and the dashboard
        $challenge     = new ChallengeController($db);          // handles challenges
        $tips          = new TipController($db);                // handles daily tips
        $admin         = new AdminController($db);              // handles admin actions
        $activityTypes = new ActivityTypeController($db);       // handles the activity type list
        $badges        = new BadgeController($db);              // handles badges



        // --((Public routes: no login needed. Anyone can call these.))

        // 4. I register a health check route so we can confirm the server is running.
        $app->get('/api/health', function ($req, $res) {
            $res->getBody()->write('{"success":true,"data":"ok"}');
            return $res->withHeader('Content-Type', 'application/json')->withStatus(200);
        });

        // 5. I register the register and login routes. These are the only two public routes.
        // No JWT token is needed here because the user does not have one yet.
        $app->post('/api/auth/register', [$auth, 'register']);
        $app->post('/api/auth/login', [$auth, 'login']);



        // --((Protected routes: the user must send a valid JWT token in the Authorization header.))
        // --((The .add(new JwtAuthMiddleware($jwt)) at the end of each route is the security guard.))
        // 6. I register the activity types route so the frontend can populate the log form dropdown.
        $app->get('/api/activity-types', [$activityTypes, 'index'])->add(new JwtAuthMiddleware($jwt));


        // 7. I register the full CRUD for activity logs.
        // GET = list my logs, POST = create a new log, PUT = edit a log, DELETE = remove a log.
        $app->get('/api/logs', [$logs, 'index'])->add(new JwtAuthMiddleware($jwt));
        $app->post('/api/logs', [$logs, 'store'])->add(new JwtAuthMiddleware($jwt));
        $app->put('/api/logs/{id}', [$logs, 'update'])->add(new JwtAuthMiddleware($jwt));
        $app->delete('/api/logs/{id}', [$logs, 'destroy'])->add(new JwtAuthMiddleware($jwt));



        // 8. I register the dashboard route.
        // It calculates today's CO2, the 7-day chart, streak count, and badges all at once.
        $app->get('/api/dashboard', [$logs, 'dashboard'])->add(new JwtAuthMiddleware($jwt));
        // 9. I register the daily tip route - returns one random eco tip from the database.
        $app->get('/api/tips/daily', [$tips, 'daily'])->add(new JwtAuthMiddleware($jwt));
        // Extends the PR1 contract: powers the "Badges -> View all" gamification screen.
        // 10. I register the badges route - returns all badges with earned/locked status for this user.
        $app->get('/api/badges', [$badges, 'index'])->add(new JwtAuthMiddleware($jwt));



        // --((Challenge routes: listing and joining = any logged-in user.))
        // --((Creating, editing, deleting = leader role only.))
        // 11. I register the challenge list and detail routes for all users.
        $app->get('/api/challenges', [$challenge, 'index'])->add(new JwtAuthMiddleware($jwt));
        // Extends the PR1 contract: challenge detail + collective-progress leaderboard.
        $app->get('/api/challenges/{id}', [$challenge, 'show'])->add(new JwtAuthMiddleware($jwt));
        // 12. I register the join route - adds the user to the Challenge_Member table.
        $app->post('/api/challenges/{id}/join', [$challenge, 'join'])->add(new JwtAuthMiddleware($jwt));
        // 13. I register the leader-only routes for managing challenges.
        // The second argument 'leader' tells the middleware to also check the role claim inside the JWT.
        $app->post('/api/challenges', [$challenge, 'store'])->add(new JwtAuthMiddleware($jwt, 'leader'));
        $app->put('/api/challenges/{id}', [$challenge, 'update'])->add(new JwtAuthMiddleware($jwt, 'leader'));
        $app->delete('/api/challenges/{id}', [$challenge, 'destroy'])->add(new JwtAuthMiddleware($jwt, 'leader'));




        // --((Admin-only routes: only accounts with role = 'admin' can reach these.))
        // 14. I register the admin routes for managing the tip library and CO2 emission factors.
        // The 'admin' argument means only admin-role JWT tokens are accepted here.
        $app->post('/api/admin/tips', [$admin, 'createTip'])->add(new JwtAuthMiddleware($jwt, 'admin'));
        $app->get('/api/admin/factors', [$admin, 'listFactors'])->add(new JwtAuthMiddleware($jwt, 'admin'));
        $app->put('/api/admin/factors/{id}', [$admin, 'updateFactor'])->add(new JwtAuthMiddleware($jwt, 'admin'));

        // 15. I register a catch-all OPTIONS route to answer CORS preflight requests from the browser.
        // The browser sends an OPTIONS request before every cross-origin POST/PUT/DELETE to check permissions.
        $app->options('/{routes:.+}', fn ($req, $res) => $res);
    }
}
