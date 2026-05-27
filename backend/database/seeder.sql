USE task_management;

INSERT INTO users (id, name, email, password, role, created_at, updated_at) VALUES
(1, 'Admin Utama', 'admin@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-01-10 08:00:00', '2026-01-10 08:00:00'),
(2, 'Budi Santoso', 'budi@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', '2026-01-12 09:30:00', '2026-01-12 09:30:00'),
(3, 'Citra Dewi', 'citra@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', '2026-01-15 10:00:00', '2026-01-15 10:00:00'),
(4, 'Deni Firmansyah', 'deni@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', '2026-02-01 08:15:00', '2026-02-01 08:15:00'),
(5, 'Eka Putri', 'eka@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', '2026-02-05 11:00:00', '2026-02-05 11:00:00');

INSERT INTO tasks (id, title, description, status, priority, assigned_user_id, created_by, due_date, created_at, updated_at) VALUES
(1, 'Setup project repository', 'Initialize Git repository and configure CI/CD pipeline for the project.', 'completed', 'high', 3, 1, '2026-02-15', '2026-02-01 09:00:00', '2026-02-14 17:00:00'),
(2, 'Design database schema', 'Create ERD and implement the database schema for the task management system.', 'completed', 'urgent', 4, 2, '2026-02-20', '2026-02-02 10:00:00', '2026-02-18 16:30:00'),
(3, 'Implement user authentication', 'Build JWT-based authentication with login, logout, and token refresh.', 'in_progress', 'high', 3, 1, '2026-03-01', '2026-02-10 08:00:00', '2026-02-25 14:00:00'),
(4, 'Build task CRUD API', 'Develop RESTful endpoints for creating, reading, updating, and deleting tasks.', 'in_progress', 'high', 4, 2, '2026-03-05', '2026-02-12 09:00:00', '2026-02-28 11:00:00'),
(5, 'File upload system', 'Implement secure file upload with validation, size limits, and thumbnail generation.', 'pending', 'medium', 5, 1, '2026-03-10', '2026-02-15 10:00:00', '2026-02-15 10:00:00'),
(6, 'Frontend login page', 'Create responsive login page with form validation and error handling.', 'pending', 'medium', 3, 2, '2026-03-12', '2026-02-18 11:00:00', '2026-02-18 11:00:00'),
(7, 'Task dashboard UI', 'Build the main task dashboard with filtering, sorting, and pagination.', 'pending', 'high', 5, 2, '2026-03-15', '2026-02-20 08:30:00', '2026-02-20 08:30:00'),
(8, 'Real-time notifications', 'Implement WebSocket-based real-time notifications for task updates.', 'pending', 'low', NULL, 1, '2026-03-20', '2026-02-22 09:00:00', '2026-02-22 09:00:00'),
(9, 'Email notification queue', 'Set up background job processing for sending email notifications.', 'pending', 'medium', 4, 1, '2026-03-18', '2026-02-23 10:00:00', '2026-02-23 10:00:00'),
(10, 'API documentation', 'Write comprehensive API documentation using OpenAPI/Swagger format.', 'review', 'medium', 3, 2, '2026-03-08', '2026-02-25 11:00:00', '2026-03-05 15:00:00'),
(11, 'Unit testing backend', 'Write unit tests for all API endpoints and business logic.', 'pending', 'high', 4, 1, '2026-03-22', '2026-02-26 08:00:00', '2026-02-26 08:00:00'),
(12, 'Video streaming feature', 'Implement video upload and adaptive streaming capabilities.', 'pending', 'low', NULL, 2, '2026-04-01', '2026-02-28 09:00:00', '2026-02-28 09:00:00'),
(13, 'Performance optimization', 'Implement caching strategy and optimize database queries.', 'pending', 'medium', 5, 1, '2026-03-25', '2026-03-01 10:00:00', '2026-03-01 10:00:00'),
(14, 'Drag and drop file upload', 'Build drag-and-drop interface for file attachments on the frontend.', 'in_progress', 'medium', 5, 2, '2026-03-10', '2026-03-02 08:00:00', '2026-03-08 14:00:00'),
(15, 'Deploy to production', 'Configure production server, SSL certificates, and deploy the application.', 'pending', 'urgent', NULL, 1, '2026-04-05', '2026-03-05 09:00:00', '2026-03-05 09:00:00');

INSERT INTO task_attachments (id, task_id, file_name, file_path, file_size, mime_type, uploaded_at) VALUES
(1, 2, 'erd-diagram.png', '/uploads/tasks/2/erd-diagram.png', 245760, 'image/png', '2026-02-05 14:00:00'),
(2, 2, 'schema-notes.pdf', '/uploads/tasks/2/schema-notes.pdf', 512000, 'application/pdf', '2026-02-06 09:00:00'),
(3, 5, 'upload-spec.docx', '/uploads/tasks/5/upload-spec.docx', 102400, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-02-16 10:30:00'),
(4, 7, 'dashboard-mockup.png', '/uploads/tasks/7/dashboard-mockup.png', 1048576, 'image/png', '2026-02-21 11:00:00'),
(5, 10, 'api-spec-draft.json', '/uploads/tasks/10/api-spec-draft.json', 35840, 'application/json', '2026-02-26 14:00:00');

INSERT INTO task_comments (id, task_id, user_id, comment, created_at) VALUES
(1, 1, 1, 'Repository has been created. Please set up branch protection rules.', '2026-02-02 10:00:00'),
(2, 1, 3, 'Branch protection configured. Main branch requires PR reviews.', '2026-02-03 14:30:00'),
(3, 2, 2, 'Please follow the naming conventions in our style guide for the schema.', '2026-02-03 11:00:00'),
(4, 2, 4, 'Draft ERD attached for review. Let me know if any changes are needed.', '2026-02-05 15:00:00'),
(5, 2, 2, 'Looks good. Please add indexes on frequently queried columns.', '2026-02-06 10:00:00'),
(6, 3, 1, 'Use JWT with RS256 algorithm. Token expiry should be 1 hour.', '2026-02-11 09:00:00'),
(7, 3, 3, 'Started implementation. Will use firebase/php-jwt library.', '2026-02-12 11:00:00'),
(8, 4, 4, 'Should we implement soft deletes for tasks?', '2026-02-13 10:00:00'),
(9, 4, 2, 'Yes, use soft deletes. Add a deleted_at column.', '2026-02-13 14:00:00'),
(10, 7, 5, 'I have attached the dashboard mockup. Please review before I start coding.', '2026-02-21 11:30:00');
