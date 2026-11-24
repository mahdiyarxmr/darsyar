<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Translator;
use App\Modules\Auth\AuthService;

final class AuthMiddleware
{
    public function handle(Request $request, array $params = []): ?Response
    {
        $user = AuthService::user();
        if (!$user) {
            if ($request->isAjax()) {
                return Response::json(['error' => Translator::get('errors.unauthorized')], 401);
            }
            Session::flash('error', Translator::get('errors.unauthorized'));
            return Response::redirect('/login');
        }
        return null;
    }
}
