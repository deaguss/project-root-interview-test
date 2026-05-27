<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\JWTHandler;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        $token = $request->getBearerToken();

        if (!$token) {
            Response::error('Token not provided', 401);
        }

        $jwt = new JWTHandler();
        $decoded = $jwt->validateToken($token);

        if (!$decoded) {
            Response::error('Invalid or expired token', 401);
        }

        $GLOBALS['auth_user'] = $decoded;
    }
}
