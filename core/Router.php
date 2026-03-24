<?php

declare(strict_types=1);

namespace Core;

use InvalidArgumentException;

/**
 * Router - Matches an HTTP request to a registered handler.
 *
 * Supports:
 * - Methods: GET, POST, PUT, DELETE, PATCH
 * - URL parameters: /players/{id}, /matches/{matchId}/stats
 * - Handler call: [ClassName::class, 'methodName'] or callable
 *
 * Route registration example:
 * $router->get('/players', [PlayerController::class, 'index']);
 * $router->get('/players/{id}', [PlayerController::class, 'show']);
 */
final class Router
{
    /** @var array<string, array<array{pattern: string, handler: callable|array}>> */
    private array $routes = [];

    private const array ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

    // --- Route registration ---

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function patch(string $path, callable|array $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    // --- Dispatching ---

    /**
     * Resolves the current HTTP request and invokes the matching handler.
     * If the route is not found, returns 404.
     */
    public function dispatch(): void
    {
        $method = $this->resolveMethod();
        $uri = $this->resolveUri();

        foreach ($this->routes[$method] ?? [] as $route) {
            $params = $this->match($route['pattern'], $uri);
            
            if ($params !== null) {
                $this->callHandler($route['handler'], $params);
                return;
            }
        }
        
        Response::notFound('Route not found.');
    }

    // --- Private helpers ---
    private function add(string $method, string $path, callable|array $handler): void
    {
        if (!in_array($method, self::ALLOWED_METHODS, strict: true)) {
            throw new InvalidArgumentException("[ERROR]: Unsupported HTTP method: {$method}");
        }

        $this->routes[$method][] = [
            'pattern' => $this->buildPattern($path),
            'handler' => $handler
        ];
    }

    /**
     * Converts a path with placeholders to a regular expression.
     * /players/{id} → /players/(?P<id>[^/]+)
     */
    private function buildPattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Gets the HTTP method from the request.
     * Supports the _method override for HTML forms (PUT/DELETE via POST + hidden input).
     */
    private function resolveMethod(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // HTML form doesn't support PUT/DELETE - we allow override via POST body
        if ($method === 'POST') {
            $override = strtoupper($_POST['_method'] ?? '');

            if (in_array($override, ['PUT', 'DELETE', 'PATCH'], strict: true)) {
                return $override;
            }
        }

        return $method;
    }

    /**
     * Gets and normalizes a URI (no query string, no trailing slash except for root).
     */
    private function resolveUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        return $uri;
    }


    /**
     * Attempts to match a URI against a pattern.
     * Returns an array of named parameters, or null if no match exists.
     *
     * @return array<string, string>|null
     */
    private function match(mixed $pattern, string $uri): ?array
    {
        if (preg_match($pattern, $uri, $matches) !== 1) {
            return null;
        }

        // Extracting only named groups (ignoring numeric indexes).
        return array_filter($matches, static fn($key) => is_string($key),
        ARRAY_FILTER_USE_KEY);
    }


    /**
     * Calls handler - supports [ClassName::class, 'method'] and callable.
     *
     * @param callable|array<int, string> $handler
     * @param array<string, string> $params
     */
    private function callHandler(callable|array $handler, array $params): void
    {
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;

            if (!class_exists($class)) {
                throw new InvalidArgumentException("[ERROR]: Controller class not found: {$class}");
            }

            $controller = new $class();

            if (!method_exists($controller, $method)) {
                throw new InvalidArgumentException("[ERROR]: Method {$method} not found in {$class}.");
            }

            $controller->{$method}($params);
            return;
        }

        if (is_callable($handler)) {
            $handler($params);
            return;
        }

        throw new InvalidArgumentException("[ERROR]: Invalid route handler.");
    }
}