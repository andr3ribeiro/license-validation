<?php

namespace App\Http;

/**
 * Simple Router for handling API requests
 */
class Router
{
    private array $routes = [];

    /**
     * Register a GET route
     */
    public function get(string $pattern, callable $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    /**
     * Register a POST route
     */
    public function post(string $pattern, callable $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    /**
     * Register a PATCH route
     */
    public function patch(string $pattern, callable $handler): void
    {
        $this->addRoute('PATCH', $pattern, $handler);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $pattern, callable $handler): void
    {
        $this->addRoute('DELETE', $pattern, $handler);
    }

    /**
     * Add a route
     */
    private function addRoute(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    /**
     * Dispatch the request
     */
    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchPath($route['pattern'], $path);
            if ($params !== false) {
                // Set route params in $_GET for easy access
                foreach ($params as $key => $value) {
                    $_GET["_route_param_$key"] = $value;
                }

                // Call the handler
                call_user_func($route['handler']);
                return;
            }
        }

        // No route matched
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Endpoint not found',
            ]
        ]);
    }

    /**
     * Match path against pattern
     * Returns parameters if matched, false otherwise
     * 
     * Pattern examples:
     * - /api/v1/brands/{brandId}
     * - /api/v1/licenses/{licenseId}/activate
     */
    private function matchPath(string $pattern, string $path): array|false
    {
        // Convert pattern to regex
        $regex = preg_replace_callback('/\{([^}]+)\}/', function($matches) {
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $pattern);

        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches)) {
            $params = [];
            foreach ($matches as $key => $value) {
                if (!is_numeric($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return false;
    }
}
