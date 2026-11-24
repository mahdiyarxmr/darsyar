<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Translator;
use App\Core\View;
use App\Modules\Auth\AuthService;

final class RoleMiddleware
{
    private array $allowedRoles;

    public function __construct(string|array $roles)
    {
        $this->allowedRoles = is_array($roles) ? $roles : explode('|', $roles);
    }

    public function handle(Request $request, array $params = []): ?Response
    {
        $user = AuthService::user();
        $role = $user['role'] ?? null;
        if (!$user || !in_array($role, $this->allowedRoles, true)) {
            if ($request->isAjax()) {
                return Response::json(['error' => Translator::get('errors.forbidden')], 403);
            }
            return Response::html(View::render('errors/403', ['title' => '403'], 'public'), 403);
        }
        return null;
    }
}
