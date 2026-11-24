<?php

declare(strict_types=1);

namespace App\Modules\Exams;

use App\Core\Database;

final class DailyTestService
{
    public static function submitPracticeAttempt(int $dailyTestId, int $studentId, array $selectedIndices, int $timeSpentSeconds): array
    {
        $questions = Database::fetchAll('SELECT * FROM daily_test_questions WHERE daily_test_id = ? ORDER BY id ASC', [$dailyTestId]);
        $total = count($questions);
        $correct = 0;
        $incorrect = 0;

        foreach ($questions as $q) {
            $qid = (int) $q['id'];
            if (!isset($selectedIndices[$qid]) || $selectedIndices[$qid] === '') {
                continue;
            }
            if ((int) $selectedIndices[$qid] === (int) $q['correct_index']) {
                $correct++;
            } else {
                $incorrect++;
            }
        }

        $pct = $total > 0 ? round(($correct / $total) * 100, 2) : 0.0;
        $now = date('Y-m-d H:i:s');

        $attemptId = Database::insert(
            'INSERT INTO daily_test_attempts (daily_test_id, student_id, total_questions, correct_count, incorrect_count, score_percentage, time_spent_seconds, answers_json, completed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $dailyTestId,
                $studentId,
                $total,
                $correct,
                $incorrect,
                $pct,
                max(0, $timeSpentSeconds),
                json_encode($selectedIndices),
                $now,
            ]
        );

        return [
            'id' => $attemptId,
            'total_questions' => $total,
            'correct_count' => $correct,
            'incorrect_count' => $incorrect,
            'score_percentage' => $pct,
        ];
    }

    public static function studentSubjectStats(int $studentId): array
    {
        return Database::fetchAll(
            'SELECT s.name AS subject_name,
                    COUNT(dta.id) AS attempts_count,
                    ROUND(AVG(dta.score_percentage), 1) AS avg_score,
                    SUM(dta.correct_count) AS total_correct
             FROM daily_test_attempts dta
             JOIN daily_tests dt ON dt.id = dta.daily_test_id
             JOIN subjects s ON s.id = dt.subject_id
             WHERE dta.student_id = ?
             GROUP BY s.id, s.name
             ORDER BY avg_score DESC',
            [$studentId]
        );
    }
}
