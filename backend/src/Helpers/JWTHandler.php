<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Core\Database;

class JWTHandler
{
    private string $secret;
    private string $algorithm;
    private int $expiry;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $this->secret = $config['jwt_secret'];
        $this->algorithm = $config['jwt_algorithm'];
        $this->expiry = $config['jwt_expiry'];
    }

    public function generateToken(array $user): string
    {
        $issuedAt = time();
        $payload = [
            'iat' => $issuedAt,
            'exp' => $issuedAt + $this->expiry,
            'jti' => bin2hex(random_bytes(16)),
            'sub' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    public function validateToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));

            if ($this->isBlacklisted($decoded->jti)) {
                return null;
            }

            return $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function blacklistToken(string $token): bool
    {
        $decoded = $this->validateToken($token);
        if (!$decoded) {
            return false;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            'INSERT INTO token_blacklist (token_jti, expires_at) VALUES (:jti, :exp)'
        );

        return $stmt->execute([
            'jti' => $decoded->jti,
            'exp' => date('Y-m-d H:i:s', $decoded->exp),
        ]);
    }

    private function isBlacklisted(string $jti): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM token_blacklist WHERE token_jti = :jti'
        );
        $stmt->execute(['jti' => $jti]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
