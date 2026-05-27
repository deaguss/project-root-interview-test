<?php

namespace App\Jobs;

class EmailNotificationJob implements JobInterface
{
    public function handle(array $data): void
    {
        $to = $data['to'] ?? 'unknown@example.com';
        $subject = $data['subject'] ?? 'Notification';
        $message = $data['message'] ?? '';

        // Simulate sending email
        $logMessage = sprintf(
            "[%s] Sending Email to: %s | Subject: %s | Message: %s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $message
        );

        file_put_contents(__DIR__ . '/../../storage/email.log', $logMessage, FILE_APPEND);
        
        // Simulate delay for email sending
        sleep(1);
    }
}
