<?php

namespace App\Core;

final class Router
{
    private static ?Router $instance = null;
    private array $routes = [];
    private array $middleware = [];
    private array $groupMiddleware = [];

    private function __construct() {}

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function group(array $attributes, callable $callback): void
    {
        $previous = $this->groupMiddleware;
        if (isset($attributes['middleware'])) {
            $mw = (array) $attributes['middleware'];
            $this->groupMiddleware = array_merge($this->groupMiddleware, $mw);
        }
        $callback($this);
        $this->groupMiddleware = $previous;
    }

    public function get(string $pattern, string $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, string $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    public function put(string $pattern, string $handler): void
    {
        $this->addRoute('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, string $handler): void
    {
        $this->addRoute('DELETE', $pattern, $handler);
    }

    public function match(array $methods, string $pattern, string $handler): void
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $pattern, $handler);
        }
    }

    private function addRoute(string $method, string $pattern, string $handler): void
    {
        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $this->groupMiddleware,
        ];
    }

    public function resolve(Request $request): void
    {
        $method = $request->method();
        $uri = $request->input('page', 'dashboard');
        $sub = $request->input('sub', '');
        $action = $request->input('action', '');

        $path = $uri;
        if ($sub) {
            $path .= '/' . $sub;
        }

        $matched = false;

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = [];
            if ($this->matchPath($route['pattern'], $path, $params)) {
                $handler = $route['handler'];

                $this->runMiddleware($route['middleware']);

                if (is_string($handler)) {
                    $parts = explode('::', $handler);
                    $controllerClass = $parts[0];
                    $controllerMethod = $parts[1] ?? 'index';

                    if (!str_contains($controllerClass, '\\')) {
                        $controllerClass = 'App\\Controllers\\' . $controllerClass;
                    }

                    if (!class_exists($controllerClass)) {
                        throw new \RuntimeException("Controller {$controllerClass} not found");
                    }

                    $controller = new $controllerClass();
                    if (!method_exists($controller, $controllerMethod)) {
                        throw new \RuntimeException("Method {$controllerMethod} not found in {$controllerClass}");
                    }

                    $controller->$controllerMethod($request, $params);
                } elseif (is_callable($handler)) {
                    $handler($request, $params);
                }

                $matched = true;
                break;
            }
        }

        if (!$matched) {
            $this->handleNotFound($request);
        }
    }

    public function resolveLegacy(Request $request): void
    {
        $page = $request->page();
        $sub = $request->sub();

        $handler = null;

        foreach ($this->routes as $route) {
            $pattern = ltrim($route['pattern'], '/');
            $parts = explode('/', $pattern);

            $pageMatch = $parts[0] ?? '';
            $subMatch = $parts[1] ?? '';

            if ($route['method'] !== 'ANY' && $route['method'] !== $request->method()) {
                continue;
            }

            if ($pageMatch !== '' && $pageMatch !== '*' && $pageMatch !== $page) {
                continue;
            }

            $subMatches = ($subMatch === '' || $subMatch === '*' || $subMatch === $sub);

            if (!$subMatches && $subMatch !== $sub) {
                continue;
            }

            $handler = $route;
            break;
        }

        if ($handler) {
            $this->runMiddleware($handler['middleware']);

            if (is_string($handler['handler'])) {
                $parts = explode('::', $handler['handler']);
                $controllerClass = $parts[0];
                $controllerMethod = $parts[1] ?? 'index';

                if (!str_contains($controllerClass, '\\')) {
                    $controllerClass = 'App\\Controllers\\' . $controllerClass;
                }

                if (!class_exists($controllerClass)) {
                    throw new \RuntimeException("Controller {$controllerClass} not found");
                }

                $controller = new $controllerClass();
                if (!method_exists($controller, $controllerMethod)) {
                    throw new \RuntimeException("Method {$controllerMethod} not found in {$controllerClass}");
                }

                $controller->$controllerMethod($request);
            } elseif (is_callable($handler['handler'])) {
                $handler['handler']($request);
            }
        } else {
            $this->handleNotFound($request);
        }
    }

    private function matchPath(string $pattern, string $path, array &$params): bool
    {
        $pattern = rtrim($pattern, '/');
        $path = rtrim($path, '/');

        if ($pattern === '/' || $pattern === '') {
            return $path === '' || $path === '/';
        }

        $pattern = ltrim($pattern, '/');
        $path = ltrim($path, '/');

        $patternParts = explode('/', $pattern);
        $pathParts = explode('/', $path);

        if (count($patternParts) !== count($pathParts)) {
            return false;
        }

        foreach ($patternParts as $i => $part) {
            if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $key = trim($part, '{}');
                $params[$key] = $pathParts[$i];
            } elseif ($part !== $pathParts[$i]) {
                return false;
            }
        }

        return true;
    }

    private function runMiddleware(array $middlewareList): void
    {
        foreach ($middlewareList as $mw) {
            if (is_string($mw)) {
                $class = str_contains($mw, '\\') ? $mw : 'App\\Middleware\\' . $mw;

                if (str_contains($class, ':')) {
                    $parts = explode(':', $class, 2);
                    $class = $parts[0];
                    $param = $parts[1];
                } else {
                    $param = null;
                }

                if (!class_exists($class)) {
                    throw new \RuntimeException("Middleware {$class} not found");
                }

                $instance = new $class();
                if ($param !== null) {
                    $instance->handle($param);
                } else {
                    $instance->handle();
                }
            }
        }
    }

    private function handleNotFound(Request $request): void
    {
        http_response_code(404);
        if ($request->isAjax()) {
            Response::error('Halaman tidak ditemukan', 404);
        }
        $viewPath = defined('VIEW_PATH') ? VIEW_PATH : __DIR__ . '/../../views';
        require $viewPath . '/errors/404.php';
        exit;
    }
}
