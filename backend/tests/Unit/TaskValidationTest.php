<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\TaskController;
use App\Core\Request;

class TaskValidationTest extends TestCase
{
    private $controller;
    private $validateMethod;

    protected function setUp(): void
    {
        $dbMock = $this->createMock(\PDO::class);
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetchColumn')->willReturn(1);
        $dbMock->method('prepare')->willReturn($stmtMock);

        $this->controller = new TaskController();
        
        $reflection = new \ReflectionClass(TaskController::class);
        
        $dbProperty = $reflection->getProperty('db');
        $dbProperty->setAccessible(true);
        $dbProperty->setValue($this->controller, $dbMock);

        $this->validateMethod = $reflection->getMethod('validateTask');
        $this->validateMethod->setAccessible(true);
    }

    public function testMissingTitleReturnsError()
    {
        $request = $this->createMock(Request::class);
        $request->method('input')->willReturn(null);

        $errors = $this->validateMethod->invoke($this->controller, $request);

        $this->assertArrayHasKey('title', $errors);
        $this->assertEquals('Title is required', $errors['title']);
    }

    public function testInvalidStatusReturnsError()
    {
        $request = $this->createMock(Request::class);
        $request->method('input')->willReturnMap([
            ['title', null, 'Valid Title'],
            ['status', null, 'invalid_status'],
            ['priority', null, null],
            ['assigned_user_id', null, null],
            ['due_date', null, null]
        ]);

        $errors = $this->validateMethod->invoke($this->controller, $request);

        $this->assertArrayHasKey('status', $errors);
        $this->assertStringContainsString('Invalid status', $errors['status']);
    }

    public function testValidDataReturnsNoErrors()
    {
        $request = $this->createMock(Request::class);
        $request->method('input')->willReturnMap([
            ['title', null, 'Valid Title'],
            ['status', 'pending', 'pending'],
            ['priority', 'medium', 'high'],
            ['assigned_user_id', null, 1],
            ['due_date', null, '2026-12-31']
        ]);

        $errors = $this->validateMethod->invoke($this->controller, $request);

        $this->assertEmpty($errors);
    }
}
