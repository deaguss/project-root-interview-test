# Task Manager Backend

This is the PHP API for the Task Management system.

## Setup
1. Run `composer install`
2. Create database and run `mysql -u root -p task_manager < database/schema.sql`
3. Run `mysql -u root -p task_manager < database/seeder.sql`
4. Start the server: `php -S localhost:8080 -t public public/index.php`
5. Start SSE server: `php sse.php`
6. Start Queue worker: `php worker.php`

## Testing
Run unit and integration tests via PHPUnit:
```bash
./vendor/bin/phpunit
```
