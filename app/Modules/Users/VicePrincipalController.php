<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Translator;
use App\Core\Uploader;
use App\Core\Validator;
use App\Modules\Attendance\AttendanceService;
use App\Modules\AuditLogs\AuditLogService;
use App\Modules\Auth\AuthService;
use App\Modules\Parents\BulkParentGeneratorService;
use App\Modules\Reports\ReportCardService;

final class VicePrincipalController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $stats = [
            'classes' => (int) Database::fetchColumn('SELECT COUNT(*) FROM classes'),
            'teachers' => (int) Database::fetchColumn('SELECT COUNT(*) FROM teachers'),
            'students' => (int) Database::fetchColumn('SELECT COUNT(*) FROM students'),
            'parents' => (int) Database::fetchColumn('SELECT COUNT(*) FROM parents'),
            'unlinked_students' => (int) Database::fetchColumn(
                'SELECT COUNT(*) FROM students s WHERE NOT EXISTS (SELECT 1 FROM parent_student ps WHERE ps.student_id = s.id)'
            ),
            'report_cards' => (int) Database::fetchColumn('SELECT COUNT(*) FROM report_cards'),
        ];

        $classEnrollment = Database::fetchAll(
            'SELECT c.name AS class_name, COUNT(s.id) AS student_count
             FROM classes c
             LEFT JOIN students s ON s.class_id = c.id
             GROUP BY c.id, c.name
             ORDER BY c.name ASC'
        );

        return $this->view('panels/vice_principal/dashboard', [
            'title' => Translator::get('panel.vice_principal'),
            'stats' => $stats,
            'classEnrollment' => $classEnrollment,
        ]);
    }

    public function academic(Request $request): Response
    {
        return $this->view('panels/vice_principal/academic', [
            'title' => Translator::get('menu.academic'),
            'years' => Database::fetchAll('SELECT * FROM academic_years ORDER BY id DESC'),
            'semesters' => Database::fetchAll('SELECT s.*, ay.name AS year_name FROM semesters s JOIN academic_years ay ON ay.id = s.academic_year_id ORDER BY s.id DESC'),
            'grades' => Database::fetchAll('SELECT * FROM grades ORDER BY level_order ASC'),
            'classes' => Database::fetchAll('SELECT c.*, g.name AS grade_name, ay.name AS year_name FROM classes c JOIN grades g ON g.id = c.grade_id JOIN academic_years ay ON ay.id = c.academic_year_id ORDER BY c.id DESC'),
            'subjects' => Database::fetchAll('SELECT * FROM subjects ORDER BY name ASC'),
            'teachers' => Database::fetchAll('SELECT t.id, u.full_name FROM teachers t JOIN users u ON u.id = t.user_id ORDER BY u.full_name ASC'),
            'assignments' => Database::fetchAll(
                'SELECT ta.*, u.full_name AS teacher_name, c.name AS class_name, s.name AS subject_name, ay.name AS year_name
                 FROM teacher_assignments ta
                 JOIN teachers t ON t.id = ta.teacher_id
                 JOIN users u ON u.id = t.user_id
                 JOIN classes c ON c.id = ta.class_id
                 JOIN subjects s ON s.id = ta.subject_id
                 JOIN academic_years ay ON ay.id = ta.academic_year_id
                 ORDER BY ta.id DESC'
            ),
        ]);
    }

    public function createAcademicYear(Request $request): Response
    {
        $data = $request->allPost();
        $v = new Validator();
        if (!$v->validate($data, ['name' => 'required|min:3|max:80', 'start_date' => 'required', 'end_date' => 'required'])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/vice-principal/academic');
        }
        if (isset($data['is_active'])) {
            Database::execute('UPDATE academic_years SET is_active = 0');
        }
        Database::insert(
            'INSERT INTO academic_years (name, start_date, end_date, is_active, created_at) VALUES (?, ?, ?, ?, ?)',
            [trim((string) $data['name']), $data['start_date'], $data['end_date'], isset($data['is_active']) ? 1 : 0, date('Y-m-d H:i:s')]
        );
        $this->flash('success', Translator::get('vp.year_created'));
        return $this->redirect('/vice-principal/academic');
    }

    public function createSemester(Request $request): Response
    {
        $data = $request->allPost();
        $v = new Validator();
        if (!$v->validate($data, ['academic_year_id' => 'required|integer', 'name' => 'required|min:2|max:80', 'start_date' => 'required', 'end_date' => 'required'])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/vice-principal/academic');
        }
        if (isset($data['is_active'])) {
            Database::execute('UPDATE semesters SET is_active = 0');
        }
        Database::insert(
            'INSERT INTO semesters (academic_year_id, name, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)',
            [(int) $data['academic_year_id'], trim((string) $data['name']), $data['start_date'], $data['end_date'], isset($data['is_active']) ? 1 : 0]
        );
        $this->flash('success', Translator::get('vp.semester_created'));
        return $this->redirect('/vice-principal/academic');
    }

    public function createClass(Request $request): Response
    {
        $data = $request->allPost();
        $v = new Validator();
        if (!$v->validate($data, [
            'academic_year_id' => 'required|integer',
            'grade_id' => 'required|integer',
            'name' => 'required|min:1|max:80',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/vice-principal/academic');
        }
        Database::insert(
            'INSERT INTO classes (academic_year_id, grade_id, name, capacity, room_number, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [(int) $data['academic_year_id'], (int) $data['grade_id'], trim((string) $data['name']), (int) ($data['capacity'] ?? 35), trim((string) ($data['room_number'] ?? '')), date('Y-m-d H:i:s')]
        );
        $this->flash('success', Translator::get('vp.class_created'));
        return $this->redirect('/vice-principal/academic');
    }

    public function createSubject(Request $request): Response
    {
        $data = $request->allPost();
        $v = new Validator();
        if (!$v->validate($data, ['code' => 'required|min:2|max:40', 'name' => 'required|min:2|max:120'])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/vice-principal/academic');
        }
        Database::insert(
            'INSERT INTO subjects (code, name, description) VALUES (?, ?, ?)',
            [strtoupper(trim((string) $data['code'])), trim((string) $data['name']), trim((string) ($data['description'] ?? ''))]
        );
        $this->flash('success', Translator::get('vp.subject_created'));
        return $this->redirect('/vice-principal/academic');
    }

    public function assignTeacher(Request $request): Response
    {
        $data = $request->allPost();
        $teacherId = (int) ($data['teacher_id'] ?? 0);
        $classId = (int) ($data['class_id'] ?? 0);
        $subjectId = (int) ($data['subject_id'] ?? 0);
        $yearId = (int) ($data['academic_year_id'] ?? 0);

        $exists = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND academic_year_id = ?',
            [$teacherId, $classId, $subjectId, $yearId]
        );
        if ($exists > 0) {
            $this->flash('error', Translator::get('vp.assignment_exists'));
            return $this->redirect('/vice-principal/academic');
        }

        Database::transaction(static function () use ($teacherId, $classId, $subjectId, $yearId): void {
            Database::execute(
                'INSERT INTO teacher_assignments (teacher_id, class_id, subject_id, academic_year_id, created_at) VALUES (?, ?, ?, ?, ?)',
                [$teacherId, $classId, $subjectId, $yearId, date('Y-m-d H:i:s')]
            );
            $csExists = (int) Database::fetchColumn(
                'SELECT COUNT(*) FROM class_subjects WHERE class_id = ? AND subject_id = ?',
                [$classId, $subjectId]
            );
            if ($csExists === 0) {
                Database::execute('INSERT INTO class_subjects (class_id, subject_id, weekly_hours) VALUES (?, ?, 4)', [$classId, $subjectId]);
            }
        });

        $this->flash('success', Translator::get('vp.teacher_assigned'));
        return $this->redirect('/vice-principal/academic');
    }

    public function removeAssignment(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        Database::execute('DELETE FROM teacher_assignments WHERE id = ?', [$id]);
        $this->flash('success', Translator::get('vp.assignment_removed'));
        return $this->redirect('/vice-principal/academic');
    }

    public function staff(Request $request): Response
    {
        $teachers = Database::fetchAll(
            'SELECT t.*, u.username, u.full_name, u.email, u.status FROM teachers t JOIN users u ON u.id = t.user_id ORDER BY t.id DESC'
        );
        $counselors = Database::fetchAll(
            'SELECT c.*, u.username, u.full_name, u.email, u.status FROM counselors c JOIN users u ON u.id = c.user_id ORDER BY c.id DESC'
        );
        return $this->view('panels/vice_principal/staff', [
            'title' => Translator::get('menu.users_staff'),
            'teachers' => $teachers,
            'counselors' => $counselors,
        ]);
    }

    public function createStaff(Request $request): Response
    {
        $data = $request->allPost();
        $v = new Validator();
        if (!$v->validate($data, [
            'role' => 'required|in:teacher,counselor',
            'full_name' => 'required|min:2|max:150',
            'username' => 'required|min:3|max:80',
            'password' => 'required|min:8|max:128',
            'employee_code' => 'required|min:2|max:50',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/vice-principal/staff');
        }

        if (Database::fetch('SELECT id FROM users WHERE username = ?', [trim((string) $data['username'])])) {
            $this->flash('error', Translator::get('admin.username_exists'));
            return $this->redirect('/vice-principal/staff');
        }

        $now = date('Y-m-d H:i:s');
        Database::transaction(static function () use ($data, $now): void {
            $uid = Database::insert(
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
            $roleId = (int) Database::fetchColumn('SELECT id FROM roles WHERE slug = ?', [$data['role']]);
            Database::execute('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)', [$uid, $roleId]);

            if ($data['role'] === 'teacher') {
                Database::insert(
                    'INSERT INTO teachers (user_id, employee_code, specialization, hire_date) VALUES (?, ?, ?, ?)',
                    [$uid, trim((string) $data['employee_code']), trim((string) ($data['specialization'] ?? '')), date('Y-m-d')]
                );
            } else {
                Database::insert(
                    'INSERT INTO counselors (user_id, employee_code, specialty) VALUES (?, ?, ?)',
                    [$uid, trim((string) $data['employee_code']), trim((string) ($data['specialization'] ?? ''))]
                );
            }
        });

        $this->flash('success', Translator::get('vp.staff_created'));
        return $this->redirect('/vice-principal/staff');
    }

    public function toggleStaffStatus(Request $request, array $params): Response
    {
        $userId = (int) ($params['id'] ?? 0);
        $user = Database::fetch(
            "SELECT u.id, u.status FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id WHERE u.id = ? AND r.slug IN ('teacher','counselor')",
            [$userId]
        );
        if ($user) {
            $next = $user['status'] === 'active' ? 'inactive' : 'active';
            Database::execute('UPDATE users SET status = ?, updated_at = ? WHERE id = ?', [$next, date('Y-m-d H:i:s'), $userId]);
            $this->flash('success', Translator::get('admin.status_updated'));
        }
        return $this->redirect('/vice-principal/staff');
    }

    public function students(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $classFilter = (int) $request->query('class_id', 0);

        $sql = 'SELECT s.*, u.username, u.full_name, u.status, c.name AS class_name,
                       (SELECT COUNT(*) FROM parent_student ps WHERE ps.student_id = s.id) AS parent_count
                FROM students s
                JOIN users u ON u.id = s.user_id
                LEFT JOIN classes c ON c.id = s.class_id
                WHERE 1=1';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (u.full_name LIKE ? OR u.username LIKE ? OR s.student_code LIKE ?)';
            $like = '%' . $q . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        if ($classFilter > 0) {
            $sql .= ' AND s.class_id = ?';
            $params[] = $classFilter;
        }
        $sql .= ' ORDER BY s.id DESC';
        $students = Database::fetchAll($sql, $params);
        $classes = Database::fetchAll('SELECT c.*, g.name AS grade_name FROM classes c JOIN grades g ON g.id = c.grade_id ORDER BY c.name ASC');

        return $this->view('panels/vice_principal/students', [
            'title' => Translator::get('menu.students_bulk'),
            'students' => $students,
            'classes' => $classes,
            'q' => $q,
            'classFilter' => $classFilter,
        ]);
    }

    public function createStudent(Request $request): Response
    {
        $data = $request->allPost();
        $v = new Validator();
        if (!$v->validate($data, [
            'full_name' => 'required|min:2|max:150',
            'username' => 'required|min:3|max:80',
            'student_code' => 'required|min:2|max:50',
            'password' => 'required|min:6|max:128',
            'class_id' => 'required|integer',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/vice-principal/students');
        }

        if (Database::fetch('SELECT id FROM users WHERE username = ?', [trim((string) $data['username'])])
            || Database::fetch('SELECT id FROM students WHERE student_code = ?', [trim((string) $data['student_code'])])) {
            $this->flash('error', Translator::get('vp.duplicate_student'));
            return $this->redirect('/vice-principal/students');
        }

        $this->insertStudentRecord($data);
        $this->flash('success', Translator::get('vp.student_registered'));
        return $this->redirect('/vice-principal/students');
    }

    public function reassignStudentClass(Request $request, array $params): Response
    {
        $studentId = (int) ($params['id'] ?? 0);
        $classId = (int) $request->post('class_id', 0);
        if ($studentId > 0 && $classId > 0) {
            Database::execute('UPDATE students SET class_id = ? WHERE id = ?', [$classId, $studentId]);
            $this->flash('success', Translator::get('vp.class_updated'));
        }
        return $this->redirect('/vice-principal/students');
    }

    public function sampleStudentsCsv(Request $request): Response
    {
        return Response::csv(
            'darsyar_students_sample.csv',
            ['full_name', 'username', 'student_code', 'guardian_phone', 'password'],
            [
                ['امیرحسین رضایی', 'amir_rezaei', 'STU-2001', '09121112233', 'Stu@2001'],
                ['نازنین کریمی', 'nazanin_k', 'STU-2002', '09124445566', 'Stu@2002'],
            ]
        );
    }

    public function importStudentsCsv(Request $request): Response
    {
        $classId = (int) $request->post('class_id', 0);
        $file = $request->file('csv_file');
        if ($classId <= 0 || !$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->flash('error', Translator::get('errors.upload_failed'));
            return $this->redirect('/vice-principal/students');
        }

        $handle = fopen((string) $file['tmp_name'], 'r');
        if (!$handle) {
            $this->flash('error', Translator::get('errors.upload_failed'));
            return $this->redirect('/vice-principal/students');
        }

        $imported = 0;
        $skipped = 0;
        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
                continue;
            }
            $fullName = trim((string) $row[0]);
            $username = trim((string) $row[1]);
            $studentCode = trim((string) $row[2]);
            $guardianPhone = trim((string) ($row[3] ?? ''));
            $password = trim((string) ($row[4] ?? '')) ?: ('Stu@' . substr($studentCode, -4) . '26');

            if ($fullName === '' || $username === '' || $studentCode === '') {
                $skipped++;
                continue;
            }
            if (Database::fetch('SELECT id FROM users WHERE username = ?', [$username])
                || Database::fetch('SELECT id FROM students WHERE student_code = ?', [$studentCode])) {
                $skipped++;
                continue;
            }

            $this->insertStudentRecord([
                'full_name' => $fullName,
                'username' => $username,
                'student_code' => $studentCode,
                'guardian_phone' => $guardianPhone,
                'password' => $password,
                'class_id' => $classId,
            ]);
            $imported++;
        }
        fclose($handle);

        $authUser = $this->authUser();
        AuditLogService::log((int) $authUser['id'], 'students.csv_imported', 'class', $classId, ['imported' => $imported, 'skipped' => $skipped]);
        $this->flash('success', Translator::get('vp.import_done', ['imported' => $imported, 'skipped' => $skipped]));
        return $this->redirect('/vice-principal/students');
    }

    public function parents(Request $request): Response
    {
        $classes = Database::fetchAll('SELECT c.*, g.name AS grade_name FROM classes c JOIN grades g ON g.id = c.grade_id ORDER BY c.name ASC');
        $parents = Database::fetchAll(
            'SELECT p.id, u.username, u.full_name, p.emergency_phone, u.must_change_password,
                    GROUP_CONCAT(su.full_name SEPARATOR ", ") AS children_names
             FROM parents p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN parent_student ps ON ps.parent_id = p.id
             LEFT JOIN students s ON s.id = ps.student_id
             LEFT JOIN users su ON su.id = s.user_id
             GROUP BY p.id, u.username, u.full_name, p.emergency_phone, u.must_change_password
             ORDER BY p.id DESC'
        );
        $students = Database::fetchAll(
            'SELECT s.id, s.student_code, u.full_name FROM students s JOIN users u ON u.id = s.user_id ORDER BY u.full_name ASC'
        );
        $generated = Session::get('last_generated_parents', []);

        return $this->view('panels/vice_principal/parents', [
            'title' => Translator::get('menu.parents_bulk'),
            'classes' => $classes,
            'parents' => $parents,
            'students' => $students,
            'generated' => $generated,
        ]);
    }

    public function generateBulkParents(Request $request): Response
    {
        $classId = (int) $request->post('class_id', 0);
        $linkSiblings = $request->post('link_siblings_by_phone', '1') === '1';
        $authUser = $this->authUser();

        $result = BulkParentGeneratorService::generateForStudents(
            $classId > 0 ? $classId : null,
            (int) $authUser['id'],
            $linkSiblings
        );

        Session::set('last_generated_parents', $result['credentials']);
        $this->flash('success', Translator::get('bulk_parents.success', [
            'created' => $result['created'],
            'linked' => $result['linked'],
            'skipped' => $result['skipped'],
        ]));
        return $this->redirect('/vice-principal/parents');
    }

    public function exportGeneratedParentsCsv(Request $request): Response
    {
        $creds = Session::get('last_generated_parents', []);
        $rows = [];
        foreach ($creds as $c) {
            $rows[] = [
                $c['student_code'],
                $c['student_name'],
                $c['class_name'],
                $c['parent_username'],
                $c['temporary_password'],
            ];
        }
        return Response::csv(
            'parent_credentials_' . date('Ymd_His') . '.csv',
            ['Student Code', 'Student Name', 'Class', 'Parent Username', 'Temporary Password'],
            $rows
        );
    }

    public function printGeneratedParents(Request $request): Response
    {
        $creds = Session::get('last_generated_parents', []);
        return $this->view('panels/vice_principal/parents_print', [
            'title' => Translator::get('report_card.parent_creds_title'),
            'credentials' => $creds,
        ], 'print');
    }

    public function linkSibling(Request $request): Response
    {
        $parentId = (int) $request->post('parent_id', 0);
        $studentId = (int) $request->post('student_id', 0);
        $rel = (string) $request->post('relationship', 'guardian');
        if ($parentId > 0 && $studentId > 0) {
            $ok = BulkParentGeneratorService::linkSibling($parentId, $studentId, $rel);
            $this->flash($ok ? 'success' : 'error', Translator::get($ok ? 'bulk_parents.student_linked' : 'bulk_parents.link_exists'));
        }
        return $this->redirect('/vice-principal/parents');
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
        $classes = Database::fetchAll('SELECT * FROM classes ORDER BY name ASC');
        $years = Database::fetchAll('SELECT * FROM academic_years ORDER BY id DESC');

        return $this->view('panels/vice_principal/timetables', [
            'title' => Translator::get('menu.timetables'),
            'timetables' => $timetables,
            'classes' => $classes,
            'years' => $years,
        ]);
    }

    public function saveTimetable(Request $request): Response
    {
        $data = $request->allPost();
        $v = new Validator();
        if (!$v->validate($data, [
            'academic_year_id' => 'required|integer',
            'class_id' => 'required|integer',
            'title' => 'required|min:2|max:150',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/vice-principal/timetables');
        }

        $filePath = null;
        $file = $request->file('timetable_file');
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $filePath = Uploader::upload($file, 'timetables', Uploader::ALLOWED_DOCS_AND_IMAGES);
            } catch (\Throwable $e) {
                $this->flash('error', $e->getMessage());
                return $this->redirect('/vice-principal/timetables');
            }
        }

        $scheduleJson = json_encode([
            'saturday' => trim((string) ($data['sat'] ?? '')),
            'sunday' => trim((string) ($data['sun'] ?? '')),
            'monday' => trim((string) ($data['mon'] ?? '')),
            'tuesday' => trim((string) ($data['tue'] ?? '')),
            'wednesday' => trim((string) ($data['wed'] ?? '')),
        ], JSON_UNESCAPED_UNICODE);

        $authUser = $this->authUser();
        $now = date('Y-m-d H:i:s');
        Database::insert(
            'INSERT INTO timetables (academic_year_id, class_id, title, file_path, schedule_json, uploaded_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [(int) $data['academic_year_id'], (int) $data['class_id'], trim((string) $data['title']), $filePath, $scheduleJson, (int) $authUser['id'], $now, $now]
        );

        $this->flash('success', Translator::get('vp.timetable_saved'));
        $role = $authUser['role'] ?? '';
        return $this->redirect($role === 'director' ? '/director/timetables' : '/vice-principal/timetables');
    }

    public function attendance(Request $request): Response
    {
        $classId = (int) $request->query('class_id', 0);
        $date = (string) $request->query('date', date('Y-m-d'));
        $classes = Database::fetchAll('SELECT * FROM classes ORDER BY name ASC');
        if ($classId === 0 && !empty($classes)) {
            $classId = (int) $classes[0]['id'];
        }

        $students = $classId > 0 ? Database::fetchAll(
            "SELECT s.id, s.student_code, u.full_name,
                    (SELECT a.status FROM attendance a WHERE a.student_id = s.id AND a.class_id = ? AND a.attendance_date = ? AND a.scope = 'school' LIMIT 1) AS today_status
             FROM students s
             JOIN users u ON u.id = s.user_id
             WHERE s.class_id = ?
             ORDER BY u.full_name ASC",
            [$classId, $date, $classId]
        ) : [];

        $monthStart = date('Y-m-01', strtotime($date) ?: time());
        $monthStats = $classId > 0 ? Database::fetchAll(
            'SELECT status, COUNT(*) AS cnt FROM attendance WHERE class_id = ? AND attendance_date >= ? GROUP BY status',
            [$classId, $monthStart]
        ) : [];

        return $this->view('panels/vice_principal/attendance', [
            'title' => Translator::get('menu.attendance'),
            'classes' => $classes,
            'selectedClassId' => $classId,
            'selectedDate' => $date,
            'students' => $students,
            'monthStats' => $monthStats,
        ]);
    }

    public function saveAttendance(Request $request): Response
    {
        $classId = (int) $request->post('class_id', 0);
        $date = (string) $request->post('attendance_date', date('Y-m-d'));
        $statuses = $request->post('status', []);
        $notes = $request->post('note', []);
        $authUser = $this->authUser();

        if ($classId > 0 && is_array($statuses)) {
            $count = AttendanceService::recordBatch($classId, null, $date, 'school', $statuses, is_array($notes) ? $notes : [], (int) $authUser['id']);
            $this->flash('success', Translator::get('vp.attendance_saved', ['count' => $count]));
        }
        return $this->redirect('/vice-principal/attendance?class_id=' . $classId . '&date=' . urlencode($date));
    }

    public function reportCards(Request $request): Response
    {
        $classes = Database::fetchAll('SELECT * FROM classes ORDER BY name ASC');
        $years = Database::fetchAll('SELECT * FROM academic_years ORDER BY id DESC');
        $semesters = Database::fetchAll('SELECT * FROM semesters ORDER BY id DESC');
        $cards = Database::fetchAll(
            'SELECT rc.*, u.full_name AS student_name, s.student_code, c.name AS class_name
             FROM report_cards rc
             JOIN students s ON s.id = rc.student_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN classes c ON c.id = s.class_id
             ORDER BY rc.id DESC'
        );

        return $this->view('panels/vice_principal/report_cards', [
            'title' => Translator::get('menu.report_cards'),
            'classes' => $classes,
            'years' => $years,
            'semesters' => $semesters,
            'cards' => $cards,
        ]);
    }

    public function generateReportCards(Request $request): Response
    {
        $classId = (int) $request->post('class_id', 0);
        $yearId = (int) $request->post('academic_year_id', 0);
        $semesterId = (int) $request->post('semester_id', 0) ?: null;
        $type = (string) $request->post('report_type', 'monthly');
        $period = trim((string) $request->post('period_label', date('Y-m')));
        $authUser = $this->authUser();

        $count = ReportCardService::generateForClass($classId, $yearId, $semesterId, $type, $period, (int) $authUser['id']);
        $this->flash('success', Translator::get('vp.cards_generated', ['count' => $count]));
        return $this->redirect('/vice-principal/report-cards');
    }

    public function updateReportCardComment(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        $comment = trim((string) $request->post('comments', ''));
        Database::execute('UPDATE report_cards SET comments = ?, updated_at = ? WHERE id = ?', [$comment ?: null, date('Y-m-d H:i:s'), $id]);
        $this->flash('success', Translator::get('vp.comments_updated'));
        return $this->redirect('/vice-principal/report-cards');
    }

    private function insertStudentRecord(array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $studentRoleId = (int) Database::fetchColumn("SELECT id FROM roles WHERE slug = 'student'");
        Database::transaction(static function () use ($data, $studentRoleId, $now): void {
            $uid = Database::insert(
                'INSERT INTO users (username, email, phone, password_hash, full_name, locale, theme, status, must_change_password, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)',
                [
                    trim((string) $data['username']),
                    trim((string) ($data['email'] ?? '')) ?: null,
                    trim((string) ($data['guardian_phone'] ?? '')) ?: null,
                    AuthService::hashPassword((string) $data['password']),
                    trim((string) $data['full_name']),
                    'fa',
                    'light',
                    'active',
                    $now,
                    $now,
                ]
            );
            Database::execute('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)', [$uid, $studentRoleId]);
            Database::insert(
                'INSERT INTO students (user_id, student_code, national_id, class_id, guardian_phone, enrollment_date)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $uid,
                    trim((string) $data['student_code']),
                    trim((string) ($data['national_id'] ?? '')) ?: null,
                    (int) $data['class_id'],
                    trim((string) ($data['guardian_phone'] ?? '')) ?: null,
                    date('Y-m-d'),
                ]
            );
        });
    }
}
