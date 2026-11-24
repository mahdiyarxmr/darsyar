<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Translator;
use App\Core\View;
use App\Modules\Auth\AuthService;
use App\Modules\Authorization\AuthorizationService;

final class PermissionMiddleware
{
    private string $permission;

    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

    public function handle(Request $request, array $params = []): ?Response
    {
        $user = AuthService::user();
        if (!$user || !AuthorizationService::hasPermission((int) $user['id'], $this->permission)) {
            if ($request->isAjax()) {
                return Response::json(['error' => Translator::get('errors.forbidden')], 403);
            }
            return Response::html(View::render('errors/403', ['title' => '403'], 'public'), 403);
        }
        return null;
    }
}
