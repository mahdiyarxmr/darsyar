<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Core\Config;
use App\Core\Database;

final class NotificationService
{
    public static function notifyUser(
        int $userId,
        string $type,
        string $title,
        string $body,
        ?string $link = null
    ): int {
        $id = Database::insert(
            'INSERT INTO notifications (user_id, type, title, body, link, is_read, created_at)
             VALUES (?, ?, ?, ?, ?, 0, ?)',
            [$userId, $type, $title, $body, $link, date('Y-m-d H:i:s')]
        );

        // Optional non-blocking email hook when enabled by administrator
        if (Config::get('mail.mail_enabled', false)) {
            $user = Database::fetch('SELECT email FROM users WHERE id = ?', [$userId]);
            if (!empty($user['email'])) {
                @mail($user['email'], $title, $body, 'From: ' . Config::get('mail.from_address', 'noreply@school.local'));
            }
        }

        return $id;
    }

    public static function notifyParentsOfStudent(
        int $studentId,
        string $type,
        string $title,
        string $body,
        ?string $link = null
    ): void {
        $parents = Database::fetchAll(
            'SELECT p.user_id
             FROM parent_student ps
             JOIN parents p ON p.id = ps.parent_id
             WHERE ps.student_id = ?',
            [$studentId]
        );

        foreach ($parents as $parent) {
            self::notifyUser((int) $parent['user_id'], $type, $title, $body, $link);
        }
    }

    public static function notifyClassStudents(
        int $classId,
        string $type,
        string $title,
        string $body,
        ?string $link = null
    ): void {
        $students = Database::fetchAll('SELECT user_id FROM students WHERE class_id = ?', [$classId]);
        foreach ($students as $student) {
            self::notifyUser((int) $student['user_id'], $type, $title, $body, $link);
        }
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        );
    }

    public static function forUser(int $userId, int $limit = 50): array
    {
        return Database::fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT ' . max(1, min(200, $limit)),
            [$userId]
        );
    }

    public static function markRead(int $notificationId, int $userId): bool
    {
        $affected = Database::execute(
            'UPDATE notifications SET is_read = 1, read_at = ? WHERE id = ? AND user_id = ?',
            [date('Y-m-d H:i:s'), $notificationId, $userId]
        );
        return $affected > 0;
    }

    public static function markAllRead(int $userId): void
    {
        Database::execute(
            'UPDATE notifications SET is_read = 1, read_at = ? WHERE user_id = ? AND is_read = 0',
            [date('Y-m-d H:i:s'), $userId]
        );
    }
}
