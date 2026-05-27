<?php

namespace App\Jobs;

use App\Core\Database;

class BulkTaskStatusJob implements JobInterface
{
    public function handle(array $data): void
    {
        $taskIds = $data['task_ids'] ?? [];
        $status = $data['status'] ?? 'pending';
        
        if (empty($taskIds)) {
            return;
        }

        $db = Database::getInstance()->getConnection();
        
        // Simulate heavy bulk operation processing
        sleep(2);

        $placeholders = str_repeat('?,', count($taskIds) - 1) . '?';
        $sql = "UPDATE tasks SET status = ? WHERE id IN ($placeholders)";
        
        $params = array_merge([$status], $taskIds);
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }
}
