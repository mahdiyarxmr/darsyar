<?php

declare(strict_types=1);

namespace App\Modules\Reports;

use App\Core\Database;
use App\Modules\Attendance\AttendanceService;
use App\Modules\Counseling\CounselingService;
use App\Modules\Settings\SettingsService;

final class PerformanceRatingService
{
    public static function studentWeights(): array
    {
        $s = SettingsService::all();
        return [
            'academic' => (float) ($s['student_weight_academic'] ?? 35),
            'homework' => (float) ($s['student_weight_homework'] ?? 20),
            'daily_tests' => (float) ($s['student_weight_daily_tests'] ?? 15),
            'study_plan' => (float) ($s['student_weight_study_plan'] ?? 15),
            'attendance' => (float) ($s['student_weight_attendance'] ?? 10),
            'improvement' => (float) ($s['student_weight_improvement'] ?? 5),
        ];
    }

    public static function teacherWeights(): array
    {
        $s = SettingsService::all();
        return [
            'homework' => (float) ($s['teacher_weight_homework'] ?? 25),
            'exams' => (float) ($s['teacher_weight_exams'] ?? 25),
            'improvement' => (float) ($s['teacher_weight_improvement'] ?? 25),
            'attendance' => (float) ($s['teacher_weight_attendance'] ?? 15),
            'activity' => (float) ($s['teacher_weight_activity'] ?? 10),
        ];
    }

    public static function evaluateStudent(int $studentId): array
    {
        $weights = self::studentWeights();
        $weightSum = max(1.0, array_sum($weights));

        $student = Database::fetch('SELECT s.*, u.full_name, c.name AS class_name FROM students s JOIN users u ON u.id = s.user_id LEFT JOIN classes c ON c.id = s.class_id WHERE s.id = ?', [$studentId]);
        $classId = (int) ($student['class_id'] ?? 0);

        // 1. Academic exam results average (chronological for improvement tracking)
        $results = Database::fetchAll(
            'SELECT percentage FROM exam_results WHERE student_id = ? ORDER BY created_at ASC, id ASC',
            [$studentId]
        );
        $examScores = array_map(static fn ($r) => (float) $r['percentage'], $results);
        $academicScore = count($examScores) > 0 ? round(array_sum($examScores) / count($examScores), 1) : 0.0;

        // Improvement: compares second half vs first half of assessments (base 50 + delta*2 clamped 0..100)
        $improvementScore = 50.0;
        if (count($examScores) >= 2) {
            $mid = (int) floor(count($examScores) / 2);
            $early = array_sum(array_slice($examScores, 0, $mid)) / $mid;
            $recent = array_sum(array_slice($examScores, $mid)) / (count($examScores) - $mid);
            $improvementScore = round(max(0.0, min(100.0, 50.0 + ($recent - $early) * 2.0)), 1);
        }

        // 2. Homework completion rate
        $totalHw = $classId > 0 ? (int) Database::fetchColumn('SELECT COUNT(*) FROM homework WHERE class_id = ?', [$classId]) : 0;
        $doneHw = (int) Database::fetchColumn('SELECT COUNT(*) FROM homework_submissions WHERE student_id = ?', [$studentId]);
        $homeworkScore = $totalHw > 0 ? round(min(100.0, ($doneHw / $totalHw) * 100), 1) : 100.0;

        // 3. Daily test participation & accuracy
        $dtRow = Database::fetch('SELECT COUNT(*) AS cnt, COALESCE(AVG(score_percentage), 0) AS avg_s FROM daily_test_attempts WHERE student_id = ?', [$studentId]);
        $dtCnt = (int) ($dtRow['cnt'] ?? 0);
        $dtScore = $dtCnt > 0 ? round(min(100.0, ((float) $dtRow['avg_s']) * 0.7 + min(30.0, $dtCnt * 6.0)), 1) : 0.0;

        // 4. Study plan completion
        $studyPlanScore = CounselingService::studentOverallCompletion($studentId);

        // 5. Attendance rate
        $att = AttendanceService::studentAttendanceSummary($studentId);
        $attendanceScore = $att['rate'];

        $weighted = (
            $academicScore * $weights['academic'] +
            $homeworkScore * $weights['homework'] +
            $dtScore * $weights['daily_tests'] +
            $studyPlanScore * $weights['study_plan'] +
            $attendanceScore * $weights['attendance'] +
            $improvementScore * $weights['improvement']
        ) / $weightSum;

        $total = round($weighted, 1);

        return [
            'student_id' => $studentId,
            'full_name' => $student['full_name'] ?? '',
            'student_code' => $student['student_code'] ?? '',
            'class_name' => $student['class_name'] ?? '-',
            'academic_score' => $academicScore,
            'homework_score' => $homeworkScore,
            'participation_score' => $dtScore,
            'study_plan_score' => $studyPlanScore,
            'attendance_score' => $attendanceScore,
            'improvement_score' => $improvementScore,
            'total_score' => $total,
            'category' => self::categoryFromScore($total),
        ];
    }

