<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $pattern, array|callable $handler, array $middlewares = []): void
    {
        $this->add('GET', $pattern, $handler, $middlewares);
    }

    public function post(string $pattern, array|callable $handler, array $middlewares = []): void
    {
        $this->add('POST', $pattern, $handler, $middlewares);
    }

    public function add(string $method, string $pattern, array|callable $handler, array $middlewares = []): void
    {
        $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $val) {
                    if (is_string($key)) {
                        $params[$key] = $val;
                    }
                }

                // Enforce CSRF automatically on all state-changing POST requests
                if ($method === 'POST' && !Csrf::verify($request->csrfToken())) {
                    if ($request->isAjax()) {
                        return Response::json(['error' => Translator::get('errors.csrf_invalid')], 419);
                    }
                    Session::flash('error', Translator::get('errors.csrf_invalid'));
                    return Response::redirect($request->path() === '/login' ? '/login' : '/');
                }

                foreach ($route['middlewares'] as $mw) {
                    $mwResponse = $this->runMiddleware($mw, $request, $params);
                    if ($mwResponse instanceof Response) {
                        return $mwResponse;
                    }
                }

                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    return $controller->$action($request, $params);
                }
                return $handler($request, $params);
            }
        }

        if ($request->isAjax()) {
            return Response::json(['error' => 'Not Found'], 404);
        }
        return Response::html(View::render('errors/404', ['title' => '404'], 'public'), 404);
    }

    private function runMiddleware(string|array $mw, Request $request, array $params): ?Response
    {
        if (is_array($mw)) {
            [$class, $arg] = $mw;
            $instance = new $class($arg);
            return $instance->handle($request, $params);
        }
        $instance = new $mw();
        return $instance->handle($request, $params);
    }
}
