<?php

namespace App\Jobs;

use App\Core\Database;
use App\Core\Queue;

class DataExportJob implements JobInterface
{
    public function handle(array $data): void
    {
        $userId = $data['user_id'];
        $userEmail = $data['user_email'];
        
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query('SELECT t.id, t.title, t.status, t.priority, t.due_date, u.name as assigned_to 
                            FROM tasks t LEFT JOIN users u ON t.assigned_user_id = u.id');
        $tasks = $stmt->fetchAll();
        
        $exportDir = __DIR__ . '/../../storage/exports';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $fileName = 'tasks_export_' . time() . '.csv';
        $filePath = $exportDir . '/' . $fileName;
        
        $fp = fopen($filePath, 'w');
        fputcsv($fp, ['ID', 'Title', 'Status', 'Priority', 'Due Date', 'Assigned To']);
        
        foreach ($tasks as $task) {
            fputcsv($fp, [
                $task['id'], 
                $task['title'], 
                $task['status'], 
                $task['priority'], 
                $task['due_date'], 
                $task['assigned_to']
            ]);
        }
        
        fclose($fp);
        
        // Push email job to notify user that export is ready
        $queue = new Queue();
        $queue->push(EmailNotificationJob::class, [
            'to' => $userEmail,
            'subject' => 'Your Data Export is Ready',
            'message' => "Your task export has been generated successfully.\nFile: {$fileName}"
        ]);
    }
}