    public static function allStudentRankings(?int $classId = null): array
    {
        $sql = 'SELECT id FROM students';
        $params = [];
        if ($classId !== null && $classId > 0) {
            $sql .= ' WHERE class_id = ?';
            $params[] = $classId;
        }
        $rows = Database::fetchAll($sql, $params);
        $list = [];
        foreach ($rows as $r) {
            $list[] = self::evaluateStudent((int) $r['id']);
        }
        usort($list, static fn ($a, $b) => $b['total_score'] <=> $a['total_score']);
        return $list;
    }

    public static function evaluateTeacher(int $teacherId): array
    {
        $weights = self::teacherWeights();
        $weightSum = max(1.0, array_sum($weights));

        $teacher = Database::fetch('SELECT t.*, u.full_name FROM teachers t JOIN users u ON u.id = t.user_id WHERE t.id = ?', [$teacherId]);

        // 1. Homework checking consistency
        $totalSubs = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM homework_submissions hs JOIN homework h ON h.id = hs.homework_id WHERE h.teacher_id = ?',
            [$teacherId]
        );
        $gradedSubs = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM homework_feedback WHERE teacher_id = ?',
            [$teacherId]
        );
        $hwCheckScore = $totalSubs > 0 ? round(min(100.0, ($gradedSubs / $totalSubs) * 100), 1) : 100.0;

        // 2. Exam creation & management
        $examCount = (int) Database::fetchColumn('SELECT COUNT(*) FROM exams WHERE teacher_id = ?', [$teacherId]);
        $dtCount = (int) Database::fetchColumn('SELECT COUNT(*) FROM daily_tests WHERE teacher_id = ?', [$teacherId]);
        $examScore = round(min(100.0, ($examCount * 25.0) + ($dtCount * 15.0)), 1);

        // 3. Student academic average & improvement in teacher's exams
        $avgStudentPct = (float) Database::fetchColumn(
            'SELECT COALESCE(AVG(er.percentage), 0) FROM exam_results er JOIN exams e ON e.id = er.exam_id WHERE e.teacher_id = ?',
            [$teacherId]
        );
        $improvementScore = round(min(100.0, $avgStudentPct > 0 ? $avgStudentPct : 50.0), 1);

        // 4. Class attendance management
        $teacherUserId = (int) ($teacher['user_id'] ?? 0);
        $attSessions = (int) Database::fetchColumn('SELECT COUNT(DISTINCT attendance_date) FROM attendance WHERE recorded_by = ?', [$teacherUserId]);
        $attScore = round(min(100.0, $attSessions * 20.0 + 40.0), 1);

        // 5. Teaching activity (homework + online classrooms + discussions)
        $hwCreated = (int) Database::fetchColumn('SELECT COUNT(*) FROM homework WHERE teacher_id = ?', [$teacherId]);
        $classrooms = (int) Database::fetchColumn('SELECT COUNT(*) FROM online_class_sessions WHERE teacher_id = ?', [$teacherId]);
        $discussions = (int) Database::fetchColumn('SELECT COUNT(*) FROM discussion_groups WHERE teacher_id = ?', [$teacherId]);
        $activityScore = round(min(100.0, ($hwCreated * 20.0) + ($classrooms * 25.0) + ($discussions * 15.0)), 1);

        $total = round((
            $hwCheckScore * $weights['homework'] +
            $examScore * $weights['exams'] +
            $improvementScore * $weights['improvement'] +
            $attScore * $weights['attendance'] +
            $activityScore * $weights['activity']
        ) / $weightSum, 1);

        return [
            'teacher_id' => $teacherId,
            'full_name' => $teacher['full_name'] ?? '',
            'employee_code' => $teacher['employee_code'] ?? '',
            'specialization' => $teacher['specialization'] ?? '-',
            'homework_score' => $hwCheckScore,
            'exams_score' => $examScore,
            'improvement_score' => $improvementScore,
            'attendance_score' => $attScore,
            'activity_score' => $activityScore,
            'total_score' => $total,
            'category' => self::categoryFromScore($total),
        ];
    }

    public static function allTeacherRankings(): array
    {
        $rows = Database::fetchAll('SELECT id FROM teachers');
        $list = [];
        foreach ($rows as $r) {
            $list[] = self::evaluateTeacher((int) $r['id']);
        }
        usort($list, static fn ($a, $b) => $b['total_score'] <=> $a['total_score']);
        return $list;
    }

    public static function categoryFromScore(float $score): string
    {
        if ($score >= 85) {
            return 'Excellent';
        }
        if ($score >= 70) {
            return 'Very Good';
        }
        if ($score >= 55) {
            return 'Good';
        }
        return 'Needs Improvement';
    }
}
