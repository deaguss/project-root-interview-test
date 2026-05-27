# Deployment Guide

This guide covers deploying the stack on a standard Linux (Ubuntu) VPS.

## 1. Server Requirements
- Nginx
- PHP 8.1+ (php-fpm, php-mysql, php-gd)
- MySQL 8.0+
- Node.js 18+
- PM2

## 2. Database
1. Create a production database.
2. Import `backend/database/schema.sql`.

## 3. Backend Deployment
1. Upload the `backend` folder to `/var/www/taskmanager-api`.
2. Run `composer install --no-dev --optimize-autoloader`.
3. Configure `config/database.php` with production credentials.
4. Ensure the `storage/` directory is writable by the web server (e.g., `chown -R www-data:www-data storage/`).
5. Configure Nginx:
```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /var/www/taskmanager-api/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }
}
```

## 4. Background Workers (PM2)
Use PM2 to keep the Queue Worker and SSE server alive.
```bash
pm2 start worker.php --interpreter php --name "queue-worker"
pm2 start sse.php --interpreter php --name "sse-server" --port 8081
pm2 save
```

## 5. Frontend Deployment
1. Upload the `frontend` folder to `/var/www/taskmanager-ui`.
2. Run `npm install`.
3. Build the application: `npm run build`.
4. Start via PM2:
```bash
pm2 start npm --name "frontend" -- run start
```
5. Configure Nginx reverse proxy for `ui.yourdomain.com` pointing to `localhost:3000`.
