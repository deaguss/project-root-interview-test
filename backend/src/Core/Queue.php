<?php

namespace App\Core;

class Queue
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function push(string $jobClass, array $data, string $queue = 'default', int $delay = 0): int
    {
        $payload = json_encode([
            'job' => $jobClass,
            'data' => $data,
        ]);

        $availableAt = time() + $delay;

        $stmt = $this->db->prepare(
            'INSERT INTO jobs (queue, payload, attempts, available_at, created_at)
             VALUES (:queue, :payload, 0, :available_at, :created_at)'
        );

        $stmt->execute([
            'queue' => $queue,
            'payload' => $payload,
            'available_at' => $availableAt,
            'created_at' => time(),
        ]);

        return (int) $this->db->lastInsertId();
    }
}
