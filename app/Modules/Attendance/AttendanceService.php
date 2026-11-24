<?php

declare(strict_types=1);

namespace App\Modules\Attendance;

use App\Core\Database;
use App\Modules\Notifications\NotificationService;

final class AttendanceService
{
    public static function recordBatch(
        int $classId,
        ?int $subjectId,
        string $date,
        string $scope,
        array $statusesByStudentId,
        array $notesByStudentId,
        int $recordedByUserId
    ): int {
        $now = date('Y-m-d H:i:s');
        $count = 0;

        Database::transaction(static function () use (
            $classId,
            $subjectId,
            $date,
            $scope,
            $statusesByStudentId,
            $notesByStudentId,
            $recordedByUserId,
            $now,
            &$count
        ): void {
            foreach ($statusesByStudentId as $studentId => $status) {
                $sid = (int) $studentId;
                if (!in_array($status, ['present', 'absent', 'late', 'excused'], true)) {
                    continue;
                }
                $note = trim((string) ($notesByStudentId[$studentId] ?? '')) ?: null;

                $existing = Database::fetch(
                    'SELECT id FROM attendance WHERE student_id = ? AND class_id = ? AND attendance_date = ? AND scope = ? AND ' .
                    ($subjectId !== null ? 'subject_id = ?' : 'subject_id IS NULL'),
                    $subjectId !== null ? [$sid, $classId, $date, $scope, $subjectId] : [$sid, $classId, $date, $scope]
                );

                if ($existing) {
                    Database::execute(
                        'UPDATE attendance SET status = ?, note = ?, recorded_by = ?, updated_at = ? WHERE id = ?',
                        [$status, $note, $recordedByUserId, $now, (int) $existing['id']]
                    );
                } else {
                    Database::execute(
                        'INSERT INTO attendance (student_id, class_id, subject_id, attendance_date, scope, status, note, recorded_by, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [$sid, $classId, $subjectId, $date, $scope, $status, $note, $recordedByUserId, $now, $now]
                    );
                }

                if ($status === 'absent' || $status === 'late') {
                    $studentName = (string) Database::fetchColumn(
                        'SELECT u.full_name FROM students s JOIN users u ON u.id = s.user_id WHERE s.id = ?',
                        [$sid]
                    );
                    $statusTxt = \App\Core\Translator::get('status.' . $status);
                    NotificationService::notifyParentsOfStudent(
                        $sid,
                        'attendance',
                        \App\Core\Translator::get('notif.attendance_alert_title', ['status' => $statusTxt]),
                        "{$studentName} — {$statusTxt} ({$date})",
                        '/parent/attendance?student_id=' . $sid
                    );
                }
                $count++;
            }
        });

        return $count;
    }

    public static function studentAttendanceSummary(int $studentId): array
    {
        $rows = Database::fetchAll(
            'SELECT a.*, s.name AS subject_name, c.name AS class_name
             FROM attendance a
             LEFT JOIN subjects s ON s.id = a.subject_id
             LEFT JOIN classes c ON c.id = a.class_id
             WHERE a.student_id = ?
             ORDER BY a.attendance_date DESC, a.id DESC',
            [$studentId]
        );

        $total = count($rows);
        $present = 0;
        $absent = 0;
        $late = 0;
        foreach ($rows as $r) {
            if ($r['status'] === 'present' || $r['status'] === 'excused') {
                $present++;
            } elseif ($r['status'] === 'absent') {
                $absent++;
            } elseif ($r['status'] === 'late') {
                $late++;
                $present++;
            }
        }
        $rate = $total > 0 ? round(($present / $total) * 100, 1) : 100.0;

        return [
            'records' => $rows,
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'rate' => $rate,
        ];
    }
}
