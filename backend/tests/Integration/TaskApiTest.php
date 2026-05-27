<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\Database;
use App\Helpers\JWTHandler;

class TaskApiTest extends TestCase
{
    private $db;
    private $token;
    private $userId;

    protected function setUp(): void
    {
        $this->db = Database::getInstance()->getConnection();
        
        $this->db->exec("DELETE FROM tasks");
        $this->db->exec("DELETE FROM users");

        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role) VALUES ('Test User', 'test@test.com', 'hash', 'user')");
        $stmt->execute();
        $this->userId = $this->db->lastInsertId();

        $jwt = new JWTHandler();
        $this->token = $jwt->generateToken([
            'id' => $this->userId,
            'name' => 'Test User',
            'email' => 'test@test.com',
            'role' => 'user'
        ]);
    }

    private function makeRequest(string $method, string $uri, array $body = [])
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->token;
        
        ob_start();
        
        $request = new \App\Core\Request();
        
        $reflection = new \ReflectionClass($request);
        $bodyProperty = $reflection->getProperty('data');
        $bodyProperty->setAccessible(true);
        $bodyProperty->setValue($request, $body);

        $router = require __DIR__ . '/../../src/Routes/api.php';
    }

    public function testCreateTaskSuccessfully()
    {
        $ch = curl_init('http://localhost:8080/api/tasks');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'title' => 'Integration Test Task',
            'description' => 'Testing API',
            'status' => 'pending',
            'priority' => 'high'
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(201, $httpCode);
        
        $data = json_decode($response, true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals('Integration Test Task', $data['data']['title']);
        $this->assertEquals($this->userId, $data['data']['created_by']);
    }

    public function testUnauthorizedAccessIsBlocked()
    {
        $ch = curl_init('http://localhost:8080/api/tasks');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(401, $httpCode);
    }
}
