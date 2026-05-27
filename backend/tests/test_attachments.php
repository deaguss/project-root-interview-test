<?php
$baseUrl = 'http://localhost:8080';

function apiRequest(string $method, string $url, $data = null, ?string $token = null, bool $isFile = false)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = [];
    if (!$isFile && $data) {
        $headers[] = 'Content-Type: application/json';
    }
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($data) {
        if ($isFile) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => $response,
    ];
}

echo "=== 1. LOGIN ===\n";
$login = apiRequest('POST', "{$baseUrl}/api/auth/login", [
    'email' => 'admin@taskmanager.com',
    'password' => 'password',
]);
$loginBody = json_decode($login['body'], true);
$token = $loginBody['data']['token'];
echo "Status: {$login['code']}\n\n";

echo "=== 2. Create Task for Attachment ===\n";
$create = apiRequest('POST', "{$baseUrl}/api/tasks", [
    'title' => 'Task for attachment testing',
], $token);
$task = json_decode($create['body'], true)['data'];
$taskId = $task['id'];
echo "Created task ID: {$taskId}\n\n";

echo "=== 3. Create Dummy File ===\n";
$testFile = __DIR__ . '/test_upload.txt';
file_put_contents($testFile, 'This is a test attachment content.');
echo "Created dummy file\n\n";

echo "=== 4. Upload Attachment ===\n";
$cfile = new CURLFile($testFile, 'text/plain', 'test_upload.txt');
$upload = apiRequest('POST', "{$baseUrl}/api/tasks/{$taskId}/attachments", ['attachment' => $cfile], $token, true);
echo "Status: {$upload['code']}\n";
echo "Response: {$upload['body']}\n\n";

$attachment = json_decode($upload['body'], true)['data'];
$attachmentId = $attachment['id'];

echo "=== 5. Download Attachment ===\n";
$download = apiRequest('GET', "{$baseUrl}/api/attachments/{$attachmentId}/download", null, $token);
echo "Status: {$download['code']}\n";
echo "Content: {$download['body']}\n\n";

echo "=== 6. Delete Attachment ===\n";
$delete = apiRequest('DELETE', "{$baseUrl}/api/attachments/{$attachmentId}", null, $token);
echo "Status: {$delete['code']}\n";
echo "Response: {$delete['body']}\n\n";

@unlink($testFile);
echo "=== ALL TESTS COMPLETED ===\n";
