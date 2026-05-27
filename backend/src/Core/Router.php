<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, array $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function resolve(Request $request): void
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        if ($method === 'OPTIONS') {
            Response::json(null, 204);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchRoute($route['path'], $uri);
            if ($params === false) {
                continue;
            }

            foreach ($route['middleware'] as $middlewareClass) {
                $middleware = new $middlewareClass();
                $middleware->handle($request);
            }

            [$controllerClass, $methodName] = $route['handler'];
            $controller = new $controllerClass();
            $controller->$methodName($request, $params);
            return;
        }

        Response::error('Route not found', 404);
    }

    private function matchRoute(string $routePath, string $uri)
    {
        $routeParts = explode('/', trim($routePath, '/'));
        $uriParts = explode('/', trim($uri, '/'));

        if (count($routeParts) !== count($uriParts)) {
            return false;
        }

        $params = [];

        for ($i = 0; $i < count($routeParts); $i++) {
            if (preg_match('/^\{(\w+)\}$/', $routeParts[$i], $matches)) {
                $params[$matches[1]] = $uriParts[$i];
            } elseif ($routeParts[$i] !== $uriParts[$i]) {
                return false;
            }
        }

        return $params;
    }
}
