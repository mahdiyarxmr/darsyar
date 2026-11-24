<?php

declare(strict_types=1);

namespace App\Modules\Reports;

use App\Core\Database;
use App\Modules\Attendance\AttendanceService;
use App\Modules\Notifications\NotificationService;

final class ReportCardService
{
    public static function generateForClass(
        int $classId,
        int $academicYearId,
        ?int $semesterId,
        string $reportType,
        string $periodLabel,
        int $generatedByUserId
    ): int {
        if (!in_array($reportType, ['monthly', 'semester', 'annual'], true)) {
            $reportType = 'monthly';
        }

        $students = Database::fetchAll('SELECT s.id, s.user_id, u.full_name FROM students s JOIN users u ON u.id = s.user_id WHERE s.class_id = ?', [$classId]);
        $subjects = Database::fetchAll(
            'SELECT cs.subject_id, s.name AS subject_name,
                    (SELECT ta.teacher_id FROM teacher_assignments ta WHERE ta.class_id = cs.class_id AND ta.subject_id = cs.subject_id LIMIT 1) AS teacher_id
             FROM class_subjects cs
             JOIN subjects s ON s.id = cs.subject_id
             WHERE cs.class_id = ?',
            [$classId]
        );

        $now = date('Y-m-d H:i:s');
        $count = 0;

        Database::transaction(static function () use (
            $students,
            $subjects,
            $classId,
            $academicYearId,
            $semesterId,
            $reportType,
            $periodLabel,
            $generatedByUserId,
            $now,
            &$count
        ): void {
            foreach ($students as $stu) {
                $sid = (int) $stu['id'];
                $att = AttendanceService::studentAttendanceSummary($sid);
                $attRate = $att['rate'];

                $totalHw = (int) Database::fetchColumn('SELECT COUNT(*) FROM homework WHERE class_id = ?', [$classId]);
                $submittedHw = (int) Database::fetchColumn(
                    'SELECT COUNT(*) FROM homework_submissions hs JOIN homework h ON h.id = hs.homework_id WHERE hs.student_id = ? AND h.class_id = ?',
                    [$sid, $classId]
                );
                $hwRate = $totalHw > 0 ? round(($submittedHw / $totalHw) * 100, 1) : 100.0;

                $items = [];
                $gradeSum = 0.0;
                $gradeCount = 0;

                foreach ($subjects as $subj) {
                    $subId = (int) $subj['subject_id'];
                    $examAvg = (float) Database::fetchColumn(
                        'SELECT COALESCE(AVG(er.percentage), 0)
                         FROM exam_results er
                         JOIN exams e ON e.id = er.exam_id
                         WHERE er.student_id = ? AND e.subject_id = ?',
                        [$sid, $subId]
                    );
                    $examCnt = (int) Database::fetchColumn(
                        'SELECT COUNT(*)
                         FROM exam_results er
                         JOIN exams e ON e.id = er.exam_id
                         WHERE er.student_id = ? AND e.subject_id = ?',
                        [$sid, $subId]
                    );

                    $hwAvg = (float) Database::fetchColumn(
                        'SELECT COALESCE(AVG((hf.score * 100.0) / h.max_score), 0)
                         FROM homework_feedback hf
                         JOIN homework_submissions hs ON hs.id = hf.submission_id
                         JOIN homework h ON h.id = hs.homework_id
                         WHERE hs.student_id = ? AND h.subject_id = ?',
                        [$sid, $subId]
                    );
                    $hwCnt = (int) Database::fetchColumn(
                        'SELECT COUNT(*)
                         FROM homework_feedback hf
                         JOIN homework_submissions hs ON hs.id = hf.submission_id
                         JOIN homework h ON h.id = hs.homework_id
                         WHERE hs.student_id = ? AND h.subject_id = ?',
                        [$sid, $subId]
                    );

                    if ($examCnt > 0 && $hwCnt > 0) {
                        $subjectGrade = round(($examAvg * 0.75) + ($hwAvg * 0.25), 2);
                    } elseif ($examCnt > 0) {
                        $subjectGrade = round($examAvg, 2);
                    } elseif ($hwCnt > 0) {
                        $subjectGrade = round($hwAvg, 2);
                    } else {
                        $subjectGrade = 0.0;
                    }

                    $items[] = [
                        'subject_id' => $subId,
                        'teacher_id' => $subj['teacher_id'] ? (int) $subj['teacher_id'] : null,
                        'subject_grade' => $subjectGrade,
                        'exam_average' => round($examAvg, 2),
                        'homework_score' => round($hwAvg, 2),
                    ];
                    if ($examCnt > 0 || $hwCnt > 0) {
                        $gradeSum += $subjectGrade;
                        $gradeCount++;
                    }
                }

                $finalAvg = $gradeCount > 0 ? round($gradeSum / $gradeCount, 2) : 0.0;
                $status = self::statusFromAverage($finalAvg);

                $rcId = Database::insert(
                    'INSERT INTO report_cards (student_id, academic_year_id, semester_id, report_type, period_label, attendance_rate, homework_rate, final_average, academic_status, is_published, generated_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)',
                    [$sid, $academicYearId, $semesterId, $reportType, $periodLabel, $attRate, $hwRate, $finalAvg, $status, $generatedByUserId, $now, $now]
                );

                foreach ($items as $it) {
                    Database::execute(
                        'INSERT INTO report_card_items (report_card_id, subject_id, teacher_id, subject_grade, exam_average, homework_score, remarks)
                         VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [$rcId, $it['subject_id'], $it['teacher_id'], $it['subject_grade'], $it['exam_average'], $it['homework_score'], self::statusFromAverage($it['subject_grade'])]
                    );
                }

                NotificationService::notifyUser(
                    (int) $stu['user_id'],
                    'report_card',
                    \App\Core\Translator::get('notif.report_card_title'),
                    "{$periodLabel} — {$finalAvg}%",
                    '/report-cards/' . $rcId . '/print'
                );
                NotificationService::notifyParentsOfStudent(
                    $sid,
                    'report_card',
                    \App\Core\Translator::get('notif.report_card_child_title', ['name' => $stu['full_name']]),
                    "{$periodLabel} — {$finalAvg}%",
                    '/report-cards/' . $rcId . '/print'
                );
                $count++;
            }
        });

        return $count;
    }

