<?php

use App\Core\Router;
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;

$router = new Router();

$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
$router->get('/api/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]);

return $router;
