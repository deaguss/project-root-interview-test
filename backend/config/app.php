<?php

return [
    'jwt_secret' => 'task_manager_secret_key_2026_x7k9m2p4',
    'jwt_algorithm' => 'HS256',
    'jwt_expiry' => 3600,
    'app_url' => 'http://localhost/project-root/backend/public',
    'upload_dir' => __DIR__ . '/../storage/uploads',
    'max_file_size' => 50 * 1024 * 1024,
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'video/mp4',
        'video/webm',
        'application/json',
        'text/plain',
    ],
];
