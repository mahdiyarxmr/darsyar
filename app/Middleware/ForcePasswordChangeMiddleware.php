<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Translator;
use App\Modules\Auth\AuthService;

final class ForcePasswordChangeMiddleware
{
    public function handle(Request $request, array $params = []): ?Response
    {
        $user = AuthService::user();
        if ($user && (int) ($user['must_change_password'] ?? 0) === 1) {
            $path = $request->path();
            if ($path !== '/account/password' && $path !== '/logout') {
                if ($request->isAjax()) {
                    return Response::json(['error' => Translator::get('auth.must_change_password')], 403);
                }
                Session::flash('warning', Translator::get('auth.must_change_password'));
                return Response::redirect('/account/password');
            }
        }
        return null;
    }
}
