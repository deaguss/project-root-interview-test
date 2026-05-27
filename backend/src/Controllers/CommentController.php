<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class CommentController
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request, array $params): void
    {
        $taskId = (int) $params['id'];

        $stmt = $this->db->prepare(
            'SELECT c.*, u.name as user_name 
             FROM task_comments c 
             JOIN users u ON c.user_id = u.id 
             WHERE c.task_id = :task_id 
             ORDER BY c.created_at ASC'
        );
        $stmt->execute(['task_id' => $taskId]);
        $comments = $stmt->fetchAll();

        Response::success($comments);
    }

    public function store(Request $request, array $params): void
    {
        $taskId = (int) $params['id'];
        $commentText = trim($request->input('comment', ''));

        if (empty($commentText)) {
            Response::error('Comment cannot be empty', 422);
        }

        $authUser = $GLOBALS['auth_user'];

        // Verify task exists
        $stmt = $this->db->prepare('SELECT id FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $taskId]);
        if (!$stmt->fetch()) {
            Response::error('Task not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO task_comments (task_id, user_id, comment)
             VALUES (:task_id, :user_id, :comment)'
        );

        $stmt->execute([
            'task_id' => $taskId,
            'user_id' => $authUser->sub,
            'comment' => $commentText
        ]);

        $commentId = (int) $this->db->lastInsertId();

        $stmt = $this->db->prepare(
            'SELECT c.*, u.name as user_name 
             FROM task_comments c 
             JOIN users u ON c.user_id = u.id 
             WHERE c.id = :id'
        );
        $stmt->execute(['id' => $commentId]);
        $newComment = $stmt->fetch();

        Response::success($newComment, 'Comment added', 201);
    }
}
