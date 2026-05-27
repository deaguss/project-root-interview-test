# Task Management Platform

A robust, real-time task management system. Built with a custom MVC PHP backend and a Next.js React frontend.

## Prerequisites
- PHP >= 7.4
- MySQL / MariaDB (Port 3306)
- Composer
- Node.js >= 18
- NPM

## Installation

### 1. Database Setup
1. Create a MySQL database named `task_manager`.
2. Import the schema and seed data:
   ```bash
   mysql -u root -p task_manager < backend/database/schema.sql
   mysql -u root -p task_manager < backend/database/seeder.sql
   ```

### 2. Backend Setup
1. Navigate to the `backend` directory:
   ```bash
   cd backend
   ```
2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy configuration:
   ```bash
   cp config/database.example.php config/database.php
   ```
   *(Update credentials inside `config/database.php` if needed)*
4. Start the API server:
   ```bash
   php -S localhost:8080 -t public public/index.php
   ```
5. Start the SSE server (for real-time updates):
   ```bash
   php -S localhost:8081 sse.php
   ```
6. Start the Queue worker:
   ```bash
   php worker.php
   ```

### 3. Frontend Setup
1. Navigate to the `frontend` directory:
   ```bash
   cd frontend
   ```
2. Install dependencies:
   ```bash
   npm install
   ```
3. Start the development server:
   ```bash
   npm run dev
   ```

### 4. Access
- Frontend: `http://localhost:3000`
- API Backend: `http://localhost:8080`
- Test Credentials: `admin@taskmanager.com` / `password`
