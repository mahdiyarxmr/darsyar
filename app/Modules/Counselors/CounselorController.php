<?php

declare(strict_types=1);

namespace App\Modules\Counselors;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Translator;
use App\Core\Validator;
use App\Core\View;
use App\Modules\Attendance\AttendanceService;
use App\Modules\Authorization\OwnershipGuard;
use App\Modules\Counseling\CounselingService;
use App\Modules\Exams\DailyTestService;
use App\Modules\Reports\PerformanceRatingService;

final class CounselorController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $this->authUser();
        $counselor = OwnershipGuard::counselorProfile((int) $user['id']);
        $cid = (int) ($counselor['id'] ?? 0);

        $search = trim((string) $request->query('q', ''));
        $classFilter = (int) $request->query('class_id', 0);

        $sql = 'SELECT s.id, s.student_code, u.full_name, c.name AS class_name,
                       (SELECT COUNT(*) FROM counselor_assignments ca WHERE ca.counselor_id = ? AND ca.student_id = s.id) AS is_assigned
                FROM students s
                JOIN users u ON u.id = s.user_id
                LEFT JOIN classes c ON c.id = s.class_id
                WHERE 1=1';
        $params = [$cid];
        if ($search !== '') {
            $sql .= ' AND (u.full_name LIKE ? OR s.student_code LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($classFilter > 0) {
            $sql .= ' AND s.class_id = ?';
            $params[] = $classFilter;
        }
        $sql .= ' ORDER BY is_assigned DESC, u.full_name ASC';
        $students = Database::fetchAll($sql, $params);

        $atRisk = [];
        foreach ($students as &$stu) {
            $stu['completion_rate'] = CounselingService::studentOverallCompletion((int) $stu['id']);
            $stu['plan_count'] = (int) Database::fetchColumn('SELECT COUNT(*) FROM study_plans WHERE student_id = ?', [(int) $stu['id']]);
            if ($stu['plan_count'] > 0 && $stu['completion_rate'] < 50.0) {
                $atRisk[] = $stu;
            }
        }
        unset($stu);

        $requests = Database::fetchAll(
            "SELECT spr.*, u.full_name AS student_name, s.student_code
             FROM study_plan_requests spr
             JOIN students s ON s.id = spr.student_id
             JOIN users u ON u.id = s.user_id
             WHERE spr.status = 'pending'
             ORDER BY spr.id DESC"
        );

        return $this->view('panels/counselor/dashboard', [
            'title' => Translator::get('panel.counselor'),
            'students' => $students,
            'atRisk' => $atRisk,
            'requests' => $requests,
            'classes' => Database::fetchAll('SELECT * FROM classes ORDER BY name ASC'),
            'search' => $search,
            'classFilter' => $classFilter,
        ]);
    }

    public function assignStudent(Request $request): Response
    {
        $user = $this->authUser();
        $counselor = OwnershipGuard::counselorProfile((int) $user['id']);
        $cid = (int) ($counselor['id'] ?? 0);
        $studentId = (int) $request->post('student_id', 0);

        if ($cid > 0 && $studentId > 0) {
            $exists = (int) Database::fetchColumn(
                'SELECT COUNT(*) FROM counselor_assignments WHERE counselor_id = ? AND student_id = ?',
                [$cid, $studentId]
            );
            if ($exists === 0) {
                Database::execute(
                    'INSERT INTO counselor_assignments (counselor_id, student_id, assigned_at) VALUES (?, ?, ?)',
                    [$cid, $studentId, date('Y-m-d H:i:s')]
                );
            }
            $this->flash('success', Translator::get('counselor.student_assigned'));
        }
        return $this->redirect('/counselor/dashboard');
    }

    public function fulfillRequest(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        Database::execute(
            "UPDATE study_plan_requests SET status = 'fulfilled', updated_at = ? WHERE id = ?",
            [date('Y-m-d H:i:s'), $id]
        );
        $this->flash('success', Translator::get('counselor.request_fulfilled'));
        return $this->redirect('/counselor/dashboard');
    }

    public function studyPlans(Request $request): Response
    {
        $studentFilter = (int) $request->query('student_id', 0);
        $students = Database::fetchAll(
            'SELECT s.id, s.student_code, u.full_name, c.name AS class_name
             FROM students s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN classes c ON c.id = s.class_id
             ORDER BY u.full_name ASC'
        );
        $subjects = Database::fetchAll('SELECT * FROM subjects ORDER BY name ASC');

        $sql = 'SELECT sp.*, su.full_name AS student_name, s.student_code
                FROM study_plans sp
                JOIN students s ON s.id = sp.student_id
                JOIN users su ON su.id = s.user_id';
        $params = [];
        if ($studentFilter > 0) {
            $sql .= ' WHERE sp.student_id = ?';
            $params[] = $studentFilter;
        }
        $sql .= ' ORDER BY sp.id DESC';
        $plans = Database::fetchAll($sql, $params);

        foreach ($plans as &$p) {
            $p['tasks'] = Database::fetchAll(
                'SELECT t.*, sub.name AS subject_name FROM study_plan_tasks t LEFT JOIN subjects sub ON sub.id = t.subject_id WHERE t.study_plan_id = ? ORDER BY t.id ASC',
                [(int) $p['id']]
            );
            $p['progress'] = CounselingService::planProgress((int) $p['id']);
        }
        unset($p);

        return $this->view('panels/counselor/study_plans', [
            'title' => Translator::get('menu.study_plans'),
            'students' => $students,
            'subjects' => $subjects,
            'plans' => $plans,
            'studentFilter' => $studentFilter,
        ]);
    }

    public function createStudyPlan(Request $request): Response
    {
        $user = $this->authUser();
        $counselor = OwnershipGuard::counselorProfile((int) $user['id']);
        $cid = (int) ($counselor['id'] ?? 0);
        $data = $request->allPost();

        $v = new Validator();
        if (!$v->validate($data, [
            'student_id' => 'required|integer',
            'title' => 'required|min:2|max:160',
            'week_start_date' => 'required',
            'week_end_date' => 'required',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/counselor/study-plans');
        }

        $planId = CounselingService::createStudyPlan(
            (int) $data['student_id'],
            $cid,
            trim((string) $data['title']),
            $data['week_start_date'],
            $data['week_end_date'],
            trim((string) ($data['weekly_goal'] ?? '')) ?: null
        );

        $this->flash('success', Translator::get('counselor.plan_created'));
        return $this->redirect('/counselor/study-plans?student_id=' . (int) $data['student_id'] . '#plan-' . $planId);
    }

    public function addStudyTask(Request $request, array $params): Response
    {
        $planId = (int) ($params['id'] ?? 0);
        $data = $request->allPost();
        $v = new Validator();
        if (!$v->validate($data, [
            'day_of_week' => 'required|in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            'task_title' => 'required|min:2|max:200',
            'duration_minutes' => 'required|integer|min_val:5',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/counselor/study-plans');
        }

        CounselingService::addTask(
            $planId,
            $data['day_of_week'],
            ((int) ($data['subject_id'] ?? 0)) ?: null,
            trim((string) $data['task_title']),
            trim((string) ($data['start_time'] ?? '')) ?: null,
            trim((string) ($data['end_time'] ?? '')) ?: null,
            (int) $data['duration_minutes'],
            trim((string) ($data['daily_goal'] ?? '')) ?: null
        );

        $this->flash('success', Translator::get('counselor.task_added'));
        return $this->redirect('/counselor/study-plans');
    }

    public function deleteStudyTask(Request $request, array $params): Response
    {
        $taskId = (int) ($params['id'] ?? 0);
        Database::execute('DELETE FROM study_plan_tasks WHERE id = ?', [$taskId]);
        $this->flash('success', Translator::get('counselor.task_deleted'));
        return $this->redirect('/counselor/study-plans');
    }

    public function notes(Request $request): Response
    {
        $studentFilter = (int) $request->query('student_id', 0);
        $students = Database::fetchAll(
            'SELECT s.id, s.student_code, u.full_name, c.name AS class_name
             FROM students s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN classes c ON c.id = s.class_id
             ORDER BY u.full_name ASC'
        );

        $sql = 'SELECT cn.*, su.full_name AS student_name, s.student_code, cu.full_name AS counselor_name
                FROM counseling_notes cn
                JOIN students s ON s.id = cn.student_id
                JOIN users su ON su.id = s.user_id
                JOIN counselors c ON c.id = cn.counselor_id
                JOIN users cu ON cu.id = c.user_id';
        $params = [];
        if ($studentFilter > 0) {
            $sql .= ' WHERE cn.student_id = ?';
            $params[] = $studentFilter;
        }
        $sql .= ' ORDER BY cn.id DESC';
        $notes = Database::fetchAll($sql, $params);

        $analysis = $studentFilter > 0 ? $this->buildStudentAnalysis($studentFilter) : null;

        return $this->view('panels/counselor/notes', [
            'title' => Translator::get('menu.counseling_notes'),
            'students' => $students,
            'notes' => $notes,
            'studentFilter' => $studentFilter,
            'analysis' => $analysis,
        ]);
    }

    public function studentAnalysis(Request $request, array $params): Response
    {
        $studentId = (int) ($params['id'] ?? 0);
        $analysis = $this->buildStudentAnalysis($studentId);
        if (!$analysis) {
            return Response::html(View::render('errors/404', ['title' => '404'], 'public'), 404);
        }

        $notes = Database::fetchAll(
            'SELECT cn.*, cu.full_name AS counselor_name
             FROM counseling_notes cn
             JOIN counselors c ON c.id = cn.counselor_id
             JOIN users cu ON cu.id = c.user_id
             WHERE cn.student_id = ?
             ORDER BY cn.id DESC',
            [$studentId]
        );

        return $this->view('panels/counselor/student_analysis', [
            'title' => Translator::get('counselor.performance_analysis'),
            'analysis' => $analysis,
            'notes' => $notes,
        ]);
    }

    public function addNote(Request $request): Response
    {
        $user = $this->authUser();
        $counselor = OwnershipGuard::counselorProfile((int) $user['id']);
        $cid = (int) ($counselor['id'] ?? 0);
        $data = $request->allPost();

        $v = new Validator();
        if (!$v->validate($data, [
            'student_id' => 'required|integer',
            'title' => 'required|min:2|max:180',
            'note' => 'required|min:2',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/counselor/notes');
        }

        $now = date('Y-m-d H:i:s');
        Database::insert(
            'INSERT INTO counseling_notes (student_id, counselor_id, title, note, follow_up_date, visible_to_parent, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (int) $data['student_id'],
                $cid,
                trim((string) $data['title']),
                trim((string) $data['note']),
                trim((string) ($data['follow_up_date'] ?? '')) ?: null,
                isset($data['visible_to_parent']) ? 1 : 0,
                $now,
                $now,
            ]
        );

        $this->flash('success', Translator::get('counselor.note_saved'));
        return $this->redirect('/counselor/notes?student_id=' . (int) $data['student_id']);
    }

    private function buildStudentAnalysis(int $studentId): ?array
    {
        $student = Database::fetch(
            'SELECT s.*, u.full_name, c.name AS class_name, g.name AS grade_name
             FROM students s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN classes c ON c.id = s.class_id
             LEFT JOIN grades g ON g.id = c.grade_id
             WHERE s.id = ?',
            [$studentId]
        );
        if (!$student) {
            return null;
        }

        return [
            'student' => $student,
            'rating' => PerformanceRatingService::evaluateStudent($studentId),
            'attendance' => AttendanceService::studentAttendanceSummary($studentId),
            'dailyStats' => DailyTestService::studentSubjectStats($studentId),
            'exams' => Database::fetchAll(
                'SELECT er.*, e.title AS exam_title, s.name AS subject_name
                 FROM exam_results er
                 JOIN exams e ON e.id = er.exam_id
                 JOIN subjects s ON s.id = e.subject_id
                 WHERE er.student_id = ?
                 ORDER BY er.published_at DESC LIMIT 10',
                [$studentId]
            ),
        ];
    }
}