    public static function findWithItems(int $reportCardId): ?array
    {
        $card = Database::fetch(
            'SELECT rc.*, u.full_name AS student_name, s.student_code, s.user_id AS student_user_id,
                    c.name AS class_name, g.name AS grade_name, ay.name AS year_name
             FROM report_cards rc
             JOIN students s ON s.id = rc.student_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN classes c ON c.id = s.class_id
             LEFT JOIN grades g ON g.id = c.grade_id
             JOIN academic_years ay ON ay.id = rc.academic_year_id
             WHERE rc.id = ?',
            [$reportCardId]
        );
        if (!$card) {
            return null;
        }
        $card['items'] = Database::fetchAll(
            'SELECT rci.*, subj.name AS subject_name, subj.code AS subject_code, tu.full_name AS teacher_name
             FROM report_card_items rci
             JOIN subjects subj ON subj.id = rci.subject_id
             LEFT JOIN teachers t ON t.id = rci.teacher_id
             LEFT JOIN users tu ON tu.id = t.user_id
             WHERE rci.report_card_id = ?
             ORDER BY subj.name ASC',
            [$reportCardId]
        );
        return $card;
    }

    public static function statusFromAverage(float $avg): string
    {
        if ($avg >= 90) {
            return 'Excellent';
        }
        if ($avg >= 80) {
            return 'Very Good';
        }
        if ($avg >= 65) {
            return 'Good';
        }
        return 'Needs Improvement';
    }
}
