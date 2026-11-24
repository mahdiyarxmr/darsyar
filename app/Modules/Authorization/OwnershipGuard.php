<?php

declare(strict_types=1);

namespace App\Modules\Authorization;

use App\Core\Database;

final class OwnershipGuard
{
    public static function parentOwnsStudent(int $parentUserId, int $studentId): bool
    {
        $count = (int) Database::fetchColumn(
            'SELECT COUNT(*)
             FROM parent_student ps
             JOIN parents p ON p.id = ps.parent_id
             WHERE p.user_id = ? AND ps.student_id = ?',
            [$parentUserId, $studentId]
        );
        return $count > 0;
    }

    public static function teacherAssignedToClass(int $teacherId, int $classId, ?int $subjectId = null): bool
    {
        if ($subjectId !== null) {
            $count = (int) Database::fetchColumn(
                'SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id = ? AND class_id = ? AND subject_id = ?',
                [$teacherId, $classId, $subjectId]
            );
            return $count > 0;
        }

        $count = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id = ? AND class_id = ?',
            [$teacherId, $classId]
        );
        return $count > 0;
    }

    public static function studentProfile(int $userId): ?array
    {
        return Database::fetch('SELECT * FROM students WHERE user_id = ?', [$userId]);
    }

    public static function teacherProfile(int $userId): ?array
    {
        return Database::fetch('SELECT * FROM teachers WHERE user_id = ?', [$userId]);
    }

    public static function counselorProfile(int $userId): ?array
    {
        return Database::fetch('SELECT * FROM counselors WHERE user_id = ?', [$userId]);
    }

    public static function parentProfile(int $userId): ?array
    {
        return Database::fetch('SELECT * FROM parents WHERE user_id = ?', [$userId]);
    }

    public static function parentChildren(int $parentUserId): array
    {
        return Database::fetchAll(
            'SELECT s.*, u.full_name, u.username, c.name AS class_name, g.name AS grade_name, ps.relationship
             FROM parent_student ps
             JOIN parents p ON p.id = ps.parent_id
             JOIN students s ON s.id = ps.student_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN classes c ON c.id = s.class_id
             LEFT JOIN grades g ON g.id = c.grade_id
             WHERE p.user_id = ?
             ORDER BY u.full_name ASC',
            [$parentUserId]
        );
    }
}
