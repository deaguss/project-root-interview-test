<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\Database;
use App\Helpers\JWTHandler;

class FileUploadTest extends TestCase
{
    private $db;
    private $token;
    private $taskId;

    protected function setUp(): void
    {
        $this->db = Database::getInstance()->getConnection();
        
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role) VALUES ('File User', 'file@test.com', 'hash', 'user')");
        $stmt->execute();
        $userId = $this->db->lastInsertId();

        $stmt = $this->db->prepare("INSERT INTO tasks (title, status, priority, created_by) VALUES ('File Task', 'pending', 'medium', :uid)");
        $stmt->execute(['uid' => $userId]);
        $this->taskId = $this->db->lastInsertId();

        $jwt = new JWTHandler();
        $this->token = $jwt->generateToken([
            'id' => $userId,
            'name' => 'File User',
            'email' => 'file@test.com',
            'role' => 'user'
        ]);
    }

    public function testSuccessfulFileUpload()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'dummy content');

        $cfile = new \CURLFile($tempFile, 'text/plain', 'test.txt');

        $ch = curl_init("http://localhost:8080/api/tasks/{$this->taskId}/attachments");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['attachment' => $cfile]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        unlink($tempFile);

        $this->assertEquals(201, $httpCode);
        
        $data = json_decode($response, true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals('test.txt', $data['data']['file_name']);
    }

    public function testDisallowedFileTypeIsRejected()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.exe';
        file_put_contents($tempFile, 'MZ executable content');

        $cfile = new \CURLFile($tempFile, 'application/x-msdownload', 'test.exe');

        $ch = curl_init("http://localhost:8080/api/tasks/{$this->taskId}/attachments");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['attachment' => $cfile]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        unlink($tempFile);

        $this->assertEquals(422, $httpCode);
        $data = json_decode($response, true);
        $this->assertStringContainsString('File type not allowed', $data['message']);
    }
}
