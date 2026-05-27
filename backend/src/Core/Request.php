<?php

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private array $params;
    private array $body;
    private array $headers;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->uri = $this->parseUri();
        $this->params = $_GET;
        $this->body = $this->parseBody();
        $this->headers = $this->parseHeaders();
    }

    private function parseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'];
        $basePath = '/project-root/backend/public';
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        $uri = strtok($uri, '?');
        return '/' . trim($uri, '/');
    }

    private function parseBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (stripos($contentType, 'multipart/form-data') !== false) {
            return $_POST;
        }

        return $_POST;
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getParam(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    public function getAllParams(): array
    {
        return $this->params;
    }

    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->body;
    }

    public function getHeader(string $key, $default = null): ?string
    {
        $key = strtoupper(str_replace('-', '-', $key));
        return $this->headers[$key] ?? $default;
    }

    public function getBearerToken(): ?string
    {
        $auth = $this->getHeader('AUTHORIZATION');
        if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public function files(string $key): array
    {
        if (!isset($_FILES[$key])) {
            return [];
        }

        $file = $_FILES[$key];
        if (!is_array($file['name'])) {
            return [$file];
        }

        $files = [];
        for ($i = 0; $i < count($file['name']); $i++) {
            $files[] = [
                'name' => $file['name'][$i],
                'type' => $file['type'][$i],
                'tmp_name' => $file['tmp_name'][$i],
                'error' => $file['error'][$i],
                'size' => $file['size'][$i],
            ];
        }
        return $files;
    }
}
