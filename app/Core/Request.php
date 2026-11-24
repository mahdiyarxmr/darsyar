<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(
        private string $method,
        private string $path,
        private array $query = [],
        private array $post = [],
        private array $files = [],
        private array $headers = [],
        private string $rawBody = '',
        private string $ip = '127.0.0.1'
    ) {}

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Strip base path if deployed in a subdirectory on cPanel
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = str_replace('\\', '/', dirname($scriptName));
        if ($baseDir !== '/' && $baseDir !== '.' && str_starts_with($path, $baseDir)) {
            $path = substr($path, strlen($baseDir));
        }
        $publicPrefix = '/public';
        if (str_starts_with($path, $publicPrefix)) {
            $path = substr($path, strlen($publicPrefix));
        }
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $headers = [];
        foreach ($_SERVER as $key => $val) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = (string) $val;
            }
        }

        $rawBody = file_get_contents('php://input') ?: '';
        $post = $_POST;
        if (empty($post) && str_contains($headers['content-type'] ?? '', 'application/json') && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $post = $decoded;
            }
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return new self($method, $path, $_GET, $post, $_FILES, $headers, $rawBody, $ip);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function allQuery(): array
    {
        return $this->query;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function allPost(): array
    {
        return $this->post;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function isAjax(): bool
    {
        return strtolower((string) $this->header('x-requested-with', '')) === 'xmlhttprequest'
            || str_contains((string) $this->header('accept', ''), 'application/json')
            || str_contains((string) $this->header('content-type', ''), 'application/json');
    }

    public function csrfToken(): ?string
    {
        $token = $this->post['_csrf'] ?? $this->header('x-csrf-token');
        return is_string($token) ? $token : null;
    }

    public function ip(): string
    {
        return $this->ip;
    }
}
