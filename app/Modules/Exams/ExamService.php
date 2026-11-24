<?php

declare(strict_types=1);

namespace App\Modules\Exams;

use App\Core\Database;
use App\Core\Translator;
use App\Modules\Notifications\NotificationService;

final class ExamService
{
    public static function checkAvailability(array $exam, int $studentId, ?int $nowTs = null): array
    {
        $nowTs = $nowTs ?? time();
        if (($exam['exam_mode'] ?? 'online') !== 'online' || ($exam['status'] ?? '') !== 'published') {
            return ['allowed' => false, 'reason' => 'closed', 'message' => Translator::get('exam.closed')];
        }

        $startsTs = strtotime((string) $exam['starts_at']);
        $endsTs = strtotime((string) $exam['ends_at']);

        if ($nowTs < $startsTs) {
            return ['allowed' => false, 'reason' => 'not_started', 'message' => Translator::get('exam.not_started')];
        }
        if ($nowTs > $endsTs) {
            return ['allowed' => false, 'reason' => 'closed', 'message' => Translator::get('exam.window_closed')];
        }

        $completedAttempts = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM exam_attempts WHERE exam_id = ? AND student_id = ? AND status IN ('submitted','graded')",
            [(int) $exam['id'], $studentId]
        );
        if ($completedAttempts >= (int) $exam['max_attempts']) {
            return ['allowed' => false, 'reason' => 'attempts_exhausted', 'message' => Translator::get('exam.attempts_exhausted')];
        }

