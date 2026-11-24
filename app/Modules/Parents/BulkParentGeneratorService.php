<?php

declare(strict_types=1);

namespace App\Modules\Parents;

use App\Core\Database;
use App\Modules\AuditLogs\AuditLogService;
use App\Modules\Auth\AuthService;

final class BulkParentGeneratorService
{
    /**
     * Automatically generate parent accounts for unlinked students in a class (or school-wide).
     * Prevents duplicate parent accounts and duplicate parent_student links.
     * Supports linking siblings sharing the same guardian phone when $linkSiblingsByPhone is true.
     *
     * @return array{created: int, linked: int, skipped: int, credentials: array}
     */
    public static function generateForStudents(
        ?int $classId,
        int $actorUserId,
        bool $linkSiblingsByPhone = true
    ): array {
        $sql = 'SELECT s.id AS student_id, s.student_code, s.guardian_phone, u.full_name AS student_name, c.name AS class_name
                FROM students s
                JOIN users u ON u.id = s.user_id
                LEFT JOIN classes c ON c.id = s.class_id';
        $params = [];
        if ($classId !== null && $classId > 0) {
            $sql .= ' WHERE s.class_id = ?';
            $params[] = $classId;
        }
        $sql .= ' ORDER BY s.id ASC';

        $students = Database::fetchAll($sql, $params);
        $parentRoleId = (int) Database::fetchColumn("SELECT id FROM roles WHERE slug = 'parent'");
        $now = date('Y-m-d H:i:s');

        $created = 0;
        $linked = 0;
        $skipped = 0;
        $credentials = [];

        Database::transaction(static function () use (
            $students,
            $parentRoleId,
            $now,
            $linkSiblingsByPhone,
            &$created,
            &$linked,
            &$skipped,
            &$credentials
        ): void {
            foreach ($students as $stu) {
                $studentId = (int) $stu['student_id'];

                $existingLink = (int) Database::fetchColumn(
                    'SELECT COUNT(*) FROM parent_student WHERE student_id = ?',
                    [$studentId]
                );
                if ($existingLink > 0) {
                    $skipped++;
                    continue;
                }

                $phone = trim((string) ($stu['guardian_phone'] ?? ''));
                $existingParentId = null;
                $existingParentUsername = null;

                if ($linkSiblingsByPhone && $phone !== '') {
                    $found = Database::fetch(
                        'SELECT p.id, u.username
                         FROM parents p
                         JOIN users u ON u.id = p.user_id
                         WHERE p.emergency_phone = ?
                         LIMIT 1',
                        [$phone]
                    );
                    if ($found) {
                        $existingParentId = (int) $found['id'];
                        $existingParentUsername = (string) $found['username'];
                    }
                }

                if ($existingParentId !== null) {
                    Database::execute(
                        'INSERT INTO parent_student (parent_id, student_id, relationship, is_primary, created_at)
                         VALUES (?, ?, ?, 1, ?)',
                        [$existingParentId, $studentId, 'guardian', $now]
                    );
                    $linked++;
                    $credentials[] = [
                        'student_code' => $stu['student_code'],
                        'student_name' => $stu['student_name'],
                        'class_name' => $stu['class_name'] ?? '-',
                        'parent_username' => $existingParentUsername,
                        'temporary_password' => '(Existing Parent Linked)',
                        'is_new' => false,
                    ];
                    continue;
                }

                $username = self::generateUniqueUsername((string) $stu['student_code']);
                $tempPassword = self::generateTempPassword();
                $parentFullName = (\App\Core\Translator::locale() === 'fa' ? 'ولی ' : 'Parent of ') . $stu['student_name'];

                $parentUserId = Database::insert(
                    'INSERT INTO users (username, email, phone, password_hash, full_name, locale, theme, status, must_change_password, created_at, updated_at)
                     VALUES (?, NULL, ?, ?, ?, ?, ?, ?, 1, ?, ?)',
                    [
                        $username,
                        $phone !== '' ? $phone : null,
                        AuthService::hashPassword($tempPassword),
                        $parentFullName,
                        'fa',
                        'light',
                        'active',
                        $now,
                        $now,
                    ]
                );

                Database::execute(
                    'INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)',
                    [$parentUserId, $parentRoleId]
                );

                $parentId = Database::insert(
                    'INSERT INTO parents (user_id, emergency_phone) VALUES (?, ?)',
                    [$parentUserId, $phone !== '' ? $phone : null]
                );

                Database::execute(
                    'INSERT INTO parent_student (parent_id, student_id, relationship, is_primary, created_at)
                     VALUES (?, ?, ?, 1, ?)',
                    [$parentId, $studentId, 'guardian', $now]
                );

                $created++;
                $linked++;
                $credentials[] = [
                    'student_code' => $stu['student_code'],
                    'student_name' => $stu['student_name'],
                    'class_name' => $stu['class_name'] ?? '-',
                    'parent_username' => $username,
                    'temporary_password' => $tempPassword,
                    'is_new' => true,
                ];
            }
        });

        AuditLogService::log($actorUserId, 'parents.bulk_generated', 'class', $classId, [
            'created' => $created,
            'linked' => $linked,
            'skipped' => $skipped,
        ]);

        return [
            'created' => $created,
            'linked' => $linked,
            'skipped' => $skipped,
            'credentials' => $credentials,
        ];
    }

    public static function linkSibling(int $parentId, int $studentId, string $relationship = 'guardian'): bool
    {
        $exists = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM parent_student WHERE parent_id = ? AND student_id = ?',
            [$parentId, $studentId]
        );
        if ($exists > 0) {
            return false;
        }
        Database::execute(
            'INSERT INTO parent_student (parent_id, student_id, relationship, is_primary, created_at) VALUES (?, ?, ?, 0, ?)',
            [$parentId, $studentId, $relationship, date('Y-m-d H:i:s')]
        );
        return true;
    }

    private static function generateUniqueUsername(string $studentCode): string
    {
        $base = 'p_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $studentCode) ?: bin2hex(random_bytes(3)));
        $candidate = $base;
        $counter = 1;
        while (Database::fetch('SELECT id FROM users WHERE username = ?', [$candidate])) {
            $candidate = $base . '_' . $counter;
            $counter++;
        }
        return $candidate;
    }

    private static function generateTempPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $len = strlen($alphabet);
        $out = 'P@';
        for ($i = 0; $i < 8; $i++) {
            $out .= $alphabet[random_int(0, $len - 1)];
        }
        return $out;
    }
}
