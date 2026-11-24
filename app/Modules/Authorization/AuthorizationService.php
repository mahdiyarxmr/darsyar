<?php

declare(strict_types=1);

namespace App\Modules\Authorization;

use App\Core\Config;
use App\Core\Database;

final class AuthorizationService
{
    public static function userRole(int $userId): ?string
    {
        $row = Database::fetch(
            'SELECT r.slug
             FROM user_roles ur
             JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = ?
             LIMIT 1',
            [$userId]
        );
        return $row['slug'] ?? null;
    }

    public static function hasPermission(int $userId, string $permissionSlug): bool
    {
        $dbCount = (int) Database::fetchColumn(
            'SELECT COUNT(*)
             FROM user_roles ur
             JOIN role_permissions rp ON rp.role_id = ur.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE ur.user_id = ? AND p.slug = ?',
            [$userId, $permissionSlug]
        );
        if ($dbCount > 0) {
            return true;
        }

        $role = self::userRole($userId);
        if (!$role) {
            return false;
        }
        $configPerms = Config::get("permissions.roles.{$role}.permissions", []);
        return in_array($permissionSlug, $configPerms, true);
    }

    public static function dashboardRouteForRole(?string $role): string
    {
        if (!$role) {
            return '/login';
        }
        return (string) Config::get("permissions.roles.{$role}.home", '/');
    }
}