        return ['allowed' => true, 'reason' => 'ok', 'message' => ''];
    }

    public static function startOrResumeAttempt(array $exam, int $studentId, ?int $nowTs = null): array
    {
        $nowTs = $nowTs ?? time();
        $examId = (int) $exam['id'];

        // Resume active in-progress attempt if not expired
        $active = Database::fetch(
            "SELECT * FROM exam_attempts WHERE exam_id = ? AND student_id = ? AND status = 'in_progress' ORDER BY id DESC LIMIT 1",
            [$examId, $studentId]
        );
        if ($active) {
            $expTs = strtotime((string) $active['expires_at']);
            if ($nowTs <= $expTs) {
                return ['ok' => true, 'attempt' => $active];
            }
            // Auto-submit expired in-progress attempt
            self::submitAttempt((int) $active['id'], $studentId, [], $expTs);
        }

        $avail = self::checkAvailability($exam, $studentId, $nowTs);
        if (!$avail['allowed']) {
            return ['ok' => false, 'error' => $avail['message'], 'reason' => $avail['reason']];
        }

        $attemptNumber = (int) Database::fetchColumn(
            'SELECT COALESCE(MAX(attempt_number), 0) + 1 FROM exam_attempts WHERE exam_id = ? AND student_id = ?',
            [$examId, $studentId]
        );

        $questions = Database::fetchAll('SELECT id FROM exam_questions WHERE exam_id = ? ORDER BY sort_order ASC, id ASC', [$examId]);
        $qIds = array_map(static fn ($r) => (int) $r['id'], $questions);
        if ((int) ($exam['randomize_questions'] ?? 0) === 1) {
            shuffle($qIds);
        }

        $durationSec = max(60, (int) $exam['duration_minutes'] * 60);
        $rawExp = $nowTs + $durationSec;
        $endsTs = strtotime((string) $exam['ends_at']);
        if (($exam['late_finish_policy'] ?? 'hard_cutoff') === 'hard_cutoff') {
            $rawExp = min($rawExp, $endsTs);
        }

        $startedAt = date('Y-m-d H:i:s', $nowTs);
        $expiresAt = date('Y-m-d H:i:s', $rawExp);

        $attemptId = Database::insert(
            'INSERT INTO exam_attempts (exam_id, student_id, attempt_number, started_at, expires_at, question_order_json, status, auto_score, manual_score, total_score)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, 0)',
            [$examId, $studentId, $attemptNumber, $startedAt, $expiresAt, json_encode($qIds), 'in_progress']
        );

        return ['ok' => true, 'attempt' => Database::fetch('SELECT * FROM exam_attempts WHERE id = ?', [$attemptId])];
    }

    public static function submitAttempt(int $attemptId, int $studentId, array $submittedAnswers, ?int $nowTs = null): array
    {
        $nowTs = $nowTs ?? time();
        $attempt = Database::fetch('SELECT * FROM exam_attempts WHERE id = ? AND student_id = ?', [$attemptId, $studentId]);
        if (!$attempt) {
            return ['ok' => false, 'error' => 'Attempt not found.'];
        }
        if ($attempt['status'] !== 'in_progress') {
            return ['ok' => true, 'attempt' => $attempt];
        }

        $exam = Database::fetch('SELECT * FROM exams WHERE id = ?', [(int) $attempt['exam_id']]);
        if (!$exam) {
            return ['ok' => false, 'error' => 'Exam not found.'];
        }

        $expTs = strtotime((string) $attempt['expires_at']);
        $endsTs = strtotime((string) $exam['ends_at']);
        $hardDeadline = ($exam['late_finish_policy'] ?? 'hard_cutoff') === 'hard_cutoff'
            ? min($expTs, $endsTs) + 30
            : $expTs + 30;

        if ($nowTs > $hardDeadline) {
            return ['ok' => false, 'error' => Translator::get('exam.window_closed')];
        }

        $questions = Database::fetchAll('SELECT * FROM exam_questions WHERE exam_id = ?', [(int) $exam['id']]);
        $negRatio = (float) ($exam['negative_marking_ratio'] ?? 0.0);

        $autoScore = 0.0;
        $hasDescriptive = false;

        Database::transaction(static function () use (
            $attemptId,
            $exam,
            $studentId,
            $questions,
            $submittedAnswers,
            $negRatio,
            $nowTs,
            &$autoScore,
            &$hasDescriptive
        ): void {
            foreach ($questions as $q) {
                $qid = (int) $q['id'];
                $points = (float) $q['points'];
                $ans = $submittedAnswers[$qid] ?? null;

                if ($q['type'] === 'mcq') {
                    $selectedOptId = ($ans !== null && $ans !== '') ? (int) $ans : null;
                    $isCorrect = null;
                    $awarded = 0.0;

                    if ($selectedOptId !== null) {
                        $opt = Database::fetch(
                            'SELECT id, is_correct FROM exam_options WHERE id = ? AND question_id = ?',
                            [$selectedOptId, $qid]
                        );
                        if ($opt) {
                            if ((int) $opt['is_correct'] === 1) {
                                $isCorrect = 1;
                                $awarded = $points;
                            } else {
                                $isCorrect = 0;
                                $awarded = $negRatio > 0 ? -($points * $negRatio) : 0.0;
                            }
                        } else {
                            $selectedOptId = null;
                        }
                    }

                    $autoScore += $awarded;
                    self::upsertAnswer($attemptId, $qid, $selectedOptId, null, $isCorrect, $awarded);
                } else {
                    $hasDescriptive = true;
                    $text = is_string($ans) ? trim($ans) : '';
                    self::upsertAnswer($attemptId, $qid, null, $text, null, 0.0);
                }
            }

            $totalRawPoints = (float) array_sum(array_map(static fn ($q) => (float) $q['points'], $questions));
            $maxScore = (float) $exam['max_score'];
            $scaledAuto = $totalRawPoints > 0 ? round(max(0.0, ($autoScore / $totalRawPoints) * $maxScore), 2) : 0.0;

            $status = $hasDescriptive ? 'submitted' : 'graded';
            $nowStr = date('Y-m-d H:i:s', $nowTs);

            Database::execute(
                'UPDATE exam_attempts SET submitted_at = ?, status = ?, auto_score = ?, total_score = ? WHERE id = ?',
                [$nowStr, $status, $scaledAuto, $scaledAuto, $attemptId]
            );

            if (!$hasDescriptive) {
                self::publishAttemptResult((int) $exam['id'], $studentId, $attemptId, $scaledAuto, $maxScore, null);
            }
        });

        return [
            'ok' => true,
            'has_descriptive' => $hasDescriptive,
            'attempt' => Database::fetch('SELECT * FROM exam_attempts WHERE id = ?', [$attemptId]),
        ];
    }

    public static function gradeDescriptiveAttempt(int $attemptId, array $scoresByAnswerId, array $commentsByAnswerId, int $graderUserId): bool
    {
        $attempt = Database::fetch('SELECT * FROM exam_attempts WHERE id = ?', [$attemptId]);
        if (!$attempt) {
            return false;
        }
        $exam = Database::fetch('SELECT * FROM exams WHERE id = ?', [(int) $attempt['exam_id']]);
        if (!$exam) {
            return false;
        }

        Database::transaction(static function () use ($attemptId, $attempt, $exam, $scoresByAnswerId, $commentsByAnswerId, $graderUserId): void {
            foreach ($scoresByAnswerId as $ansId => $score) {
                $aid = (int) $ansId;
                $comment = trim((string) ($commentsByAnswerId[$ansId] ?? '')) ?: null;
                Database::execute(
                    'UPDATE exam_answers SET score_awarded = ?, teacher_comment = ? WHERE id = ? AND attempt_id = ?',
                    [max(0.0, (float) $score), $comment, $aid, $attemptId]
                );
            }

            $questionsTotal = (float) Database::fetchColumn(
                'SELECT COALESCE(SUM(points), 0) FROM exam_questions WHERE exam_id = ?',
                [(int) $exam['id']]
            );
            $rawEarned = (float) Database::fetchColumn(
                'SELECT COALESCE(SUM(score_awarded), 0) FROM exam_answers WHERE attempt_id = ?',
                [$attemptId]
            );
            $manualRaw = (float) Database::fetchColumn(
                "SELECT COALESCE(SUM(ea.score_awarded), 0)
                 FROM exam_answers ea
                 JOIN exam_questions eq ON eq.id = ea.question_id
                 WHERE ea.attempt_id = ? AND eq.type = 'descriptive'",
                [$attemptId]
            );

            $maxScore = (float) $exam['max_score'];
            $finalTotal = $questionsTotal > 0 ? round(max(0.0, min($maxScore, ($rawEarned / $questionsTotal) * $maxScore)), 2) : 0.0;
            $scaledManual = $questionsTotal > 0 ? round(($manualRaw / $questionsTotal) * $maxScore, 2) : 0.0;

            Database::execute(
                "UPDATE exam_attempts SET manual_score = ?, total_score = ?, status = 'graded' WHERE id = ?",
                [$scaledManual, $finalTotal, $attemptId]
            );

            self::publishAttemptResult((int) $exam['id'], (int) $attempt['student_id'], $attemptId, $finalTotal, $maxScore, $graderUserId);
        });

        return true;
    }

    public static function saveOfflineScore(int $examId, int $studentId, float $score, float $maxScore, ?string $remarks, int $graderUserId, bool $publish = true): void
    {
        $now = date('Y-m-d H:i:s');
        $clamped = max(0.0, min($maxScore, $score));
        $pct = $maxScore > 0 ? round(($clamped / $maxScore) * 100, 2) : 0.0;

        $existing = Database::fetch('SELECT id FROM exam_results WHERE exam_id = ? AND student_id = ?', [$examId, $studentId]);
        if ($existing) {
            Database::execute(
                'UPDATE exam_results SET score = ?, max_score = ?, percentage = ?, remarks = ?, graded_by = ?, published_at = ?, updated_at = ? WHERE id = ?',
                [$clamped, $maxScore, $pct, $remarks, $graderUserId, $publish ? $now : null, $now, (int) $existing['id']]
            );
        } else {
            Database::execute(
                'INSERT INTO exam_results (exam_id, student_id, attempt_id, score, max_score, percentage, remarks, graded_by, published_at, created_at, updated_at)
                 VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$examId, $studentId, $clamped, $maxScore, $pct, $remarks, $graderUserId, $publish ? $now : null, $now, $now]
            );
        }

        if ($publish) {
            self::dispatchExamResultNotifications($examId, $studentId, $clamped, $maxScore);
        }
    }

    private static function publishAttemptResult(int $examId, int $studentId, int $attemptId, float $score, float $maxScore, ?int $graderUserId): void
    {
        $now = date('Y-m-d H:i:s');
        $pct = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0.0;
        $existing = Database::fetch('SELECT id FROM exam_results WHERE exam_id = ? AND student_id = ?', [$examId, $studentId]);

        if ($existing) {
            Database::execute(
                'UPDATE exam_results SET attempt_id = ?, score = ?, max_score = ?, percentage = ?, graded_by = ?, published_at = ?, updated_at = ? WHERE id = ?',
                [$attemptId, $score, $maxScore, $pct, $graderUserId, $now, $now, (int) $existing['id']]
            );
        } else {
            Database::execute(
                'INSERT INTO exam_results (exam_id, student_id, attempt_id, score, max_score, percentage, graded_by, published_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$examId, $studentId, $attemptId, $score, $maxScore, $pct, $graderUserId, $now, $now, $now]
            );
        }

        Database::execute('UPDATE exams SET results_published = 1 WHERE id = ?', [$examId]);
        self::dispatchExamResultNotifications($examId, $studentId, $score, $maxScore);
    }

    private static function dispatchExamResultNotifications(int $examId, int $studentId, float $score, float $maxScore): void
    {
        $examTitle = (string) Database::fetchColumn('SELECT title FROM exams WHERE id = ?', [$examId]);
        $stu = Database::fetch('SELECT s.user_id, u.full_name FROM students s JOIN users u ON u.id = s.user_id WHERE s.id = ?', [$studentId]);
        if (!$stu) {
            return;
        }
        $msg = "{$examTitle}: {$score} / {$maxScore}";
        NotificationService::notifyUser((int) $stu['user_id'], 'exam_result', Translator::get('notif.exam_result_title'), $msg, '/student/exams');
        NotificationService::notifyParentsOfStudent(
            $studentId,
            'exam_result',
            Translator::get('notif.child_exam_result_title', ['name' => $stu['full_name']]),
            $msg,
            '/parent/exams?student_id=' . $studentId
        );
    }

    private static function upsertAnswer(int $attemptId, int $questionId, ?int $selectedOptId, ?string $answerText, ?int $isCorrect, float $awarded): void
    {
        $existing = Database::fetch('SELECT id FROM exam_answers WHERE attempt_id = ? AND question_id = ?', [$attemptId, $questionId]);
        if ($existing) {
            Database::execute(
                'UPDATE exam_answers SET selected_option_id = ?, answer_text = ?, is_correct = ?, score_awarded = ? WHERE id = ?',
                [$selectedOptId, $answerText, $isCorrect, $awarded, (int) $existing['id']]
            );
        } else {
            Database::execute(
                'INSERT INTO exam_answers (attempt_id, question_id, selected_option_id, answer_text, is_correct, score_awarded)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$attemptId, $questionId, $selectedOptId, $answerText, $isCorrect, $awarded]
            );
        }
    }
}
