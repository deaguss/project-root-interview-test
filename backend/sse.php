<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/vendor/autoload.php';

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
    die("Connection failed: " . $e->getMessage());
}

$lastChecked = time();
set_time_limit(0);

while (true) {
    if (connection_aborted()) {
        break;
    }
    
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM tasks WHERE UNIX_TIMESTAMP(updated_at) >= :last_checked');
    $stmt->execute(['last_checked' => $lastChecked]);
    $tasksResult = $stmt->fetch();
    
    $stmtComments = $db->prepare('SELECT COUNT(*) as count FROM task_comments WHERE UNIX_TIMESTAMP(created_at) >= :last_checked');
    $stmtComments->execute(['last_checked' => $lastChecked]);
    $commentsResult = $stmtComments->fetch();
    
    if (($tasksResult && $tasksResult['count'] > 0) || ($commentsResult && $commentsResult['count'] > 0)) {
        $lastChecked = time();
        echo "data: " . json_encode(['type' => 'tasks_updated', 'time' => $lastChecked]) . "\n\n";
    } else {
        echo ": ping\n\n";
    }
    
    ob_flush();
    flush();
    
    sleep(2);
}
