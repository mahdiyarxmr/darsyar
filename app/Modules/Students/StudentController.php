<?php

declare(strict_types=1);

namespace App\Modules\Students;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Translator;
use App\Core\Uploader;
use App\Modules\Attendance\AttendanceService;
use App\Modules\Authorization\OwnershipGuard;
use App\Modules\Classrooms\SkyroomService;
use App\Modules\Counseling\CounselingService;
use App\Modules\Exams\DailyTestService;
use App\Modules\Exams\ExamService;
use App\Modules\Homework\HomeworkService;
use App\Modules\Reports\PerformanceRatingService;

final class StudentController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);
        $cid = (int) ($student['class_id'] ?? 0);

        $rating = $sid > 0 ? PerformanceRatingService::evaluateStudent($sid) : [];
        $upcomingExams = $cid > 0 ? Database::fetchAll(
            "SELECT e.*, s.name AS subject_name FROM exams e JOIN subjects s ON s.id = e.subject_id WHERE e.class_id = ? AND e.status = 'published' ORDER BY e.starts_at DESC LIMIT 5",
            [$cid]
        ) : [];
        $recentResults = Database::fetchAll(
            'SELECT er.*, e.title AS exam_title, s.name AS subject_name FROM exam_results er JOIN exams e ON e.id = er.exam_id JOIN subjects s ON s.id = e.subject_id WHERE er.student_id = ? ORDER BY er.published_at DESC LIMIT 5',
            [$sid]
        );
        $dailyStats = $sid > 0 ? DailyTestService::studentSubjectStats($sid) : [];

        return $this->view('panels/student/dashboard', [
            'title' => Translator::get('panel.student'),
            'student' => $student,
            'rating' => $rating,
            'upcomingExams' => $upcomingExams,
            'recentResults' => $recentResults,
            'dailyStats' => $dailyStats,
        ]);
    }

    public function schedule(Request $request): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $cid = (int) ($student['class_id'] ?? 0);

        $timetables = $cid > 0 ? Database::fetchAll(
            'SELECT t.*, ay.name AS year_name FROM timetables t JOIN academic_years ay ON ay.id = t.academic_year_id WHERE t.class_id = ? ORDER BY t.id DESC',
            [$cid]
        ) : [];
        $subjects = $cid > 0 ? Database::fetchAll(
            'SELECT s.*, u.full_name AS teacher_name
             FROM class_subjects cs
             JOIN subjects s ON s.id = cs.subject_id
             LEFT JOIN teacher_assignments ta ON ta.class_id = cs.class_id AND ta.subject_id = cs.subject_id
             LEFT JOIN teachers t ON t.id = ta.teacher_id
             LEFT JOIN users u ON u.id = t.user_id
             WHERE cs.class_id = ?',
            [$cid]
        ) : [];

        return $this->view('panels/student/schedule', [
            'title' => Translator::get('menu.schedule'),
            'timetables' => $timetables,
            'subjects' => $subjects,
        ]);
    }

    public function exams(Request $request): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);
        $cid = (int) ($student['class_id'] ?? 0);

        $exams = $cid > 0 ? Database::fetchAll(
            "SELECT e.*, s.name AS subject_name,
                    (SELECT er.score FROM exam_results er WHERE er.exam_id = e.id AND er.student_id = ? LIMIT 1) AS my_score,
                    (SELECT er.percentage FROM exam_results er WHERE er.exam_id = e.id AND er.student_id = ? LIMIT 1) AS my_pct,
                    (SELECT COUNT(*) FROM exam_attempts ea WHERE ea.exam_id = e.id AND ea.student_id = ? AND ea.status IN ('submitted','graded')) AS attempts_used
             FROM exams e
             JOIN subjects s ON s.id = e.subject_id
             WHERE e.class_id = ? AND e.status = 'published'
             ORDER BY e.starts_at DESC",
            [$sid, $sid, $sid, $cid]
        ) : [];

        return $this->view('panels/student/exams', [
            'title' => Translator::get('menu.exams'),
            'exams' => $exams,
            'studentId' => $sid,
        ]);
    }

    public function takeExam(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);
        $examId = (int) ($params['id'] ?? 0);

        $exam = Database::fetch('SELECT e.*, s.name AS subject_name FROM exams e JOIN subjects s ON s.id = e.subject_id WHERE e.id = ?', [$examId]);
        if (!$exam || (int) $exam['class_id'] !== (int) ($student['class_id'] ?? 0)) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $res = ExamService::startOrResumeAttempt($exam, $sid);
        if (!$res['ok']) {
            $this->flash('error', $res['error']);
            return $this->redirect('/student/exams');
        }

        $attempt = $res['attempt'];
        $order = json_decode((string) ($attempt['question_order_json'] ?? '[]'), true) ?: [];
        $questions = Database::fetchAll('SELECT * FROM exam_questions WHERE exam_id = ?', [$examId]);
        $byId = [];
        foreach ($questions as $q) {
            $opts = Database::fetchAll('SELECT id, option_text FROM exam_options WHERE question_id = ? ORDER BY sort_order ASC', [(int) $q['id']]);
            if ((int) ($exam['randomize_options'] ?? 0) === 1) {
                shuffle($opts);
            }
            $q['options'] = $opts;
            $byId[(int) $q['id']] = $q;
        }
        $orderedQuestions = [];
        foreach ($order as $qid) {
            if (isset($byId[$qid])) {
                $orderedQuestions[] = $byId[$qid];
            }
        }
        if ($orderedQuestions === []) {
            $orderedQuestions = array_values($byId);
        }

        return $this->view('panels/student/exam_take', [
            'title' => $exam['title'],
            'exam' => $exam,
            'attempt' => $attempt,
            'questions' => $orderedQuestions,
        ]);
    }

    public function submitExam(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);
        $attemptId = (int) $request->post('attempt_id', 0);
        $answers = $request->post('answers', []);

        $res = ExamService::submitAttempt($attemptId, $sid, is_array($answers) ? $answers : []);
        if (!$res['ok']) {
            $this->flash('error', $res['error']);
            return $this->redirect('/student/exams');
        }

        $msg = $res['has_descriptive']
            ? Translator::get('exam.submitted') . ' ' . Translator::get('exam.descriptive_awaiting')
            : Translator::get('exam.submitted') . ' ' . Translator::get('exam.auto_graded');
        $this->flash('success', $msg);
        return $this->redirect('/student/exams');
    }

    public function examResult(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);
        $examId = (int) ($params['id'] ?? 0);

        $exam = Database::fetch('SELECT e.*, s.name AS subject_name FROM exams e JOIN subjects s ON s.id = e.subject_id WHERE e.id = ?', [$examId]);
        if (!$exam || (int) $exam['class_id'] !== (int) ($student['class_id'] ?? 0)) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $attempt = Database::fetch(
            "SELECT * FROM exam_attempts WHERE exam_id = ? AND student_id = ? AND status IN ('submitted','graded') ORDER BY id DESC LIMIT 1",
            [$examId, $sid]
        );
        $result = Database::fetch('SELECT * FROM exam_results WHERE exam_id = ? AND student_id = ? LIMIT 1', [$examId, $sid]);
        $answers = $attempt ? Database::fetchAll(
            'SELECT ea.*, eq.type, eq.prompt, eq.points, eo.option_text AS selected_option_text
             FROM exam_answers ea
             JOIN exam_questions eq ON eq.id = ea.question_id
             LEFT JOIN exam_options eo ON eo.id = ea.selected_option_id
             WHERE ea.attempt_id = ?
             ORDER BY eq.sort_order ASC',
            [(int) $attempt['id']]
        ) : [];

        return $this->view('panels/student/exam_result', [
            'title' => Translator::get('student.exam_result_review'),
            'exam' => $exam,
            'attempt' => $attempt,
            'result' => $result,
            'answers' => $answers,
        ]);
    }

    public function dailyTests(Request $request): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);
        $cid = (int) ($student['class_id'] ?? 0);

        $tests = Database::fetchAll(
            'SELECT dt.*, s.name AS subject_name,
                    (SELECT COUNT(*) FROM daily_test_questions q WHERE q.daily_test_id = dt.id) AS q_count
             FROM daily_tests dt
             JOIN subjects s ON s.id = dt.subject_id
             WHERE dt.is_active = 1 AND (dt.class_id IS NULL OR dt.class_id = ?)
             ORDER BY dt.id DESC',
            [$cid]
        );
        $history = Database::fetchAll(
            'SELECT dta.*, dt.title, s.name AS subject_name
             FROM daily_test_attempts dta
             JOIN daily_tests dt ON dt.id = dta.daily_test_id
             JOIN subjects s ON s.id = dt.subject_id
             WHERE dta.student_id = ?
             ORDER BY dta.completed_at DESC LIMIT 15',
            [$sid]
        );
        $subjectStats = DailyTestService::studentSubjectStats($sid);

        return $this->view('panels/student/daily_tests', [
            'title' => Translator::get('menu.daily_tests'),
            'tests' => $tests,
            'history' => $history,
            'subjectStats' => $subjectStats,
        ]);
    }

    public function takeDailyTest(Request $request, array $params): Response
    {
        $dtId = (int) ($params['id'] ?? 0);
        $test = Database::fetch('SELECT dt.*, s.name AS subject_name FROM daily_tests dt JOIN subjects s ON s.id = dt.subject_id WHERE dt.id = ?', [$dtId]);
        if (!$test) {
            return Response::html(\App\Core\View::render('errors/404', ['title' => '404'], 'public'), 404);
        }
        $questions = Database::fetchAll('SELECT * FROM daily_test_questions WHERE daily_test_id = ? ORDER BY id ASC', [$dtId]);
        return $this->view('panels/student/daily_test_take', [
            'title' => $test['title'],
            'test' => $test,
            'questions' => $questions,
        ]);
    }

    public function submitDailyTest(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);
        $dtId = (int) ($params['id'] ?? 0);

        $answers = $request->post('answers', []);
        $startedTs = (int) $request->post('started_ts', time());
        $spent = max(1, time() - $startedTs);

        $res = DailyTestService::submitPracticeAttempt($dtId, $sid, is_array($answers) ? $answers : [], $spent);
        $this->flash('success', Translator::get('student.daily_completed_msg', [
            'correct' => $res['correct_count'],
            'total' => $res['total_questions'],
            'pct' => $res['score_percentage'],
        ]));
        return $this->redirect('/student/daily-tests');
    }

    public function homework(Request $request): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);
        $cid = (int) ($student['class_id'] ?? 0);

        $items = $cid > 0 ? Database::fetchAll(
            'SELECT h.*, s.name AS subject_name,
                    hs.id AS submission_id, hs.status AS sub_status, hs.submitted_at, hs.submission_text,
                    hf.score AS graded_score, hf.feedback_text, hf.decision
             FROM homework h
             JOIN subjects s ON s.id = h.subject_id
             LEFT JOIN homework_submissions hs ON hs.homework_id = h.id AND hs.student_id = ?
             LEFT JOIN homework_feedback hf ON hf.submission_id = hs.id
             WHERE h.class_id = ?
             ORDER BY h.due_at DESC',
            [$sid, $cid]
        ) : [];

        return $this->view('panels/student/homework', [
            'title' => Translator::get('menu.homework'),
            'items' => $items,
        ]);
    }

    public function submitHomework(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);
        $hwId = (int) ($params['id'] ?? 0);

        $hw = Database::fetch('SELECT * FROM homework WHERE id = ?', [$hwId]);
        if (!$hw || (int) $hw['class_id'] !== (int) ($student['class_id'] ?? 0)) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $attachmentPath = null;
        $file = $request->file('submission_file');
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $attachmentPath = Uploader::upload($file, 'submissions', Uploader::ALLOWED_DOCS_AND_IMAGES);
            } catch (\Throwable $e) {
                $this->flash('error', $e->getMessage());
                return $this->redirect('/student/homework');
            }
        }

        $text = trim((string) $request->post('submission_text', ''));
        if ($text === '' && $attachmentPath === null) {
            $this->flash('error', Translator::get('validation.required', ['field' => Translator::get('student.your_notes')]));
            return $this->redirect('/student/homework');
        }

        HomeworkService::submitWork($hwId, $sid, $text ?: null, $attachmentPath);
        $this->flash('success', Translator::get('student.hw_submitted'));
        return $this->redirect('/student/homework');
    }

    public function studyPlans(Request $request): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);

        $plans = Database::fetchAll(
            'SELECT sp.*, u.full_name AS counselor_name
             FROM study_plans sp
             JOIN counselors c ON c.id = sp.counselor_id
             JOIN users u ON u.id = c.user_id
             WHERE sp.student_id = ?
             ORDER BY sp.id DESC',
            [$sid]
        );
        foreach ($plans as &$p) {
            $p['tasks'] = Database::fetchAll(
                'SELECT t.*, s.name AS subject_name FROM study_plan_tasks t LEFT JOIN subjects s ON s.id = t.subject_id WHERE t.study_plan_id = ? ORDER BY t.id ASC',
                [(int) $p['id']]
            );
            $p['progress'] = CounselingService::planProgress((int) $p['id']);
        }
        unset($p);

        return $this->view('panels/student/study_plans', [
            'title' => Translator::get('menu.study_plans'),
            'plans' => $plans,
        ]);
    }

    public function requestStudyPlan(Request $request): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);
        $note = trim((string) $request->post('note', ''));
        if ($sid > 0) {
            $now = date('Y-m-d H:i:s');
            Database::insert(
                'INSERT INTO study_plan_requests (student_id, note, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [$sid, $note ?: null, 'pending', $now, $now]
            );
            $this->flash('success', Translator::get('student.request_sent'));
        }
        return $this->redirect('/student/study-plans');
    }

    public function toggleStudyTask(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);
        $taskId = (int) ($params['id'] ?? 0);
        $completed = (int) $request->input('is_completed', 1) === 1;

        $stats = CounselingService::toggleTaskForStudent($taskId, $sid, $completed);
        if ($stats === null) {
            return $request->isAjax()
                ? $this->json(['error' => Translator::get('errors.forbidden')], 403)
                : Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        if ($request->isAjax()) {
            return $this->json(['ok' => true, 'stats' => $stats]);
        }
        return $this->redirect('/student/study-plans');
    }

    public function classrooms(Request $request): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $cid = (int) ($student['class_id'] ?? 0);

        $sessions = $cid > 0 ? Database::fetchAll(
            'SELECT ocs.*, s.name AS subject_name, tu.full_name AS teacher_name
             FROM online_class_sessions ocs
             JOIN subjects s ON s.id = ocs.subject_id
             JOIN teachers t ON t.id = ocs.teacher_id
             JOIN users tu ON tu.id = t.user_id
             WHERE ocs.class_id = ?
             ORDER BY ocs.scheduled_start DESC',
            [$cid]
        ) : [];

        return $this->view('panels/student/classrooms', [
            'title' => Translator::get('menu.classrooms'),
            'sessions' => $sessions,
        ]);
    }

    public function joinClassroom(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);
        $sessionId = (int) ($params['id'] ?? 0);

        $session = Database::fetch('SELECT * FROM online_class_sessions WHERE id = ?', [$sessionId]);
        if (!$session || (int) $session['class_id'] !== (int) ($student['class_id'] ?? 0)) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $exists = Database::fetch('SELECT id FROM online_class_attendance WHERE session_id = ? AND student_id = ?', [$sessionId, $sid]);
        if (!$exists) {
            Database::execute(
                'INSERT INTO online_class_attendance (session_id, student_id, joined_at, duration_minutes, status) VALUES (?, ?, ?, 30, ?)',
                [$sessionId, $sid, date('Y-m-d H:i:s'), 'joined']
            );
        }

        $url = SkyroomService::resolveJoinUrl($session, $user, false);
        if (!$url) {
            $this->flash('warning', Translator::get('skyroom.not_configured'));
            return $this->redirect('/student/classrooms');
        }
        return Response::redirect($url);
    }

    public function attendance(Request $request): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $summary = AttendanceService::studentAttendanceSummary((int) ($student['id'] ?? 0));

        return $this->view('panels/student/attendance', [
            'title' => Translator::get('menu.attendance'),
            'summary' => $summary,
        ]);
    }

    public function reportCards(Request $request): Response
    {
        $user = $this->authUser();
        $student = OwnershipGuard::studentProfile((int) $user['id']);
        $sid = (int) ($student['id'] ?? 0);

        $cards = Database::fetchAll(
            'SELECT rc.*, ay.name AS year_name FROM report_cards rc JOIN academic_years ay ON ay.id = rc.academic_year_id WHERE rc.student_id = ? AND rc.is_published = 1 ORDER BY rc.id DESC',
            [$sid]
        );

        return $this->view('panels/student/report_cards', [
            'title' => Translator::get('menu.report_cards'),
            'cards' => $cards,
        ]);
    }
}
