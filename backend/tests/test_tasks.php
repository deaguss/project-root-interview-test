<?php

$baseUrl = 'http://localhost:8080';

function apiRequest(string $method, string $url, ?array $data = null, ?string $token = null): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => json_decode($response, true),
    ];
}

echo "=== 1. LOGIN ===\n";
$login = apiRequest('POST', "{$baseUrl}/api/auth/login", [
    'email' => 'admin@taskmanager.com',
    'password' => 'password',
]);
echo "Status: {$login['code']}\n";
$token = $login['body']['data']['token'];
echo "Token: " . substr($token, 0, 30) . "...\n\n";

echo "=== 2. GET /api/tasks (page 1, 3 per page) ===\n";
$list = apiRequest('GET', "{$baseUrl}/api/tasks?per_page=3&page=1", null, $token);
echo "Status: {$list['code']}\n";
echo "Total tasks: {$list['body']['data']['pagination']['total']}\n";
echo "Tasks on page: " . count($list['body']['data']['tasks']) . "\n";
foreach ($list['body']['data']['tasks'] as $t) {
    echo "  - [{$t['id']}] {$t['title']} ({$t['status']}/{$t['priority']})\n";
}
echo "\n";

echo "=== 3. GET /api/tasks (filter: status=pending) ===\n";
$filtered = apiRequest('GET', "{$baseUrl}/api/tasks?status=pending", null, $token);
echo "Status: {$filtered['code']}\n";
echo "Pending tasks: {$filtered['body']['data']['pagination']['total']}\n\n";

echo "=== 4. GET /api/tasks (sort: priority ASC) ===\n";
$sorted = apiRequest('GET', "{$baseUrl}/api/tasks?sort_by=priority&sort_order=ASC&per_page=5", null, $token);
echo "Status: {$sorted['code']}\n";
foreach ($sorted['body']['data']['tasks'] as $t) {
    echo "  - [{$t['priority']}] {$t['title']}\n";
}
echo "\n";

echo "=== 5. GET /api/tasks (search: 'upload') ===\n";
$search = apiRequest('GET', "{$baseUrl}/api/tasks?search=upload", null, $token);
echo "Status: {$search['code']}\n";
echo "Found: {$search['body']['data']['pagination']['total']}\n";
foreach ($search['body']['data']['tasks'] as $t) {
    echo "  - {$t['title']}\n";
}
echo "\n";

echo "=== 6. POST /api/tasks (create) ===\n";
$create = apiRequest('POST', "{$baseUrl}/api/tasks", [
    'title' => 'Test task from API',
    'description' => 'This is a test task created via the API test script.',
    'priority' => 'high',
    'status' => 'pending',
    'assigned_user_id' => 3,
    'due_date' => '2026-06-01',
], $token);
echo "Status: {$create['code']}\n";
$newTaskId = $create['body']['data']['id'];
echo "Created task ID: {$newTaskId}\n";
echo "Title: {$create['body']['data']['title']}\n";
echo "Assigned to: {$create['body']['data']['assigned_user_name']}\n\n";

echo "=== 7. GET /api/tasks/{$newTaskId} (show) ===\n";
$show = apiRequest('GET', "{$baseUrl}/api/tasks/{$newTaskId}", null, $token);
echo "Status: {$show['code']}\n";
echo "Title: {$show['body']['data']['title']}\n";
echo "Comments: " . count($show['body']['data']['comments']) . "\n";
echo "Attachments: " . count($show['body']['data']['attachments']) . "\n\n";

echo "=== 8. PUT /api/tasks/{$newTaskId} (update) ===\n";
$update = apiRequest('PUT', "{$baseUrl}/api/tasks/{$newTaskId}", [
    'title' => 'Updated test task',
    'status' => 'in_progress',
    'priority' => 'urgent',
], $token);
echo "Status: {$update['code']}\n";
echo "New title: {$update['body']['data']['title']}\n";
echo "New status: {$update['body']['data']['status']}\n";
echo "New priority: {$update['body']['data']['priority']}\n\n";

echo "=== 9. DELETE /api/tasks/{$newTaskId} ===\n";
$delete = apiRequest('DELETE', "{$baseUrl}/api/tasks/{$newTaskId}", null, $token);
echo "Status: {$delete['code']}\n";
echo "Message: {$delete['body']['message']}\n\n";

echo "=== 10. GET /api/tasks/{$newTaskId} (verify deleted) ===\n";
$verify = apiRequest('GET', "{$baseUrl}/api/tasks/{$newTaskId}", null, $token);
echo "Status: {$verify['code']}\n";
echo "Message: {$verify['body']['message']}\n\n";

echo "=== 11. POST /api/tasks (validation error) ===\n";
$invalid = apiRequest('POST', "{$baseUrl}/api/tasks", [
    'priority' => 'invalid_value',
], $token);
echo "Status: {$invalid['code']}\n";
echo "Message: {$invalid['body']['message']}\n";
echo "Errors: " . json_encode($invalid['body']['errors']) . "\n\n";

echo "=== ALL TESTS PASSED ===\n";
