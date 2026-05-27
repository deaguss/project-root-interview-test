# Database Schema

## `users`
| Column | Type | Constraints |
|--------|------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT |
| name | VARCHAR(255) | NOT NULL |
| email | VARCHAR(255) | UNIQUE, NOT NULL |
| password | VARCHAR(255) | NOT NULL |
| role | ENUM | 'admin', 'user', default 'user' |
| created_at | TIMESTAMP | CURRENT_TIMESTAMP |
| updated_at | TIMESTAMP | CURRENT_TIMESTAMP ON UPDATE |

## `tasks`
| Column | Type | Constraints |
|--------|------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT |
| title | VARCHAR(255) | NOT NULL |
| description | TEXT | NULL |
| status | ENUM | 'pending', 'in_progress', 'review', 'completed', 'cancelled' |
| priority | ENUM | 'low', 'medium', 'high', 'urgent' |
| assigned_user_id | INT | FOREIGN KEY (users.id) |
| created_by | INT | FOREIGN KEY (users.id) |
| due_date | DATE | NULL |
| created_at | TIMESTAMP | CURRENT_TIMESTAMP |
| updated_at | TIMESTAMP | CURRENT_TIMESTAMP ON UPDATE |

## `task_attachments`
| Column | Type | Constraints |
|--------|------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT |
| task_id | INT | FOREIGN KEY (tasks.id) ON DELETE CASCADE |
| file_name | VARCHAR(255) | NOT NULL |
| file_path | VARCHAR(255) | NOT NULL |
| file_size | INT | NOT NULL |
| mime_type | VARCHAR(100) | NOT NULL |
| uploaded_at| TIMESTAMP | CURRENT_TIMESTAMP |

## `task_comments`
| Column | Type | Constraints |
|--------|------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT |
| task_id | INT | FOREIGN KEY (tasks.id) ON DELETE CASCADE |
| user_id | INT | FOREIGN KEY (users.id) |
| comment | TEXT | NOT NULL |
| created_at | TIMESTAMP | CURRENT_TIMESTAMP |

## `jobs` (Queue)
| Column | Type | Constraints |
|--------|------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT |
| queue | VARCHAR(255) | NOT NULL |
| payload | TEXT | NOT NULL |
| attempts | INT | DEFAULT 0 |
| reserved_at| INT | NULL |
| available_at| INT | NOT NULL |
| created_at | INT | NOT NULL |

## `failed_jobs`
Stores jobs that have exceeded maximum retry attempts.

## `token_blacklist`
Stores revoked JWTs for immediate invalidation upon logout.
