<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class TaskController
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request, array $params): void
    {
        $page = max(1, (int) $request->getParam('page', 1));
        $perPage = min(100, max(1, (int) $request->getParam('per_page', 10)));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $bindings = [];

        $status = $request->getParam('status');
        if ($status) {
            $where[] = 't.status = :status';
            $bindings['status'] = $status;
        }

        $priority = $request->getParam('priority');
        if ($priority) {
            $where[] = 't.priority = :priority';
            $bindings['priority'] = $priority;
        }

        $assignedUserId = $request->getParam('assigned_user_id');
        if ($assignedUserId !== null) {
            $where[] = 't.assigned_user_id = :assigned_user_id';
            $bindings['assigned_user_id'] = (int) $assignedUserId;
        }

        $createdBy = $request->getParam('created_by');
        if ($createdBy) {
            $where[] = 't.created_by = :created_by';
            $bindings['created_by'] = (int) $createdBy;
        }

        $search = $request->getParam('search');
        if ($search) {
            $where[] = '(t.title LIKE :search OR t.description LIKE :search_desc)';
            $bindings['search'] = "%{$search}%";
            $bindings['search_desc'] = "%{$search}%";
        }

        $dueDateFrom = $request->getParam('due_date_from');
        if ($dueDateFrom) {
            $where[] = 't.due_date >= :due_date_from';
            $bindings['due_date_from'] = $dueDateFrom;
        }

        $dueDateTo = $request->getParam('due_date_to');
        if ($dueDateTo) {
            $where[] = 't.due_date <= :due_date_to';
            $bindings['due_date_to'] = $dueDateTo;
        }

        $whereClause = '';
        if (!empty($where)) {
            $whereClause = 'WHERE ' . implode(' AND ', $where);
        }

        $allowedSorts = ['title', 'status', 'priority', 'due_date', 'created_at', 'updated_at'];
        $sortBy = $request->getParam('sort_by', 'created_at');
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $sortOrder = strtoupper($request->getParam('sort_order', 'DESC'));
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }

        $countSql = "SELECT COUNT(*) FROM tasks t {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($bindings);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT t.*, 
                       a.name AS assigned_user_name, 
                       c.name AS creator_name
                FROM tasks t
                LEFT JOIN users a ON t.assigned_user_id = a.id
                LEFT JOIN users c ON t.created_by = c.id
                {$whereClause}
                ORDER BY t.{$sortBy} {$sortOrder}
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $tasks = $stmt->fetchAll();

        foreach ($tasks as &$task) {
            $task['id'] = (int) $task['id'];
            $task['assigned_user_id'] = $task['assigned_user_id'] ? (int) $task['assigned_user_id'] : null;
            $task['created_by'] = (int) $task['created_by'];
        }

        $lastPage = max(1, (int) ceil($total / $perPage));

        Response::success([
            'tasks' => $tasks,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'has_next' => $page < $lastPage,
                'has_prev' => $page > 1,
            ],
        ], 'Tasks retrieved');
    }

    public function show(Request $request, array $params): void
    {
        $task = $this->findTask($params['id']);

        $commentsStmt = $this->db->prepare(
            'SELECT tc.*, u.name AS user_name 
             FROM task_comments tc 
             JOIN users u ON tc.user_id = u.id 
             WHERE tc.task_id = :task_id 
             ORDER BY tc.created_at ASC'
        );
        $commentsStmt->execute(['task_id' => $task['id']]);
        $task['comments'] = $commentsStmt->fetchAll();

        $attachStmt = $this->db->prepare(
            'SELECT * FROM task_attachments WHERE task_id = :task_id ORDER BY uploaded_at DESC'
        );
        $attachStmt->execute(['task_id' => $task['id']]);
        $task['attachments'] = $attachStmt->fetchAll();

        Response::success($task, 'Task retrieved');
    }

    public function store(Request $request, array $params): void
    {
        $errors = $this->validateTask($request);
        if (!empty($errors)) {
            Response::error('Validation failed', 422, $errors);
        }

        $authUser = $GLOBALS['auth_user'];
        $assignedUserId = $request->input('assigned_user_id');

        $stmt = $this->db->prepare(
            'INSERT INTO tasks (title, description, status, priority, assigned_user_id, created_by, due_date)
             VALUES (:title, :description, :status, :priority, :assigned_user_id, :created_by, :due_date)'
        );

        $stmt->execute([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'status' => $request->input('status', 'pending'),
            'priority' => $request->input('priority', 'medium'),
            'assigned_user_id' => $assignedUserId,
            'created_by' => $authUser->sub,
            'due_date' => $request->input('due_date'),
        ]);

        $taskId = $this->db->lastInsertId();
        $task = $this->findTask($taskId);

        if ($assignedUserId) {
            $userStmt = $this->db->prepare('SELECT email, name FROM users WHERE id = :id');
            $userStmt->execute(['id' => $assignedUserId]);
            $assignedUser = $userStmt->fetch();

            if ($assignedUser) {
                $queue = new \App\Core\Queue();
                $queue->push(\App\Jobs\EmailNotificationJob::class, [
                    'to' => $assignedUser['email'],
                    'subject' => "New Task Assigned: {$task['title']}",
                    'message' => "Hello {$assignedUser['name']},\n\nYou have been assigned a new task: {$task['title']}.\nPriority: {$task['priority']}\nDue Date: {$task['due_date']}"
                ]);
            }
        }

        Response::success($task, 'Task created', 201);
    }

    public function update(Request $request, array $params): void
    {
        $task = $this->findTask($params['id']);

        $fields = [];
        $bindings = ['id' => $task['id']];

        $updatable = ['title', 'description', 'status', 'priority', 'assigned_user_id', 'due_date'];
        
        $newAssignedUser = null;

        foreach ($updatable as $field) {
            $value = $request->input($field);
            if ($value !== null) {
                $fields[] = "{$field} = :{$field}";
                $bindings[$field] = $value;
                
                if ($field === 'assigned_user_id' && $value != $task['assigned_user_id']) {
                    $newAssignedUser = $value;
                }
            }
        }

        if (empty($fields)) {
            Response::error('No fields to update', 422);
        }

        $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);

        $updatedTask = $this->findTask($params['id']);

        if ($newAssignedUser) {
            $userStmt = $this->db->prepare('SELECT email, name FROM users WHERE id = :id');
            $userStmt->execute(['id' => $newAssignedUser]);
            $assignedUser = $userStmt->fetch();

            if ($assignedUser) {
                $queue = new \App\Core\Queue();
                $queue->push(\App\Jobs\EmailNotificationJob::class, [
                    'to' => $assignedUser['email'],
                    'subject' => "Task Assigned: {$updatedTask['title']}",
                    'message' => "Hello {$assignedUser['name']},\n\nYou have been assigned to task: {$updatedTask['title']}.\nStatus: {$updatedTask['status']}"
                ]);
            }
        }

        Response::success($updatedTask, 'Task updated');
    }

    public function destroy(Request $request, array $params): void
    {
        $task = $this->findTask($params['id']);

        $stmt = $this->db->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $task['id']]);

        Response::success(null, 'Task deleted');
    }

    public function bulkUpdate(Request $request, array $params): void
    {
        $taskIds = $request->input('task_ids');
        $status = $request->input('status');

        if (empty($taskIds) || !is_array($taskIds)) {
            Response::error('task_ids array is required', 422);
        }

        if (!$status) {
            Response::error('status is required', 422);
        }

        $queue = new \App\Core\Queue();
        $queue->push(\App\Jobs\BulkTaskStatusJob::class, [
            'task_ids' => $taskIds,
            'status' => $status
        ]);

        Response::success(null, 'Bulk status update job queued', 202);
    }

    public function export(Request $request, array $params): void
    {
        $authUser = $GLOBALS['auth_user'];
        
        $queue = new \App\Core\Queue();
        $queue->push(\App\Jobs\DataExportJob::class, [
            'user_id' => $authUser->sub,
            'user_email' => $authUser->email
        ]);

        Response::success(null, 'Data export job queued. You will receive an email when it is ready.', 202);
    }

    private function findTask($id): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, a.name AS assigned_user_name, c.name AS creator_name
             FROM tasks t
             LEFT JOIN users a ON t.assigned_user_id = a.id
             LEFT JOIN users c ON t.created_by = c.id
             WHERE t.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $task = $stmt->fetch();

        if (!$task) {
            Response::error('Task not found', 404);
        }

        $task['id'] = (int) $task['id'];
        $task['assigned_user_id'] = $task['assigned_user_id'] ? (int) $task['assigned_user_id'] : null;
        $task['created_by'] = (int) $task['created_by'];

        return $task;
    }

    private function validateTask(Request $request): array
    {
        $errors = [];

        if (!$request->input('title')) {
            $errors['title'] = 'Title is required';
        } elseif (strlen($request->input('title')) > 255) {
            $errors['title'] = 'Title must not exceed 255 characters';
        }

        $validStatuses = ['pending', 'in_progress', 'review', 'completed', 'cancelled'];
        $status = $request->input('status');
        if ($status && !in_array($status, $validStatuses)) {
            $errors['status'] = 'Invalid status. Allowed: ' . implode(', ', $validStatuses);
        }

        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        $priority = $request->input('priority');
        if ($priority && !in_array($priority, $validPriorities)) {
            $errors['priority'] = 'Invalid priority. Allowed: ' . implode(', ', $validPriorities);
        }

        $assignedUserId = $request->input('assigned_user_id');
        if ($assignedUserId) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE id = :id');
            $stmt->execute(['id' => $assignedUserId]);
            if ((int) $stmt->fetchColumn() === 0) {
                $errors['assigned_user_id'] = 'Assigned user not found';
            }
        }

        $dueDate = $request->input('due_date');
        if ($dueDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $errors['due_date'] = 'Due date must be in YYYY-MM-DD format';
        }

        return $errors;
    }
}
