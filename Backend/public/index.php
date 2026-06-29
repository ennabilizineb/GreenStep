<?php

declare(strict_types=1);
// This is the entry point for all HTTP requests. 
//It is the only file that is publicly accessible.
use App\Database\Database; // 
use App\Middleware\CorsMiddleware;
use App\Routes\Api;
use App\Support\JsonErrorHandler;
use Dotenv\Dotenv; // a tool to read the secrets from .env files and put them into $_ENV
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// 1. Load environment (.env). safeLoad() = don't crash if a var is missing.
Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$settings = require __DIR__ . '/../config/settings.php';

// 2. One PDO connection for the whole request lifecycle.
$db = Database::connect($settings['db']);

// 3. Build the Slim app.
$app = AppFactory::create();

// 4. Parse JSON request bodies -> $request->getParsedBody().
$app->addBodyParsingMiddleware();

// 5. Routing must be registered before the error middleware.
$app->addRoutingMiddleware();

// 6. CORS wraps everything (added last = runs first / outermost).
$app->add(new CorsMiddleware());

// 7. Central error handling -> uniform JSON envelopes instead of HTML stack traces.
$errorMiddleware = $app->addErrorMiddleware(
    (bool) $settings['displayErrorDetails'],
    true,
    true
);
$errorMiddleware->setDefaultErrorHandler(
    new JsonErrorHandler($app->getResponseFactory())
);

// 8. Register all routes (the API contract).
(new Api($db, $settings))($app);

$app->run();
