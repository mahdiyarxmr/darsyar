<?php

declare(strict_types=1);

namespace App\Core;

use App\Modules\Auth\AuthService;
use App\Modules\Settings\SettingsService;
use Throwable;

final class App
{
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function run(): void
    {
        Session::start();
        $this->initLocale();

        $request = Request::capture();
        try {
            $response = $this->router->dispatch($request);
        } catch (Throwable $e) {
            error_log('[DarsYar Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            if ($request->isAjax()) {
                $payload = ['error' => Config::get('app.debug') ? $e->getMessage() : Translator::get('errors.server_error')];
                $response = Response::json($payload, 500);
            } else {
                $response = Response::html(
                    View::render('errors/500', [
                        'title' => '500',
                        'message' => Config::get('app.debug') ? $e->getMessage() : Translator::get('errors.server_error'),
                    ], 'public'),
                    500
                );
            }
        }
        $response->send();
    }

    private function initLocale(): void
    {
        $supported = Config::get('app.supported_locales', ['fa', 'en']);
        $locale = Session::get('locale');
        if (!in_array($locale, $supported, true)) {
            $user = null;
            try {
                $user = AuthService::user();
            } catch (Throwable) {
            }
            if ($user && in_array($user['locale'] ?? '', $supported, true)) {
                $locale = $user['locale'];
            } else {
                $globalLocale = null;
                try {
                    $globalLocale = SettingsService::get('default_locale');
                } catch (Throwable) {
                }
                $locale = in_array($globalLocale, $supported, true)
                    ? $globalLocale
                    : Config::get('app.default_locale', 'fa');
            }
        }
        Translator::setLocale((string) $locale);
    }
}
