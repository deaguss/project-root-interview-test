<?php
$ch = curl_init('http://localhost:8080/api/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{"email":"admin@taskmanager.com","password":"password"}');
$r = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);
echo "HTTP {$code}\n";
echo "Content-Type: {$contentType}\n";
echo "Body length: " . strlen($r) . "\n";
echo "Body (first 500 chars):\n";
echo substr($r, 0, 500) . "\n";
echo "\nJSON decode test:\n";
$json = json_decode($r, true);
echo "Decoded: " . ($json ? 'YES' : 'NO - ' . json_last_error_msg()) . "\n";
if ($json) {
    echo "Token: " . substr($json['data']['token'] ?? 'N/A', 0, 30) . "...\n";
}
