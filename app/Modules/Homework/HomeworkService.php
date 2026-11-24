<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use App\Core\Database;
use App\Modules\Notifications\NotificationService;

final class HomeworkService
{
    public static function createAssignment(
        int $teacherId,
        int $classId,
        int $subjectId,
        string $title,
        string $description,
        ?string $attachmentPath,
        ?string $requirements,
        string $dueAt,
        float $maxScore
    ): int {
        $now = date('Y-m-d H:i:s');
        $hwId = Database::insert(
            'INSERT INTO homework (teacher_id, class_id, subject_id, title, description, attachment_path, requirements, due_at, max_score, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$teacherId, $classId, $subjectId, $title, $description, $attachmentPath, $requirements, $dueAt, $maxScore, $now, $now]
        );

        NotificationService::notifyClassStudents(
            $classId,
            'homework',
            \App\Core\Translator::get('notif.new_hw_title', ['title' => $title]),
            $dueAt,
            '/student/homework'
        );

        return $hwId;
    }

    public static function submitWork(int $homeworkId, int $studentId, ?string $text, ?string $attachmentPath): int
    {
        $hw = Database::fetch('SELECT due_at FROM homework WHERE id = ?', [$homeworkId]);
        $now = date('Y-m-d H:i:s');
        $isLate = $hw && strtotime($now) > strtotime((string) $hw['due_at']);
        $status = $isLate ? 'late' : 'submitted';

        $existing = Database::fetch('SELECT id FROM homework_submissions WHERE homework_id = ? AND student_id = ?', [$homeworkId, $studentId]);
        if ($existing) {
            Database::execute(
                'UPDATE homework_submissions SET submission_text = ?, attachment_path = COALESCE(?, attachment_path), submitted_at = ?, status = ? WHERE id = ?',
                [$text, $attachmentPath, $now, $status, (int) $existing['id']]
            );
            return (int) $existing['id'];
        }

        return Database::insert(
            'INSERT INTO homework_submissions (homework_id, student_id, submission_text, attachment_path, submitted_at, status)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$homeworkId, $studentId, $text, $attachmentPath, $now, $status]
        );
    }

    public static function gradeSubmission(int $submissionId, int $teacherId, float $score, string $feedbackText, string $decision): bool
    {
        $sub = Database::fetch(
            'SELECT hs.*, h.title AS hw_title, h.max_score, s.user_id AS student_user_id, u.full_name AS student_name
             FROM homework_submissions hs
             JOIN homework h ON h.id = hs.homework_id
             JOIN students s ON s.id = hs.student_id
             JOIN users u ON u.id = s.user_id
             WHERE hs.id = ?',
            [$submissionId]
        );
        if (!$sub) {
            return false;
        }
        if (!in_array($decision, ['accepted', 'needs_revision'], true)) {
            $decision = 'accepted';
        }

        $now = date('Y-m-d H:i:s');
        $clamped = max(0.0, min((float) $sub['max_score'], $score));

        Database::transaction(static function () use ($submissionId, $teacherId, $clamped, $feedbackText, $decision, $now): void {
            Database::execute('UPDATE homework_submissions SET status = ? WHERE id = ?', [$decision, $submissionId]);
            $existingFb = Database::fetch('SELECT id FROM homework_feedback WHERE submission_id = ?', [$submissionId]);
            if ($existingFb) {
                Database::execute(
                    'UPDATE homework_feedback SET teacher_id = ?, score = ?, feedback_text = ?, decision = ?, graded_at = ? WHERE id = ?',
                    [$teacherId, $clamped, $feedbackText, $decision, $now, (int) $existingFb['id']]
                );
            } else {
                Database::execute(
                    'INSERT INTO homework_feedback (submission_id, teacher_id, score, feedback_text, decision, graded_at)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [$submissionId, $teacherId, $clamped, $feedbackText, $decision, $now]
                );
            }
        });

        $statusText = \App\Core\Translator::get('status.' . $decision);
        $msg = "{$sub['hw_title']} — {$clamped}/{$sub['max_score']} ({$statusText})";
        NotificationService::notifyUser((int) $sub['student_user_id'], 'homework_graded', \App\Core\Translator::get('notif.hw_graded_title'), $msg, '/student/homework');
        NotificationService::notifyParentsOfStudent(
            (int) $sub['student_id'],
            'homework_graded',
            \App\Core\Translator::get('notif.hw_graded_child_title', ['name' => $sub['student_name']]),
            $msg,
            '/parent/homework?student_id=' . $sub['student_id']
        );

        return true;
    }
}
