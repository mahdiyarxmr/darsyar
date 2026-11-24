<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function url(string $path = '/'): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = str_replace('\\', '/', dirname($scriptName));
        if ($baseDir === '/' || $baseDir === '.' || $baseDir === '\\') {
            $baseDir = '';
        }
        $baseDir = preg_replace('#/public$#', '', $baseDir) ?? '';
        $normalized = '/' . ltrim($path, '/');
        return $baseDir . ($normalized === '/' && $baseDir !== '' ? '/' : $normalized);
    }

    public static function currentRequestUri(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        if ($uri === '' || !str_starts_with($uri, '/')) {
            return '/';
        }
        return $uri;
    }

    public static function render(string $viewName, array $data = [], ?string $layout = 'main'): string
    {
        $viewFile = APP_PATH . '/Views/' . ltrim($viewName, '/') . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View not found: {$viewName}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean() ?: '';

        if ($layout === null) {
            return $content;
        }

        $layoutFile = APP_PATH . '/Views/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            return $content;
        }

        ob_start();
        require $layoutFile;
        return ob_get_clean() ?: '';
    }

    public static function partial(string $partialName, array $data = []): string
    {
        return self::render('partials/' . ltrim($partialName, '/'), $data, null);
    }
}
