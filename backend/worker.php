<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

$config = require __DIR__ . '/config/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['database'],
    $config['charset']
);

try {
    $db = new \PDO($dsn, $config['username'], $config['password'], [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    die("Worker failed to connect to database: " . $e->getMessage() . "\n");
}

echo "Worker started. Waiting for jobs...\n";

while (true) {
    try {
        $db->beginTransaction();

        $now = time();
        $stmt = $db->prepare(
            'SELECT * FROM jobs 
             WHERE reserved_at IS NULL AND available_at <= :now 
             ORDER BY id ASC LIMIT 1 FOR UPDATE SKIP LOCKED'
        );
        $stmt->execute(['now' => $now]);
        $job = $stmt->fetch();

        if ($job) {
            $updateStmt = $db->prepare('UPDATE jobs SET reserved_at = :now, attempts = attempts + 1 WHERE id = :id');
            $updateStmt->execute(['now' => $now, 'id' => $job['id']]);
            $db->commit();

            echo "[{$now}] Processing Job ID {$job['id']}\n";

            try {
                $payload = json_decode($job['payload'], true);
                $jobClass = $payload['job'];
                $data = $payload['data'];

                if (!class_exists($jobClass)) {
                    throw new \Exception("Job class {$jobClass} not found");
                }

                $jobInstance = new $jobClass();
                if (!$jobInstance instanceof \App\Jobs\JobInterface) {
                    throw new \Exception("Job class {$jobClass} does not implement JobInterface");
                }

                $jobInstance->handle($data);

                // Success, remove job
                $deleteStmt = $db->prepare('DELETE FROM jobs WHERE id = :id');
                $deleteStmt->execute(['id' => $job['id']]);
                
                echo "[{$now}] Job ID {$job['id']} completed successfully.\n";

            } catch (\Throwable $e) {
                echo "[{$now}] Job ID {$job['id']} failed: " . $e->getMessage() . "\n";
                
                if ($job['attempts'] >= 3) {
                    // Move to failed jobs
                    $failStmt = $db->prepare(
                        'INSERT INTO failed_jobs (queue, payload, exception) VALUES (:queue, :payload, :exception)'
                    );
                    $failStmt->execute([
                        'queue' => $job['queue'],
                        'payload' => $job['payload'],
                        'exception' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                    ]);
                    
                    $deleteStmt = $db->prepare('DELETE FROM jobs WHERE id = :id');
                    $deleteStmt->execute(['id' => $job['id']]);
                    echo "[{$now}] Job ID {$job['id']} permanently failed.\n";
                } else {
                    // Release back to queue with delay
                    $releaseStmt = $db->prepare('UPDATE jobs SET reserved_at = NULL, available_at = :available_at WHERE id = :id');
                    $releaseStmt->execute(['available_at' => time() + 60, 'id' => $job['id']]);
                }
            }
        } else {
            $db->commit();
            // No jobs, sleep
            sleep(2);
        }
    } catch (\Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "Worker Error: " . $e->getMessage() . "\n";
        sleep(5);
    }
}
