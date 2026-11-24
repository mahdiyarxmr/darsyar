<?php

declare(strict_types=1);

namespace App\Modules\Counseling;

use App\Core\Database;
use App\Modules\Notifications\NotificationService;

final class CounselingService
{
    public static function createStudyPlan(
        int $studentId,
        int $counselorId,
        string $title,
        string $weekStart,
        string $weekEnd,
        ?string $weeklyGoal
    ): int {
        $now = date('Y-m-d H:i:s');
        $planId = Database::insert(
            'INSERT INTO study_plans (student_id, counselor_id, title, week_start_date, week_end_date, weekly_goal, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$studentId, $counselorId, $title, $weekStart, $weekEnd, $weeklyGoal, 'active', $now, $now]
        );

        $stu = Database::fetch('SELECT user_id FROM students WHERE id = ?', [$studentId]);
        if ($stu) {
            NotificationService::notifyUser((int) $stu['user_id'], 'study_plan', \App\Core\Translator::get('notif.new_plan_title'), $title, '/student/study-plans');
            NotificationService::notifyParentsOfStudent($studentId, 'study_plan', \App\Core\Translator::get('notif.new_plan_child_title'), $title, '/parent/study-plans?student_id=' . $studentId);
        }
        return $planId;
    }

    public static function addTask(
        int $planId,
        string $dayOfWeek,
        ?int $subjectId,
        string $taskTitle,
        ?string $startTime,
        ?string $endTime,
        int $durationMinutes,
        ?string $dailyGoal
    ): int {
        return Database::insert(
            'INSERT INTO study_plan_tasks (study_plan_id, day_of_week, subject_id, task_title, start_time, end_time, duration_minutes, daily_goal, is_completed)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)',
            [$planId, $dayOfWeek, $subjectId, $taskTitle, $startTime, $endTime, max(5, $durationMinutes), $dailyGoal]
        );
    }

    public static function toggleTaskForStudent(int $taskId, int $studentId, bool $completed): ?array
    {
        $task = Database::fetch(
            'SELECT t.*, sp.student_id
             FROM study_plan_tasks t
             JOIN study_plans sp ON sp.id = t.study_plan_id
             WHERE t.id = ? AND sp.student_id = ?',
            [$taskId, $studentId]
        );
        if (!$task) {
            return null;
        }

        Database::execute(
            'UPDATE study_plan_tasks SET is_completed = ?, completed_at = ? WHERE id = ?',
            [$completed ? 1 : 0, $completed ? date('Y-m-d H:i:s') : null, $taskId]
        );

        return self::planProgress((int) $task['study_plan_id']);
    }

    public static function planProgress(int $planId): array
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) AS completed,
                    COALESCE(SUM(duration_minutes), 0) AS total_minutes,
                    COALESCE(SUM(CASE WHEN is_completed = 1 THEN duration_minutes ELSE 0 END), 0) AS completed_minutes
             FROM study_plan_tasks
             WHERE study_plan_id = ?',
            [$planId]
        );
        $total = (int) ($row['total'] ?? 0);
        $completed = (int) ($row['completed'] ?? 0);
        $incomplete = max(0, $total - $completed);
        $pct = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

        return [
            'total' => $total,
            'completed' => $completed,
            'incomplete' => $incomplete,
            'percentage' => $pct,
            'total_minutes' => (int) ($row['total_minutes'] ?? 0),
            'completed_minutes' => (int) ($row['completed_minutes'] ?? 0),
        ];
    }

    public static function studentOverallCompletion(int $studentId): float
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN t.is_completed = 1 THEN 1 ELSE 0 END) AS completed
             FROM study_plan_tasks t
             JOIN study_plans sp ON sp.id = t.study_plan_id
             WHERE sp.student_id = ?',
            [$studentId]
        );
        $total = (int) ($row['total'] ?? 0);
        if ($total === 0) {
            return 0.0;
        }
        return round((((int) ($row['completed'] ?? 0)) / $total) * 100, 1);
    }
}
