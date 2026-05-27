<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Sockets\TaskSocket;
use App\Core\Database;

$taskSocket = new TaskSocket();

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            $taskSocket
        )
    ),
    8081
);

$db = Database::getInstance()->getConnection();

$lastCheck = time();

$server->loop->addPeriodicTimer(1, function () use ($taskSocket, $db, &$lastCheck) {
    $dateStr = date('Y-m-d H:i:s', $lastCheck);
    
    $stmt1 = $db->prepare("SELECT COUNT(*) FROM tasks WHERE updated_at > :d");
    $stmt1->execute(['d' => $dateStr]);
    $tasksChanged = $stmt1->fetchColumn() > 0;

    $stmt2 = $db->prepare("SELECT COUNT(*) FROM task_comments WHERE created_at > :d");
    $stmt2->execute(['d' => $dateStr]);
    $commentsChanged = $stmt2->fetchColumn() > 0;

    if ($tasksChanged || $commentsChanged) {
        $taskSocket->broadcastTasksUpdated();
        $lastCheck = time();
    }
});

echo "WebSocket server running on port 8081...\n";
$server->run();
