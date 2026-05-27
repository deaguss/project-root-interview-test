<?php

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\TaskController;
use App\Middleware\AuthMiddleware;

$router = new Router();

$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
$router->get('/api/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]);

$router->get('/api/tasks', [TaskController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/tasks/{id}', [TaskController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/tasks', [TaskController::class, 'store'], [AuthMiddleware::class]);
$router->put('/api/tasks/{id}', [TaskController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/api/tasks/{id}', [TaskController::class, 'destroy'], [AuthMiddleware::class]);

return $router;
