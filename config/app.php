<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'name' => Env::get('APP_NAME', 'DarsYar'),
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url' => rtrim((string) Env::get('APP_URL', 'http://127.0.0.1:8000'), '/'),
    'timezone' => Env::get('APP_TIMEZONE', 'Asia/Tehran'),
    'key' => Env::get('APP_KEY', ''),
    'default_locale' => Env::get('APP_DEFAULT_LOCALE', 'fa'),
    'supported_locales' => ['fa', 'en'],
    'rtl_locales' => ['fa'],
    'default_theme' => Env::get('APP_DEFAULT_THEME', 'light'),
    'max_upload_bytes' => 10 * 1024 * 1024, // 10 MB
    'session_lifetime' => 7200, // 2 hours
    'login_rate_limit' => [
        'max_attempts' => 5,
        'window_seconds' => 900, // 15 minutes
    ],
];
