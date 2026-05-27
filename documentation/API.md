# API Documentation

All endpoints (except login) require a Bearer token in the Authorization header.
`Authorization: Bearer <token>`

Base URL: `http://localhost:8080/api`

## Authentication

### POST /auth/login
Authenticates a user and returns a JWT.
**Body:**
```json
{
  "email": "user@example.com",
  "password": "password"
}
```

### POST /auth/logout
Blacklists the current JWT token.

### GET /auth/me
Returns the authenticated user's details.

## Tasks

### GET /tasks
Retrieves a paginated list of tasks.
**Query Parameters:**
- `page` (int)
- `per_page` (int)
- `status` (string)
- `priority` (string)
- `search` (string)
- `sort_by` (string)
- `sort_order` (ASC/DESC)

### GET /tasks/{id}
Retrieves details of a specific task, including comments and attachments.

### POST /tasks
Creates a new task.
**Body:**
```json
{
  "title": "Task Name",
  "description": "Details",
  "status": "pending",
  "priority": "medium",
  "due_date": "2026-12-31"
}
```

### PUT /tasks/{id}
Updates an existing task.

### DELETE /tasks/{id}
Deletes a task.

### POST /tasks/bulk-status
Updates the status of multiple tasks via queue.
**Body:**
```json
{
  "task_ids": [1, 2, 3],
  "status": "completed"
}
```

### POST /tasks/export
Queues a CSV export job of all tasks.

## Attachments

### POST /tasks/{id}/attachments
Uploads a file attachment.
**Form Data:**
- `attachment`: File

### GET /attachments/{id}/download
Downloads the specified attachment.

### DELETE /attachments/{id}
Deletes an attachment.

## Comments

### GET /tasks/{id}/comments
Retrieves all comments for a task.

### POST /tasks/{id}/comments
Adds a comment to a task.
**Body:**
```json
{
  "comment": "This is a comment"
}
```
