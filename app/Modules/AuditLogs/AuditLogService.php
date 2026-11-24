<?php

declare(strict_types=1);

namespace App\Modules\AuditLogs;

use App\Core\Database;

final class AuditLogService
{
    public static function log(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        array $metadata = [],
        ?string $ip = null
    ): void {
        Database::execute(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $action,
                $entityType,
                $entityId,
                $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE),
                $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
                date('Y-m-d H:i:s'),
            ]
        );
    }

    public static function recent(int $limit = 100): array
    {
        return Database::fetchAll(
            'SELECT a.*, u.username, u.full_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC
             LIMIT ' . max(1, min(500, $limit))
        );
    }
}
