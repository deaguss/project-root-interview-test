<?php

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\TaskController;
use App\Controllers\AttachmentController;
use App\Controllers\CommentController;
use App\Middleware\AuthMiddleware;

$router = new Router();

$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
$router->get('/api/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]);

$router->get('/api/tasks', [TaskController::class, 'index'], [AuthMiddleware::class]);
$router->post('/api/tasks/bulk-status', [TaskController::class, 'bulkUpdate'], [AuthMiddleware::class]);
$router->post('/api/tasks/export', [TaskController::class, 'export'], [AuthMiddleware::class]);
$router->get('/api/tasks/{id}', [TaskController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/tasks', [TaskController::class, 'store'], [AuthMiddleware::class]);
$router->put('/api/tasks/{id}', [TaskController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/api/tasks/{id}', [TaskController::class, 'destroy'], [AuthMiddleware::class]);

$router->get('/api/tasks/{id}/attachments', [AttachmentController::class, 'index'], [AuthMiddleware::class]);
$router->post('/api/tasks/{id}/attachments', [AttachmentController::class, 'upload'], [AuthMiddleware::class]);
$router->get('/api/attachments/{id}/download', [AttachmentController::class, 'download'], [AuthMiddleware::class]);
$router->delete('/api/attachments/{id}', [AttachmentController::class, 'destroy'], [AuthMiddleware::class]);

$router->get('/api/tasks/{id}/comments', [CommentController::class, 'index'], [AuthMiddleware::class]);
$router->post('/api/tasks/{id}/comments', [CommentController::class, 'store'], [AuthMiddleware::class]);

return $router;
