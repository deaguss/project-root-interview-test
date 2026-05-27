<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\JWTHandler;

class AuthController
{
    private \PDO $db;
    private JWTHandler $jwt;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->jwt = new JWTHandler();
    }

    public function login(Request $request, array $params): void
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if (!$email || !$password) {
            Response::error('Email and password are required', 422, [
                'email' => !$email ? 'Email is required' : null,
                'password' => !$password ? 'Password is required' : null,
            ]);
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            Response::error('Invalid credentials', 401);
        }

        $token = $this->jwt->generateToken($user);

        Response::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
        ], 'Login successful');
    }

    public function logout(Request $request, array $params): void
    {
        $token = $request->getBearerToken();

        if (!$token) {
            Response::error('Token not provided', 401);
        }

        $this->jwt->blacklistToken($token);

        Response::success(null, 'Logout successful');
    }

    public function me(Request $request, array $params): void
    {
        $authUser = $GLOBALS['auth_user'];

        $stmt = $this->db->prepare('SELECT id, name, email, role, created_at, updated_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $authUser->sub]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error('User not found', 404);
        }

        $user['id'] = (int) $user['id'];

        Response::success($user, 'User info retrieved');
    }
}
