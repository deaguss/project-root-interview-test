<?php

namespace App\Sockets;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Helpers\JWTHandler;
use Exception;

class TaskSocket implements MessageComponentInterface
{
    protected \SplObjectStorage $clients;
    protected array $userConnections;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $querystring = $conn->httpRequest->getUri()->getQuery();
        parse_str($querystring, $query);

        if (!isset($query['token'])) {
            $conn->close();
            return;
        }

        try {
            $jwt = new JWTHandler();
            $decoded = $jwt->validateToken($query['token']);
            
            $this->clients->attach($conn);
            $this->userConnections[$conn->resourceId] = [
                'id' => $decoded->sub,
                'name' => $decoded->name
            ];

            $this->broadcastPresence();

        } catch (Exception $e) {
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        if (!$data || !isset($data['type'])) return;

        $user = $this->userConnections[$from->resourceId] ?? null;
        if (!$user) return;

        if ($data['type'] === 'typing' || $data['type'] === 'stop_typing') {
            foreach ($this->clients as $client) {
                if ($from !== $client) {
                    $client->send(json_encode([
                        'type' => $data['type'],
                        'taskId' => $data['taskId'] ?? null,
                        'user' => $user
                    ]));
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        unset($this->userConnections[$conn->resourceId]);
        
        $this->broadcastPresence();
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
    }

    public function broadcastPresence()
    {
        $onlineUsers = [];
        foreach ($this->userConnections as $userData) {
            $onlineUsers[$userData['id']] = $userData['name'];
        }

        $msg = json_encode([
            'type' => 'presence',
            'users' => $onlineUsers
        ]);

        foreach ($this->clients as $client) {
            $client->send($msg);
        }
    }

    public function broadcastTasksUpdated()
    {
        $msg = json_encode(['type' => 'tasks_updated']);
        foreach ($this->clients as $client) {
            $client->send($msg);
        }
    }
}
