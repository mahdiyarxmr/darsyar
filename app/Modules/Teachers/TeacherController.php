<?php

declare(strict_types=1);

namespace App\Modules\Teachers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Translator;
use App\Core\Uploader;
use App\Core\Validator;
use App\Modules\Attendance\AttendanceService;
use App\Modules\Authorization\OwnershipGuard;
use App\Modules\Classrooms\SkyroomService;
use App\Modules\Exams\ExamService;
use App\Modules\Homework\HomeworkService;
use App\Modules\Reports\PerformanceRatingService;

final class TeacherController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);

        $assignments = Database::fetchAll(
            'SELECT ta.*, c.name AS class_name, g.name AS grade_name, s.name AS subject_name, s.code AS subject_code
             FROM teacher_assignments ta
             JOIN classes c ON c.id = ta.class_id
             JOIN grades g ON g.id = c.grade_id
             JOIN subjects s ON s.id = ta.subject_id
             WHERE ta.teacher_id = ?
             ORDER BY c.name ASC, s.name ASC',
            [$tid]
        );

        $classIds = array_values(array_unique(array_map(static fn ($a) => (int) $a['class_id'], $assignments)));
        $rankings = [];
        foreach ($classIds as $cid) {
            foreach (PerformanceRatingService::allStudentRankings($cid) as $row) {
                $rankings[] = $row;
            }
        }
        usort($rankings, static fn ($a, $b) => $b['total_score'] <=> $a['total_score']);

        $stats = [
            'assigned_classes' => count($classIds),
            'exams_count' => (int) Database::fetchColumn('SELECT COUNT(*) FROM exams WHERE teacher_id = ?', [$tid]),
            'homework_count' => (int) Database::fetchColumn('SELECT COUNT(*) FROM homework WHERE teacher_id = ?', [$tid]),
            'pending_grading' => (int) Database::fetchColumn(
                "SELECT COUNT(*) FROM exam_attempts ea JOIN exams e ON e.id = ea.exam_id WHERE e.teacher_id = ? AND ea.status = 'submitted'",
                [$tid]
            ),
        ];

        return $this->view('panels/teacher/dashboard', [
            'title' => Translator::get('panel.teacher'),
            'assignments' => $assignments,
            'stats' => $stats,
            'rankings' => array_slice($rankings, 0, 10),
        ]);
    }

    public function classes(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);

        $assignments = Database::fetchAll(
            'SELECT ta.*, c.name AS class_name, g.name AS grade_name, s.name AS subject_name, s.code AS subject_code
             FROM teacher_assignments ta
             JOIN classes c ON c.id = ta.class_id
             JOIN grades g ON g.id = c.grade_id
             JOIN subjects s ON s.id = ta.subject_id
             WHERE ta.teacher_id = ?
             ORDER BY c.name ASC, s.name ASC',
            [$tid]
        );

        $classIds = array_values(array_unique(array_map(static fn ($a) => (int) $a['class_id'], $assignments)));
        $selectedClassId = (int) $request->query('class_id', $classIds[0] ?? 0);
        $students = [];
        if ($selectedClassId > 0 && OwnershipGuard::teacherAssignedToClass($tid, $selectedClassId)) {
            $students = PerformanceRatingService::allStudentRankings($selectedClassId);
        }

        return $this->view('panels/teacher/classes', [
            'title' => Translator::get('menu.my_classes'),
            'assignments' => $assignments,
            'selectedClassId' => $selectedClassId,
            'students' => $students,
        ]);
    }

    public function questions(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);

        $subjects = $this->assignedSubjects($tid);
        $grades = Database::fetchAll('SELECT * FROM grades ORDER BY level_order ASC');
        $questions = Database::fetchAll(
            'SELECT qb.*, s.name AS subject_name FROM question_bank qb JOIN subjects s ON s.id = qb.subject_id WHERE qb.teacher_id = ? ORDER BY qb.id DESC',
            [$tid]
        );

        return $this->view('panels/teacher/questions', [
            'title' => Translator::get('menu.questions'),
            'subjects' => $subjects,
            'grades' => $grades,
            'questions' => $questions,
        ]);
    }

    public function createQuestion(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $data = $request->allPost();

        $v = new Validator();
        if (!$v->validate($data, [
            'subject_id' => 'required|integer',
            'type' => 'required|in:mcq,descriptive',
            'prompt' => 'required|min:3',
            'points' => 'required|numeric|min_val:0.5',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/teacher/questions');
        }

        $options = [];
        if ($data['type'] === 'mcq') {
            foreach (['opt_0', 'opt_1', 'opt_2', 'opt_3'] as $k) {
                $txt = trim((string) ($data[$k] ?? ''));
                if ($txt !== '') {
                    $options[] = $txt;
                }
            }
        }
        $now = date('Y-m-d H:i:s');
        Database::insert(
            'INSERT INTO question_bank (teacher_id, subject_id, grade_id, type, prompt, options_json, correct_answer, points, difficulty, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tid,
                (int) $data['subject_id'],
                ((int) ($data['grade_id'] ?? 0)) ?: null,
                $data['type'],
                trim((string) $data['prompt']),
                $options !== [] ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
                trim((string) ($data['correct_index'] ?? '0')),
                (float) $data['points'],
                in_array($data['difficulty'] ?? 'medium', ['easy', 'medium', 'hard'], true) ? $data['difficulty'] : 'medium',
                $now,
                $now,
            ]
        );

        $this->flash('success', Translator::get('teacher.question_saved'));
        return $this->redirect('/teacher/questions');
    }

    public function exams(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);

        $assignments = $this->assignedPairs($tid);
        $exams = Database::fetchAll(
            'SELECT e.*, c.name AS class_name, s.name AS subject_name,
                    (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = e.id) AS q_count,
                    (SELECT COUNT(*) FROM exam_attempts ea WHERE ea.exam_id = e.id) AS attempt_count
             FROM exams e
             JOIN classes c ON c.id = e.class_id
             JOIN subjects s ON s.id = e.subject_id
             WHERE e.teacher_id = ?
             ORDER BY e.id DESC',
            [$tid]
        );

        return $this->view('panels/teacher/exams', [
            'title' => Translator::get('menu.exams'),
            'assignments' => $assignments,
            'exams' => $exams,
        ]);
    }

    public function createExam(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $data = $request->allPost();

        $v = new Validator();
        if (!$v->validate($data, [
            'title' => 'required|min:2|max:180',
            'exam_mode' => 'required|in:online,offline',
            'assignment_pair' => 'required',
            'starts_at' => 'required',
            'ends_at' => 'required',
            'max_score' => 'required|numeric|min_val:1',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/teacher/exams');
        }

        [$classId, $subjectId] = array_map('intval', explode(':', (string) $data['assignment_pair']) + [0, 0]);
        if (!OwnershipGuard::teacherAssignedToClass($tid, $classId, $subjectId)) {
            $this->flash('error', Translator::get('errors.forbidden'));
            return $this->redirect('/teacher/exams');
        }

        $startsAt = str_replace('T', ' ', (string) $data['starts_at']);
        if (strlen($startsAt) === 16) {
            $startsAt .= ':00';
        }
        $endsAt = str_replace('T', ' ', (string) $data['ends_at']);
        if (strlen($endsAt) === 16) {
            $endsAt .= ':00';
        }
        if (strtotime($endsAt) <= strtotime($startsAt)) {
            $this->flash('error', Translator::get('teacher.end_after_start'));
            return $this->redirect('/teacher/exams');
        }

        $yearId = (int) Database::fetchColumn('SELECT academic_year_id FROM classes WHERE id = ?', [$classId]);
        $now = date('Y-m-d H:i:s');

        $practicePath = null;
        $file = $request->file('practice_sheet');
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $practicePath = Uploader::upload($file, 'exams', Uploader::ALLOWED_DOCS_AND_IMAGES);
            } catch (\Throwable $e) {
                $this->flash('error', $e->getMessage());
                return $this->redirect('/teacher/exams');
            }
        }

        $examId = Database::insert(
            'INSERT INTO exams (title, description, exam_mode, subject_id, class_id, teacher_id, academic_year_id, starts_at, ends_at, duration_minutes, max_score, negative_marking_ratio, randomize_questions, randomize_options, max_attempts, late_finish_policy, practice_sheet_path, results_published, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)',
            [
                trim((string) $data['title']),
                trim((string) ($data['description'] ?? '')),
                $data['exam_mode'],
                $subjectId,
                $classId,
                $tid,
                $yearId,
                $startsAt,
                $endsAt,
                max(5, (int) ($data['duration_minutes'] ?? 45)),
                (float) $data['max_score'],
                max(0.0, (float) ($data['negative_marking_ratio'] ?? 0)),
                isset($data['randomize_questions']) ? 1 : 0,
                isset($data['randomize_options']) ? 1 : 0,
                max(1, (int) ($data['max_attempts'] ?? 1)),
                in_array($data['late_finish_policy'] ?? 'hard_cutoff', ['hard_cutoff', 'allow_finish_duration'], true) ? $data['late_finish_policy'] : 'hard_cutoff',
                $practicePath,
                'published',
                $now,
                $now,
            ]
        );

        $this->flash('success', Translator::get('teacher.exam_created'));
        return $this->redirect('/teacher/exams/' . $examId);
    }

    public function examDetail(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $examId = (int) ($params['id'] ?? 0);

        $exam = Database::fetch(
            'SELECT e.*, c.name AS class_name, s.name AS subject_name
             FROM exams e
             JOIN classes c ON c.id = e.class_id
             JOIN subjects s ON s.id = e.subject_id
             WHERE e.id = ? AND e.teacher_id = ?',
            [$examId, $tid]
        );
        if (!$exam) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $questions = Database::fetchAll('SELECT * FROM exam_questions WHERE exam_id = ? ORDER BY sort_order ASC, id ASC', [$examId]);
        foreach ($questions as &$q) {
            $q['options'] = Database::fetchAll('SELECT * FROM exam_options WHERE question_id = ? ORDER BY sort_order ASC', [(int) $q['id']]);
        }
        unset($q);

        $bankQuestions = Database::fetchAll(
            'SELECT * FROM question_bank WHERE teacher_id = ? AND subject_id = ? ORDER BY id DESC',
            [$tid, (int) $exam['subject_id']]
        );

        $attempts = Database::fetchAll(
            'SELECT ea.*, u.full_name AS student_name, s.student_code
             FROM exam_attempts ea
             JOIN students s ON s.id = ea.student_id
             JOIN users u ON u.id = s.user_id
             WHERE ea.exam_id = ?
             ORDER BY ea.id DESC',
            [$examId]
        );

        $classStudents = Database::fetchAll(
            'SELECT s.id, s.student_code, u.full_name,
                    (SELECT er.score FROM exam_results er WHERE er.exam_id = ? AND er.student_id = s.id LIMIT 1) AS recorded_score,
                    (SELECT er.remarks FROM exam_results er WHERE er.exam_id = ? AND er.student_id = s.id LIMIT 1) AS recorded_remarks
             FROM students s
             JOIN users u ON u.id = s.user_id
             WHERE s.class_id = ?
             ORDER BY u.full_name ASC',
            [$examId, $examId, (int) $exam['class_id']]
        );

        return $this->view('panels/teacher/exam_detail', [
            'title' => $exam['title'],
            'exam' => $exam,
            'questions' => $questions,
            'bankQuestions' => $bankQuestions,
            'attempts' => $attempts,
            'classStudents' => $classStudents,
        ]);
    }

    public function addExamQuestion(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $examId = (int) ($params['id'] ?? 0);

        $exam = Database::fetch('SELECT * FROM exams WHERE id = ? AND teacher_id = ?', [$examId, $tid]);
        if (!$exam) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $data = $request->allPost();
        $bankId = (int) ($data['bank_question_id'] ?? 0);
        $nextOrder = (int) Database::fetchColumn('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM exam_questions WHERE exam_id = ?', [$examId]);

        if ($bankId > 0) {
            $bq = Database::fetch('SELECT * FROM question_bank WHERE id = ? AND teacher_id = ?', [$bankId, $tid]);
            if ($bq) {
                $qid = Database::insert(
                    'INSERT INTO exam_questions (exam_id, question_bank_id, type, prompt, points, sort_order) VALUES (?, ?, ?, ?, ?, ?)',
                    [$examId, $bankId, $bq['type'], $bq['prompt'], (float) $bq['points'], $nextOrder]
                );
                if ($bq['type'] === 'mcq') {
                    $opts = json_decode((string) $bq['options_json'], true) ?: [];
                    $correctIdx = (int) ($bq['correct_answer'] ?? 0);
                    foreach ($opts as $i => $optTxt) {
                        Database::insert(
                            'INSERT INTO exam_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, ?, ?)',
                            [$qid, $optTxt, $i === $correctIdx ? 1 : 0, $i + 1]
                        );
                    }
                }
                $this->flash('success', Translator::get('teacher.question_saved'));
            }
            return $this->redirect('/teacher/exams/' . $examId);
        }

        $v = new Validator();
        if (!$v->validate($data, ['type' => 'required|in:mcq,descriptive', 'prompt' => 'required|min:2', 'points' => 'required|numeric|min_val:0.5'])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/teacher/exams/' . $examId);
        }

        $qid = Database::insert(
            'INSERT INTO exam_questions (exam_id, type, prompt, points, sort_order) VALUES (?, ?, ?, ?, ?)',
            [$examId, $data['type'], trim((string) $data['prompt']), (float) $data['points'], $nextOrder]
        );
        if ($data['type'] === 'mcq') {
            $correctIdx = (int) ($data['correct_index'] ?? 0);
            foreach (['opt_0', 'opt_1', 'opt_2', 'opt_3'] as $idx => $key) {
                $txt = trim((string) ($data[$key] ?? ''));
                if ($txt !== '') {
                    Database::insert(
                        'INSERT INTO exam_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, ?, ?)',
                        [$qid, $txt, $idx === $correctIdx ? 1 : 0, $idx + 1]
                    );
                }
            }
        }

        $this->flash('success', Translator::get('teacher.question_saved'));
        return $this->redirect('/teacher/exams/' . $examId);
    }

    public function gradeAttemptView(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $attemptId = (int) ($params['id'] ?? 0);

        $attempt = Database::fetch(
            'SELECT ea.*, e.title AS exam_title, e.max_score, e.teacher_id, u.full_name AS student_name
             FROM exam_attempts ea
             JOIN exams e ON e.id = ea.exam_id
             JOIN students s ON s.id = ea.student_id
             JOIN users u ON u.id = s.user_id
             WHERE ea.id = ? AND e.teacher_id = ?',
            [$attemptId, $tid]
        );
        if (!$attempt) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $answers = Database::fetchAll(
            'SELECT ea.*, eq.type, eq.prompt, eq.points, eo.option_text AS selected_option_text
             FROM exam_answers ea
             JOIN exam_questions eq ON eq.id = ea.question_id
             LEFT JOIN exam_options eo ON eo.id = ea.selected_option_id
             WHERE ea.attempt_id = ?
             ORDER BY eq.sort_order ASC',
            [$attemptId]
        );

        return $this->view('panels/teacher/grade_attempt', [
            'title' => Translator::get('teacher.grade_attempt_title') . ': ' . $attempt['student_name'],
            'attempt' => $attempt,
            'answers' => $answers,
        ]);
    }

    public function submitAttemptGrade(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $attemptId = (int) ($params['id'] ?? 0);

        $ownerCheck = Database::fetch(
            'SELECT ea.exam_id FROM exam_attempts ea JOIN exams e ON e.id = ea.exam_id WHERE ea.id = ? AND e.teacher_id = ?',
            [$attemptId, $tid]
        );
        if (!$ownerCheck) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $scores = $request->post('scores', []);
        $comments = $request->post('comments', []);
        ExamService::gradeDescriptiveAttempt($attemptId, is_array($scores) ? $scores : [], is_array($comments) ? $comments : [], (int) $user['id']);

        $this->flash('success', Translator::get('teacher.grade_saved'));
        return $this->redirect('/teacher/exams/' . $ownerCheck['exam_id']);
    }

    public function saveOfflineScores(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $examId = (int) ($params['id'] ?? 0);

        $exam = Database::fetch('SELECT * FROM exams WHERE id = ? AND teacher_id = ?', [$examId, $tid]);
        if (!$exam) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $scores = $request->post('scores', []);
        $remarks = $request->post('remarks', []);
        $publish = $request->post('publish', '1') === '1';

        if (is_array($scores)) {
            foreach ($scores as $sid => $val) {
                if ($val === '' || !is_numeric($val)) {
                    continue;
                }
                ExamService::saveOfflineScore(
                    $examId,
                    (int) $sid,
                    (float) $val,
                    (float) $exam['max_score'],
                    trim((string) ($remarks[$sid] ?? '')) ?: null,
                    (int) $user['id'],
                    $publish
                );
            }
        }
        if ($publish) {
            Database::execute('UPDATE exams SET results_published = 1 WHERE id = ?', [$examId]);
        }

        $this->flash('success', Translator::get('teacher.offline_saved'));
        return $this->redirect('/teacher/exams/' . $examId);
    }

    public function importOfflineScoresCsv(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $examId = (int) ($params['id'] ?? 0);

        $exam = Database::fetch('SELECT * FROM exams WHERE id = ? AND teacher_id = ?', [$examId, $tid]);
        if (!$exam) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $file = $request->file('scores_csv');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->flash('error', Translator::get('errors.upload_failed'));
            return $this->redirect('/teacher/exams/' . $examId);
        }

        $handle = fopen((string) $file['tmp_name'], 'r');
        $imported = 0;
        if ($handle) {
            fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 2) {
                    continue;
                }
                $code = trim((string) $row[0]);
                $scoreVal = trim((string) $row[1]);
                $rem = trim((string) ($row[2] ?? '')) ?: null;
                if ($code === '' || !is_numeric($scoreVal)) {
                    continue;
                }
                $studentId = (int) Database::fetchColumn('SELECT id FROM students WHERE student_code = ? AND class_id = ?', [$code, (int) $exam['class_id']]);
                if ($studentId > 0) {
                    ExamService::saveOfflineScore($examId, $studentId, (float) $scoreVal, (float) $exam['max_score'], $rem, (int) $user['id'], true);
                    $imported++;
                }
            }
            fclose($handle);
        }

        Database::execute('UPDATE exams SET results_published = 1 WHERE id = ?', [$examId]);
        $this->flash('success', Translator::get('teacher.offline_imported', ['count' => $imported]));
        return $this->redirect('/teacher/exams/' . $examId);
    }

    public function dailyTests(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);

        $tests = Database::fetchAll(
            'SELECT dt.*, s.name AS subject_name, c.name AS class_name,
                    (SELECT COUNT(*) FROM daily_test_questions q WHERE q.daily_test_id = dt.id) AS q_count
             FROM daily_tests dt
             JOIN subjects s ON s.id = dt.subject_id
             LEFT JOIN classes c ON c.id = dt.class_id
             WHERE dt.teacher_id = ?
             ORDER BY dt.id DESC',
            [$tid]
        );

        foreach ($tests as &$dt) {
            $dt['questions'] = Database::fetchAll('SELECT * FROM daily_test_questions WHERE daily_test_id = ? ORDER BY id ASC', [(int) $dt['id']]);
        }
        unset($dt);

        return $this->view('panels/teacher/daily_tests', [
            'title' => Translator::get('menu.daily_tests'),
            'tests' => $tests,
            'assignments' => $this->assignedPairs($tid),
            'subjects' => $this->assignedSubjects($tid),
        ]);
    }

    public function createDailyTest(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $data = $request->allPost();

        $v = new Validator();
        if (!$v->validate($data, ['title' => 'required|min:2|max:180', 'subject_id' => 'required|integer'])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/teacher/daily-tests');
        }

        $now = date('Y-m-d H:i:s');
        $dtId = Database::insert(
            'INSERT INTO daily_tests (title, subject_id, class_id, teacher_id, description, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?)',
            [$data['title'], (int) $data['subject_id'], ((int) ($data['class_id'] ?? 0)) ?: null, $tid, trim((string) ($data['description'] ?? '')), $now, $now]
        );

        if (!empty($data['prompt'])) {
            $opts = array_values(array_filter([
                trim((string) ($data['opt_0'] ?? '')),
                trim((string) ($data['opt_1'] ?? '')),
                trim((string) ($data['opt_2'] ?? '')),
                trim((string) ($data['opt_3'] ?? '')),
            ]));
            if (count($opts) >= 2) {
                Database::insert(
                    'INSERT INTO daily_test_questions (daily_test_id, prompt, options_json, correct_index, explanation) VALUES (?, ?, ?, ?, ?)',
                    [$dtId, trim((string) $data['prompt']), json_encode($opts, JSON_UNESCAPED_UNICODE), (int) ($data['correct_index'] ?? 0), trim((string) ($data['explanation'] ?? ''))]
                );
            }
        }

        $this->flash('success', Translator::get('teacher.daily_test_created'));
        return $this->redirect('/teacher/daily-tests');
    }

    public function addDailyTestQuestion(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $dtId = (int) ($params['id'] ?? 0);

        $dt = Database::fetch('SELECT * FROM daily_tests WHERE id = ? AND teacher_id = ?', [$dtId, $tid]);
        if (!$dt) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $data = $request->allPost();
        $opts = array_values(array_filter([
            trim((string) ($data['opt_0'] ?? '')),
            trim((string) ($data['opt_1'] ?? '')),
            trim((string) ($data['opt_2'] ?? '')),
            trim((string) ($data['opt_3'] ?? '')),
        ]));
        if (!empty($data['prompt']) && count($opts) >= 2) {
            Database::insert(
                'INSERT INTO daily_test_questions (daily_test_id, prompt, options_json, correct_index, explanation) VALUES (?, ?, ?, ?, ?)',
                [$dtId, trim((string) $data['prompt']), json_encode($opts, JSON_UNESCAPED_UNICODE), (int) ($data['correct_index'] ?? 0), trim((string) ($data['explanation'] ?? ''))]
            );
            $this->flash('success', Translator::get('teacher.daily_q_added'));
        } else {
            $this->flash('error', Translator::get('validation.required', ['field' => 'prompt']));
        }

        return $this->redirect('/teacher/daily-tests');
    }

    public function homework(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);

        $assignments = $this->assignedPairs($tid);
        $homeworks = Database::fetchAll(
            'SELECT h.*, c.name AS class_name, s.name AS subject_name,
                    (SELECT COUNT(*) FROM homework_submissions hs WHERE hs.homework_id = h.id) AS sub_count
             FROM homework h
             JOIN classes c ON c.id = h.class_id
             JOIN subjects s ON s.id = h.subject_id
             WHERE h.teacher_id = ?
             ORDER BY h.id DESC',
            [$tid]
        );

        $submissions = Database::fetchAll(
            'SELECT hs.*, h.title AS hw_title, h.max_score, u.full_name AS student_name, s.student_code,
                    hf.score AS graded_score, hf.feedback_text, hf.decision
             FROM homework_submissions hs
             JOIN homework h ON h.id = hs.homework_id
             JOIN students s ON s.id = hs.student_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN homework_feedback hf ON hf.submission_id = hs.id
             WHERE h.teacher_id = ?
             ORDER BY hs.submitted_at DESC',
            [$tid]
        );

        return $this->view('panels/teacher/homework', [
            'title' => Translator::get('menu.homework'),
            'assignments' => $assignments,
            'homeworks' => $homeworks,
            'submissions' => $submissions,
        ]);
    }

    public function createHomework(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $data = $request->allPost();

        $v = new Validator();
        if (!$v->validate($data, [
            'assignment_pair' => 'required',
            'title' => 'required|min:2|max:180',
            'description' => 'required|min:2',
            'due_at' => 'required',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/teacher/homework');
        }

        [$classId, $subjectId] = array_map('intval', explode(':', (string) $data['assignment_pair']) + [0, 0]);
        if (!OwnershipGuard::teacherAssignedToClass($tid, $classId, $subjectId)) {
            $this->flash('error', Translator::get('errors.forbidden'));
            return $this->redirect('/teacher/homework');
        }

        $attachmentPath = null;
        $file = $request->file('attachment');
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $attachmentPath = Uploader::upload($file, 'homework', Uploader::ALLOWED_DOCS_AND_IMAGES);
            } catch (\Throwable $e) {
                $this->flash('error', $e->getMessage());
                return $this->redirect('/teacher/homework');
            }
        }

        $dueAt = str_replace('T', ' ', (string) $data['due_at']);
        if (strlen($dueAt) === 16) {
            $dueAt .= ':00';
        }

        HomeworkService::createAssignment(
            $tid,
            $classId,
            $subjectId,
            trim((string) $data['title']),
            trim((string) $data['description']),
            $attachmentPath,
            trim((string) ($data['requirements'] ?? '')) ?: null,
            $dueAt,
            max(1.0, (float) ($data['max_score'] ?? 20))
        );

        $this->flash('success', Translator::get('teacher.hw_published'));
        return $this->redirect('/teacher/homework');
    }

    public function gradeHomework(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $subId = (int) ($params['id'] ?? 0);

        $owns = Database::fetch(
            'SELECT hs.id FROM homework_submissions hs JOIN homework h ON h.id = hs.homework_id WHERE hs.id = ? AND h.teacher_id = ?',
            [$subId, $tid]
        );
        if (!$owns) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $score = (float) $request->post('score', 0);
        $feedback = trim((string) $request->post('feedback_text', ''));
        $decision = (string) $request->post('decision', 'accepted');

        HomeworkService::gradeSubmission($subId, $tid, $score, $feedback, $decision);
        $this->flash('success', Translator::get('teacher.hw_graded'));
        return $this->redirect('/teacher/homework');
    }

    public function classrooms(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);

        $sessions = Database::fetchAll(
            'SELECT ocs.*, c.name AS class_name, s.name AS subject_name,
                    (SELECT COUNT(*) FROM online_class_attendance oca WHERE oca.session_id = ocs.id) AS attendee_count
             FROM online_class_sessions ocs
             JOIN classes c ON c.id = ocs.class_id
             JOIN subjects s ON s.id = ocs.subject_id
             WHERE ocs.teacher_id = ?
             ORDER BY ocs.scheduled_start DESC',
            [$tid]
        );

        return $this->view('panels/teacher/classrooms', [
            'title' => Translator::get('menu.classrooms'),
            'sessions' => $sessions,
            'assignments' => $this->assignedPairs($tid),
            'skyroomConfigured' => SkyroomService::isConfigured(),
        ]);
    }

    public function createClassroom(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $data = $request->allPost();

        $v = new Validator();
        if (!$v->validate($data, [
            'assignment_pair' => 'required',
            'title' => 'required|min:2|max:180',
            'scheduled_start' => 'required',
            'scheduled_end' => 'required',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/teacher/classrooms');
        }

        [$classId, $subjectId] = array_map('intval', explode(':', (string) $data['assignment_pair']) + [0, 0]);
        if (!OwnershipGuard::teacherAssignedToClass($tid, $classId, $subjectId)) {
            $this->flash('error', Translator::get('errors.forbidden'));
            return $this->redirect('/teacher/classrooms');
        }

        $start = str_replace('T', ' ', (string) $data['scheduled_start']);
        if (strlen($start) === 16) $start .= ':00';
        $end = str_replace('T', ' ', (string) $data['scheduled_end']);
        if (strlen($end) === 16) $end .= ':00';

        $res = SkyroomService::createSession(
            $tid,
            $classId,
            $subjectId,
            trim((string) $data['title']),
            trim((string) ($data['description'] ?? '')),
            $start,
            $end,
            trim((string) ($data['fallback_url'] ?? '')) ?: null
        );

        if ($res['warning']) {
            $this->flash('warning', $res['warning']);
        } else {
            $this->flash('success', Translator::get('skyroom.session_scheduled'));
        }
        return $this->redirect('/teacher/classrooms');
    }

    public function updateClassroomStatus(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $sessionId = (int) ($params['id'] ?? 0);
        $status = (string) $request->post('status', 'scheduled');
        if (in_array($status, ['scheduled', 'live', 'completed', 'cancelled'], true)) {
            Database::execute(
                'UPDATE online_class_sessions SET status = ?, updated_at = ? WHERE id = ? AND teacher_id = ?',
                [$status, date('Y-m-d H:i:s'), $sessionId, $tid]
            );
            $this->flash('success', Translator::get('admin.status_updated'));
        }
        return $this->redirect('/teacher/classrooms');
    }

    public function joinClassroom(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $sessionId = (int) ($params['id'] ?? 0);

        $session = Database::fetch('SELECT * FROM online_class_sessions WHERE id = ? AND teacher_id = ?', [$sessionId, $tid]);
        if (!$session) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $url = SkyroomService::resolveJoinUrl($session, $user, true);
        if (!$url) {
            $this->flash('error', Translator::get('skyroom.not_configured'));
            return $this->redirect('/teacher/classrooms');
        }
        return Response::redirect($url);
    }

    public function attendance(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);
        $assignments = $this->assignedPairs($tid);

        $pair = (string) $request->query('pair', '');
        if ($pair === '' && !empty($assignments)) {
            $pair = $assignments[0]['class_id'] . ':' . $assignments[0]['subject_id'];
        }
        [$classId, $subjectId] = $pair !== '' ? array_map('intval', explode(':', $pair) + [0, 0]) : [0, 0];
        $date = (string) $request->query('date', date('Y-m-d'));

        $students = ($classId > 0 && OwnershipGuard::teacherAssignedToClass($tid, $classId, $subjectId))
            ? Database::fetchAll(
                "SELECT s.id, s.student_code, u.full_name,
                        (SELECT a.status FROM attendance a WHERE a.student_id = s.id AND a.class_id = ? AND a.subject_id = ? AND a.attendance_date = ? AND a.scope = 'classroom' LIMIT 1) AS today_status
                 FROM students s
                 JOIN users u ON u.id = s.user_id
                 WHERE s.class_id = ?
                 ORDER BY u.full_name ASC",
                [$classId, $subjectId, $date, $classId]
            )
            : [];

        $history = ($classId > 0 && OwnershipGuard::teacherAssignedToClass($tid, $classId, $subjectId))
            ? Database::fetchAll(
                "SELECT a.attendance_date, a.status, COUNT(*) AS cnt
                 FROM attendance a
                 WHERE a.class_id = ? AND a.subject_id = ? AND a.scope = 'classroom'
                 GROUP BY a.attendance_date, a.status
                 ORDER BY a.attendance_date DESC LIMIT 20",
                [$classId, $subjectId]
            )
            : [];

        return $this->view('panels/teacher/attendance', [
            'title' => Translator::get('menu.attendance'),
            'assignments' => $assignments,
            'selectedPair' => $pair,
            'selectedDate' => $date,
            'students' => $students,
            'history' => $history,
        ]);
    }

    public function saveAttendance(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);

        $pair = (string) $request->post('pair', '');
        [$classId, $subjectId] = array_map('intval', explode(':', $pair) + [0, 0]);
        if (!OwnershipGuard::teacherAssignedToClass($tid, $classId, $subjectId)) {
            return Response::html(\App\Core\View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $date = (string) $request->post('attendance_date', date('Y-m-d'));
        $statuses = $request->post('status', []);
        $notes = $request->post('note', []);

        $count = AttendanceService::recordBatch($classId, $subjectId, $date, 'classroom', is_array($statuses) ? $statuses : [], is_array($notes) ? $notes : [], (int) $user['id']);
        $this->flash('success', Translator::get('teacher.class_attendance_saved', ['count' => $count]));
        return $this->redirect('/teacher/attendance?pair=' . urlencode($pair) . '&date=' . urlencode($date));
    }

    private function assignedPairs(int $teacherId): array
    {
        return Database::fetchAll(
            'SELECT ta.class_id, ta.subject_id, c.name AS class_name, s.name AS subject_name
             FROM teacher_assignments ta
             JOIN classes c ON c.id = ta.class_id
             JOIN subjects s ON s.id = ta.subject_id
             WHERE ta.teacher_id = ?
             ORDER BY c.name ASC, s.name ASC',
            [$teacherId]
        );
    }

    private function assignedSubjects(int $teacherId): array
    {
        return Database::fetchAll(
            'SELECT DISTINCT s.id, s.name, s.code
             FROM teacher_assignments ta
             JOIN subjects s ON s.id = ta.subject_id
             WHERE ta.teacher_id = ?
             ORDER BY s.name ASC',
            [$teacherId]
        );
    }
}
