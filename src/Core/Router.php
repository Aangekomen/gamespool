<?php
declare(strict_types=1);

namespace GamesPool\Core;

class Router
{
    /** @var array<string, array<int, array{pattern:string, params:array<int,string>, handler:callable|array}>> */
    private array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'PATCH'  => [],
        'DELETE' => [],
    ];

    public function get(string $path, callable|array $handler): void    { $this->add('GET', $path, $handler); }
    public function post(string $path, callable|array $handler): void   { $this->add('POST', $path, $handler); }
    public function put(string $path, callable|array $handler): void    { $this->add('PUT', $path, $handler); }
    public function patch(string $path, callable|array $handler): void  { $this->add('PATCH', $path, $handler); }
    public function delete(string $path, callable|array $handler): void { $this->add('DELETE', $path, $handler); }

    private function add(string $method, string $path, callable|array $handler): void
    {
        $params = [];
        $pattern = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $path);
        $this->routes[$method][] = [
            'pattern' => '#^' . $pattern . '$#',
            'params'  => $params,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        // Allow form-method override via _method input
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches);
                $args = [];
                foreach ($route['params'] as $i => $name) {
                    $args[$name] = $matches[$i] ?? null;
                }
                $this->call($route['handler'], $args);
                return;
            }
        }

        http_response_code(404);
        echo view('errors/404', []);
    }

    private function call(callable|array $handler, array $args): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = new $class();
            $output = $instance->$method(...array_values($args));
        } else {
            $output = $handler(...array_values($args));
        }
        if (is_string($output)) {
            echo $output;
        }
    }
}
