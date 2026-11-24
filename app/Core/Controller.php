<?php

declare(strict_types=1);

namespace App\Core;

use App\Modules\Auth\AuthService;
use App\Modules\Settings\SettingsService;
use Throwable;

abstract class Controller
{
    protected function view(string $template, array $data = [], ?string $layout = 'main'): Response
    {
        $data['authUser'] = $data['authUser'] ?? AuthService::user();
        $data['flash'] = $data['flash'] ?? Session::pullFlash();
        $data['locale'] = Translator::locale();
        $data['direction'] = Translator::direction();
        $data['theme'] = $this->resolveTheme($data['authUser']);
        return Response::html(View::render($template, $data, $layout));
    }

    protected function json(array $payload, int $status = 200): Response
    {
        return Response::json($payload, $status);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect($path);
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    protected function authUser(): ?array
    {
        return AuthService::user();
    }

    protected function resolveTheme(?array $user): string
    {
        $sessionTheme = Session::get('theme');
        if (in_array($sessionTheme, ['light', 'dark'], true)) {
            return $sessionTheme;
        }
        if ($user && in_array($user['theme'] ?? '', ['light', 'dark'], true)) {
            return $user['theme'];
        }
        try {
            $globalTheme = SettingsService::get('default_theme');
            if (in_array($globalTheme, ['light', 'dark'], true)) {
                return $globalTheme;
            }
        } catch (Throwable) {
        }
        return (string) Config::get('app.default_theme', 'light');
    }
}
