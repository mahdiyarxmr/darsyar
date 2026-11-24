<?php

declare(strict_types=1);

namespace App\Modules\Administrators;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Translator;
use App\Core\Validator;
use App\Modules\AuditLogs\AuditLogService;
use App\Modules\Auth\AuthService;
use App\Modules\Notifications\NotificationService;
use App\Modules\Reports\PerformanceRatingService;
use App\Modules\Settings\SettingsService;

final class DirectorController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $stats = [
            'students' => (int) Database::fetchColumn('SELECT COUNT(*) FROM students'),
            'teachers' => (int) Database::fetchColumn('SELECT COUNT(*) FROM teachers'),
            'exams_total' => (int) Database::fetchColumn('SELECT COUNT(*) FROM exams'),
            'avg_exam_pct' => round((float) Database::fetchColumn('SELECT COALESCE(AVG(percentage), 0) FROM exam_results'), 1),
            'homework_subs' => (int) Database::fetchColumn('SELECT COUNT(*) FROM homework_submissions'),
            'daily_test_attempts' => (int) Database::fetchColumn('SELECT COUNT(*) FROM daily_test_attempts'),
        ];

        $classStats = $this->classComparisons();
        $topTeachers = array_slice(PerformanceRatingService::allTeacherRankings(), 0, 5);
        $topStudents = array_slice(PerformanceRatingService::allStudentRankings(), 0, 5);

        return $this->view('panels/director/dashboard', [
            'title' => Translator::get('panel.director'),
            'stats' => $stats,
            'classStats' => $classStats,
            'topTeachers' => $topTeachers,
            'topStudents' => $topStudents,
        ]);
    }

    public function vicePrincipals(Request $request): Response
    {
        $vps = Database::fetchAll(
            "SELECT u.*, a.title, a.department
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             LEFT JOIN administrators a ON a.user_id = u.id
             WHERE r.slug = 'vice_principal'
             ORDER BY u.id DESC"
        );

        return $this->view('panels/director/vice_principals', [
            'title' => Translator::get('menu.vice_principals'),
            'vicePrincipals' => $vps,
        ]);
    }

    public function createVicePrincipal(Request $request): Response
    {
        $data = $request->allPost();
        $v = new Validator();
        if (!$v->validate($data, [
            'full_name' => 'required|min:2|max:150',
            'username' => 'required|min:3|max:80',
            'password' => 'required|min:8|max:128',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/director/vice-principals');
        }

        if (Database::fetch('SELECT id FROM users WHERE username = ?', [trim((string) $data['username'])])) {
            $this->flash('error', Translator::get('admin.username_exists'));
            return $this->redirect('/director/vice-principals');
        }

        $now = date('Y-m-d H:i:s');
        $authUser = $this->authUser();

        $uid = Database::transaction(static function () use ($data, $now): int {
            $id = Database::insert(
                'INSERT INTO users (username, email, password_hash, full_name, locale, theme, status, must_change_password, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)',
                [
                    trim((string) $data['username']),
                    trim((string) ($data['email'] ?? '')) ?: null,
                    AuthService::hashPassword((string) $data['password']),
                    trim((string) $data['full_name']),
                    'fa',
                    'light',
                    'active',
                    $now,
                    $now,
                ]
            );
            $roleId = (int) Database::fetchColumn("SELECT id FROM roles WHERE slug = 'vice_principal'");
            Database::execute('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)', [$id, $roleId]);
            Database::insert('INSERT INTO administrators (user_id, title, department) VALUES (?, ?, ?)', [$id, Translator::get('roles.vice_principal'), trim((string) ($data['department'] ?? ''))]);
            return $id;
        });

        AuditLogService::log((int) $authUser['id'], 'director.vp_created', 'user', $uid);
        $this->flash('success', Translator::get('director.vp_created'));
        return $this->redirect('/director/vice-principals');
    }

    public function toggleVicePrincipal(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        $vp = Database::fetch(
            "SELECT u.id, u.status FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id WHERE u.id = ? AND r.slug = 'vice_principal'",
            [$id]
        );
        if ($vp) {
            $next = $vp['status'] === 'active' ? 'inactive' : 'active';
            Database::execute('UPDATE users SET status = ?, updated_at = ? WHERE id = ?', [$next, date('Y-m-d H:i:s'), $id]);
            $authUser = $this->authUser();
            AuditLogService::log((int) $authUser['id'], 'director.vp_toggled', 'user', $id, ['status' => $next]);
            $this->flash('success', Translator::get('admin.status_updated'));
        }
        return $this->redirect('/director/vice-principals');
    }

    public function teacherRatings(Request $request): Response
    {
        $rankings = PerformanceRatingService::allTeacherRankings();
        return $this->view('panels/director/teacher_ratings', [
            'title' => Translator::get('menu.teacher_ratings'),
            'rankings' => $rankings,
            'weights' => PerformanceRatingService::teacherWeights(),
        ]);
    }

    public function saveTeacherWeights(Request $request): Response
    {
        $d = $request->allPost();
        SettingsService::setMany([
            'teacher_weight_homework' => (string) max(0, (float) ($d['homework'] ?? 25)),
            'teacher_weight_exams' => (string) max(0, (float) ($d['exams'] ?? 25)),
            'teacher_weight_improvement' => (string) max(0, (float) ($d['improvement'] ?? 25)),
            'teacher_weight_attendance' => (string) max(0, (float) ($d['attendance'] ?? 15)),
            'teacher_weight_activity' => (string) max(0, (float) ($d['activity'] ?? 10)),
        ], 'ratings');
        $this->flash('success', Translator::get('director.weights_updated'));
        return $this->redirect('/director/teacher-ratings');
    }

    public function studentRatings(Request $request): Response
    {
        $classId = (int) $request->query('class_id', 0);
        $rankings = PerformanceRatingService::allStudentRankings($classId > 0 ? $classId : null);
        return $this->view('panels/director/student_ratings', [
            'title' => Translator::get('menu.student_ratings'),
            'rankings' => $rankings,
            'weights' => PerformanceRatingService::studentWeights(),
            'classes' => Database::fetchAll('SELECT * FROM classes ORDER BY name ASC'),
            'selectedClassId' => $classId,
        ]);
    }

    public function saveStudentWeights(Request $request): Response
    {
        $d = $request->allPost();
        SettingsService::setMany([
            'student_weight_academic' => (string) max(0, (float) ($d['academic'] ?? 35)),
            'student_weight_homework' => (string) max(0, (float) ($d['homework'] ?? 20)),
            'student_weight_daily_tests' => (string) max(0, (float) ($d['daily_tests'] ?? 15)),
            'student_weight_study_plan' => (string) max(0, (float) ($d['study_plan'] ?? 15)),
            'student_weight_attendance' => (string) max(0, (float) ($d['attendance'] ?? 10)),
            'student_weight_improvement' => (string) max(0, (float) ($d['improvement'] ?? 5)),
        ], 'ratings');
        $this->flash('success', Translator::get('director.weights_updated'));
        return $this->redirect('/director/student-ratings');
    }

    public function classComparison(Request $request): Response
    {
        return $this->view('panels/director/class_comparison', [
            'title' => Translator::get('menu.class_comparison'),
            'classStats' => $this->classComparisons(),
        ]);
    }

    public function reports(Request $request): Response
    {
        $subjectStats = Database::fetchAll(
            'SELECT s.code, s.name AS subject_name,
                    COUNT(DISTINCT e.id) AS exam_count,
                    ROUND(COALESCE(AVG(er.percentage), 0), 1) AS avg_pct
             FROM subjects s
             LEFT JOIN exams e ON e.subject_id = s.id
             LEFT JOIN exam_results er ON er.exam_id = e.id
             GROUP BY s.id, s.code, s.name
             ORDER BY avg_pct DESC'
        );

        $attendanceBreakdown = Database::fetchAll(
            'SELECT status, COUNT(*) AS cnt FROM attendance GROUP BY status'
        );

        $reportCardStats = Database::fetchAll(
            'SELECT report_type, COUNT(*) AS cnt, ROUND(COALESCE(AVG(final_average), 0), 1) AS avg_score
             FROM report_cards
             GROUP BY report_type'
        );

        return $this->view('panels/director/reports', [
            'title' => Translator::get('menu.reports'),
            'subjectStats' => $subjectStats,
            'attendanceBreakdown' => $attendanceBreakdown,
            'reportCardStats' => $reportCardStats,
            'classStats' => $this->classComparisons(),
        ]);
    }

    public function exportReportsCsv(Request $request): Response
    {
        $rows = [];
        foreach ($this->classComparisons() as $c) {
            $rows[] = [
                $c['class_name'],
                $c['grade_name'],
                $c['student_count'],
                $c['avg_exam_pct'] . '%',
                $c['daily_test_count'],
            ];
        }
        return Response::csv(
            'darsyar_school_report_' . date('Ymd_His') . '.csv',
            ['Class', 'Grade', 'Students', 'Exam Average', 'Daily Practice Attempts'],
            $rows
        );
    }

    public function announcements(Request $request): Response
    {
        $announcements = Database::fetchAll(
            'SELECT a.*, u.full_name AS author_name FROM announcements a LEFT JOIN users u ON u.id = a.author_id ORDER BY a.id DESC'
        );
        return $this->view('panels/director/announcements', [
            'title' => Translator::get('menu.announcements'),
            'announcements' => $announcements,
        ]);
    }

    public function createAnnouncement(Request $request): Response
    {
        $data = $request->allPost();
        $v = new Validator();
        if (!$v->validate($data, [
            'title' => 'required|min:2|max:180',
            'body' => 'required|min:2',
            'target_role' => 'required|in:all,teachers,students,parents',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/director/announcements');
        }

        $authUser = $this->authUser();
        $now = date('Y-m-d H:i:s');
        $id = Database::insert(
            'INSERT INTO announcements (author_id, target_role, title, body, is_public, published_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, 0, ?, ?, ?)',
            [(int) $authUser['id'], $data['target_role'], trim((string) $data['title']), trim((string) $data['body']), $now, $now, $now]
        );

        $roleFilter = match ($data['target_role']) {
            'teachers' => "('teacher')",
            'students' => "('student')",
            'parents' => "('parent')",
            default => "('vice_principal','counselor','teacher','student','parent')",
        };
        $targetUsers = Database::fetchAll(
            "SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id WHERE u.status = 'active' AND r.slug IN {$roleFilter}"
        );
        foreach ($targetUsers as $u) {
            NotificationService::notifyUser((int) $u['id'], 'announcement', trim((string) $data['title']), mb_substr(trim((string) $data['body']), 0, 140), '/notifications');
        }

        AuditLogService::log((int) $authUser['id'], 'director.announcement_created', 'announcement', $id);
        $this->flash('success', Translator::get('admin.announcement_published'));
        return $this->redirect('/director/announcements');
    }

    public function timetables(Request $request): Response
    {
        $timetables = Database::fetchAll(
            'SELECT t.*, c.name AS class_name, ay.name AS year_name
             FROM timetables t
             JOIN classes c ON c.id = t.class_id
             JOIN academic_years ay ON ay.id = t.academic_year_id
             ORDER BY t.id DESC'
        );
        return $this->view('panels/vice_principal/timetables', [
            'title' => Translator::get('menu.timetables'),
            'timetables' => $timetables,
            'classes' => Database::fetchAll('SELECT * FROM classes ORDER BY name ASC'),
            'years' => Database::fetchAll('SELECT * FROM academic_years ORDER BY id DESC'),
        ]);
    }

    private function classComparisons(): array
    {
        return Database::fetchAll(
            'SELECT c.id, c.name AS class_name, g.name AS grade_name,
                    (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id) AS student_count,
                    (SELECT ROUND(COALESCE(AVG(er.percentage), 0), 1) FROM exam_results er JOIN students s ON s.id = er.student_id WHERE s.class_id = c.id) AS avg_exam_pct,
                    (SELECT COUNT(*) FROM daily_test_attempts dta JOIN students s ON s.id = dta.student_id WHERE s.class_id = c.id) AS daily_test_count
             FROM classes c
             JOIN grades g ON g.id = c.grade_id
             ORDER BY c.name ASC'
        );
    }
}
