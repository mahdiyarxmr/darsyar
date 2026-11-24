<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Self-contained PSR-4 Autoloader for App\ namespace (zero Composer runtime dependency on cPanel)
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// Load environment variables from .env if present
App\Core\Env::load(BASE_PATH . '/.env');

// Load configuration
App\Core\Config::loadAll(BASE_PATH . '/config');

// Set default timezone
date_default_timezone_set((string) App\Core\Config::get('app.timezone', 'UTC'));
