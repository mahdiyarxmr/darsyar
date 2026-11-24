<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/database/seeders/DatabaseSeeder.php';
require_once dirname(__DIR__) . '/tests/TestRunner.php';

use App\Core\Csrf;
use App\Core\Database;
use App\Core\DateHelper;
use App\Core\Request;
use App\Core\Session;
use App\Core\Translator;
use App\Core\Uploader;
use App\Middleware\ForcePasswordChangeMiddleware;
use App\Middleware\RoleMiddleware;
use App\Modules\Attendance\AttendanceService;
use App\Modules\Auth\AuthService;
use App\Modules\Authorization\AuthorizationService;
use App\Modules\Authorization\OwnershipGuard;
use App\Modules\Counseling\CounselingService;
use App\Modules\Exams\DailyTestService;
use App\Modules\Exams\ExamService;
use App\Modules\Homework\HomeworkService;
use App\Modules\Notifications\NotificationService;
use App\Modules\Parents\BulkParentGeneratorService;
use App\Modules\Reports\PerformanceRatingService;
use App\Modules\Reports\ReportCardService;
use Tests\TestRunner;

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$t = new TestRunner();
TestRunner::bootFreshDatabase();

echo "[1] Authentication, CSRF, Rate Limiting & RBAC\n";
$good = AuthService::attempt('superadmin', 'School@2026', '127.0.0.2');
$t->assertTrue($good['ok'], 'Valid superadmin login succeeds');
$t->assertEquals('super_admin', $good['role'], 'Super admin role resolved');

$bad = AuthService::attempt('director', 'WrongPass123', '10.0.0.55');
$t->assertTrue(!$bad['ok'], 'Rejects wrong password');

// Rate limiter test (4 more wrong attempts -> 5th locks director)
for ($i = 0; $i < 4; $i++) {
    AuthService::attempt('director', 'WrongPass123', '10.0.0.55');
}
$locked = AuthService::attempt('director', 'School@2026', '10.0.0.55');
$t->assertTrue(!$locked['ok'], 'Rate limiter blocks after 5 failed attempts');

$csrfToken = Csrf::token();
$t->assertTrue(Csrf::verify($csrfToken), 'CSRF verifies valid token');
$t->assertTrue(!Csrf::verify('invalid-token'), 'CSRF rejects invalid token');

// Role Middleware check
$teacherUid = (int) Database::fetchColumn("SELECT id FROM users WHERE username = 'teacher1'");
Session::set('auth_user_id', $teacherUid);
AuthService::clearCache();
$mwAdmin = new RoleMiddleware('super_admin');
$resp403 = $mwAdmin->handle(new Request('GET', '/admin/dashboard'));
$t->assertEquals(403, $resp403?->status(), 'RoleMiddleware blocks teacher from super_admin route');

echo "\n[2] Anti-IDOR Isolation (Parent-Child & Teacher-Class)\n";
$parent1Uid = (int) Database::fetchColumn("SELECT id FROM users WHERE username = 'parent1'");
$stu1Id = (int) Database::fetchColumn("SELECT id FROM students WHERE student_code = 'STU-1001'");
$stu2Id = (int) Database::fetchColumn("SELECT id FROM students WHERE student_code = 'STU-1002'");
$t->assertTrue(OwnershipGuard::parentOwnsStudent($parent1Uid, $stu1Id), 'Parent1 can access linked child student1');
$t->assertTrue(!OwnershipGuard::parentOwnsStudent($parent1Uid, $stu2Id), 'Parent1 CANNOT access unlinked student2');

$teacher1Id = (int) Database::fetchColumn('SELECT id FROM teachers WHERE user_id = ?', [$teacherUid]);
$class10AId = (int) Database::fetchColumn("SELECT id FROM classes WHERE name = '10-A'");
$class11AId = (int) Database::fetchColumn("SELECT id FROM classes WHERE name = '11-A'");
$mathId = (int) Database::fetchColumn("SELECT id FROM subjects WHERE code = 'MATH101'");
$t->assertTrue(OwnershipGuard::teacherAssignedToClass($teacher1Id, $class10AId, $mathId), 'Teacher1 assigned to 10-A Math');
$t->assertTrue(!OwnershipGuard::teacherAssignedToClass($teacher1Id, $class11AId, $mathId), 'Teacher1 NOT assigned to 11-A Math');

echo "\n[3] Bulk Parent Account Generation & Duplicate Prevention\n";
$vpUid = (int) Database::fetchColumn("SELECT id FROM users WHERE username = 'viceprincipal'");
$bulk1 = BulkParentGeneratorService::generateForStudents($class10AId, $vpUid, true);
$t->assertEquals(1, $bulk1['created'], 'Creates 1 parent account for unlinked student2 in 10-A');
$t->assertEquals(1, $bulk1['skipped'], 'Skips already-linked student1 in 10-A');
$t->assertEquals(1, count($bulk1['credentials']), 'Returns generated credential entry');

// Re-running must create 0 duplicates
$bulk2 = BulkParentGeneratorService::generateForStudents($class10AId, $vpUid, true);
$t->assertEquals(0, $bulk2['created'], 'Re-running bulk generator creates 0 duplicate parent accounts');

// Forced password change middleware on generated parent
$genParentUsername = $bulk1['credentials'][0]['parent_username'];
$genParentTempPass = $bulk1['credentials'][0]['temporary_password'];
$genLogin = AuthService::attempt($genParentUsername, $genParentTempPass, '127.0.0.3');
$t->assertTrue($genLogin['ok'] && $genLogin['must_change_password'] === true, 'Generated parent logs in with must_change_password=true');
$forceMw = new ForcePasswordChangeMiddleware();
$forceResp = $forceMw->handle(new Request('GET', '/parent/dashboard'));
$t->assertEquals(302, $forceResp?->status(), 'ForcePasswordChangeMiddleware redirects generated parent to password rotation');

echo "\n[4] Exams: Windows, Auto-Grading, Negative Marking, Manual Grading & Offline\n";
$yearId = (int) Database::fetchColumn('SELECT id FROM academic_years WHERE is_active = 1');
$nowTs = time();
$examId = Database::insert(
    'INSERT INTO exams (title, exam_mode, subject_id, class_id, teacher_id, academic_year_id, starts_at, ends_at, duration_minutes, max_score, negative_marking_ratio, max_attempts, late_finish_policy, status, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [
        'Midterm Calculus',
        'online',
        $mathId,
        $class10AId,
        $teacher1Id,
        $yearId,
        date('Y-m-d H:i:s', $nowTs - 600),
        date('Y-m-d H:i:s', $nowTs + 1800),
        30,
        20.0,
        0.25,
        1,
        'hard_cutoff',
        'published',
        date('Y-m-d H:i:s'),
        date('Y-m-d H:i:s'),
    ]
);
$q1Id = Database::insert('INSERT INTO exam_questions (exam_id, type, prompt, points, sort_order) VALUES (?, ?, ?, ?, ?)', [$examId, 'mcq', '2 + 2 = ?', 10, 1]);
$q1OptA = Database::insert('INSERT INTO exam_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, 1, 1)', [$q1Id, '4']);
$q1OptB = Database::insert('INSERT INTO exam_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, 0, 2)', [$q1Id, '5']);
$q2Id = Database::insert('INSERT INTO exam_questions (exam_id, type, prompt, points, sort_order) VALUES (?, ?, ?, ?, ?)', [$examId, 'descriptive', 'Explain limit.', 10, 2]);

$examRow = Database::fetch('SELECT * FROM exams WHERE id = ?', [$examId]);
$t->assertTrue(!ExamService::checkAvailability($examRow, $stu1Id, $nowTs - 1200)['allowed'], 'Rejects exam before start window');
$t->assertTrue(ExamService::checkAvailability($examRow, $stu1Id, $nowTs)['allowed'], 'Allows exam inside window');
$t->assertTrue(!ExamService::checkAvailability($examRow, $stu1Id, $nowTs + 3600)['allowed'], 'Rejects exam after end window');

$started = ExamService::startOrResumeAttempt($examRow, $stu1Id, $nowTs);
$t->assertTrue($started['ok'], 'Student1 starts exam attempt');
$attemptId = (int) $started['attempt']['id'];

$submitted = ExamService::submitAttempt($attemptId, $stu1Id, [
    $q1Id => (string) $q1OptA,
    $q2Id => 'Limit describes function behavior near a point.',
], $nowTs + 300);
$t->assertTrue($submitted['ok'] && $submitted['has_descriptive'], 'Exam submitted with descriptive pending');
$t->assertEquals(10.0, (float) $submitted['attempt']['auto_score'], 'Objective question auto-graded (10/20)');

$descAnsId = (int) Database::fetchColumn('SELECT id FROM exam_answers WHERE attempt_id = ? AND question_id = ?', [$attemptId, $q2Id]);
ExamService::gradeDescriptiveAttempt($attemptId, [$descAnsId => 8.0], [$descAnsId => 'Good explanation'], $teacherUid);
$finalScore = (float) Database::fetchColumn('SELECT score FROM exam_results WHERE exam_id = ? AND student_id = ?', [$examId, $stu1Id]);
$t->assertEquals(18.0, $finalScore, 'Combined auto + manual grade saved to exam_results (18/20)');
$t->assertTrue(NotificationService::unreadCount($parent1Uid) > 0, 'Parent1 automatically notified of exam result');

// Offline exam
$offExamId = Database::insert(
    'INSERT INTO exams (title, exam_mode, subject_id, class_id, teacher_id, academic_year_id, starts_at, ends_at, duration_minutes, max_score, status, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    ['Paper Quiz 1', 'offline', $mathId, $class10AId, $teacher1Id, $yearId, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), 45, 20.0, 'published', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
);
ExamService::saveOfflineScore($offExamId, $stu1Id, 16.0, 20.0, 'Well done', $teacherUid, true);
$t->assertEquals(80.0, (float) Database::fetchColumn('SELECT percentage FROM exam_results WHERE exam_id = ? AND student_id = ?', [$offExamId, $stu1Id]), 'Offline exam score recorded (80%)');

// Daily practice test
$dtId = Database::insert('INSERT INTO daily_tests (title, subject_id, class_id, teacher_id, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?)', ['Math Sprint', $mathId, $class10AId, $teacher1Id, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
$dtQId = Database::insert('INSERT INTO daily_test_questions (daily_test_id, prompt, options_json, correct_index) VALUES (?, ?, ?, ?)', [$dtId, '3*3=?', json_encode(['6', '9', '12']), 1]);
$dtRes = DailyTestService::submitPracticeAttempt($dtId, $stu1Id, [$dtQId => '1'], 42);
$t->assertEquals(100.0, $dtRes['score_percentage'], 'Daily practice test graded independently (100%)');

echo "\n[5] Homework, Counseling Study Plans & Attendance\n";
$hwId = HomeworkService::createAssignment($teacher1Id, $class10AId, $mathId, 'HW 1', 'Solve set A', null, 'PDF', date('Y-m-d H:i:s', strtotime('+2 days')), 20.0);
$subId = HomeworkService::submitWork($hwId, $stu1Id, 'Done all exercises', null);
HomeworkService::gradeSubmission($subId, $teacher1Id, 19.0, 'Great work', 'accepted');
$t->assertEquals('accepted', Database::fetchColumn('SELECT status FROM homework_submissions WHERE id = ?', [$subId]), 'Homework graded and accepted');

$counselorId = (int) Database::fetchColumn('SELECT id FROM counselors LIMIT 1');
$planId = CounselingService::createStudyPlan($stu1Id, $counselorId, 'Week 1 Plan', date('Y-m-d'), date('Y-m-d', strtotime('+6 days')), 'Focus Math');
$t1Id = CounselingService::addTask($planId, 'saturday', $mathId, 'Math 60m', '16:00', '17:00', 60, 'Ch1');
$t2Id = CounselingService::addTask($planId, 'sunday', $mathId, 'Math 45m', '16:00', '16:45', 45, 'Ch2');
$prog = CounselingService::toggleTaskForStudent($t1Id, $stu1Id, true);
$t->assertEquals(50.0, $prog['percentage'], 'Study plan calculates 50% completion (1/2 tasks)');

$attCount = AttendanceService::recordBatch($class10AId, null, date('Y-m-d'), 'school', [$stu1Id => 'present', $stu2Id => 'absent'], [], $vpUid);
$t->assertEquals(2, $attCount, 'Attendance recorded for 2 students');

echo "\n[6] Report Cards & Performance Rating Engine\n";
$rcCount = ReportCardService::generateForClass($class10AId, $yearId, null, 'monthly', 'November 2025', $vpUid);
$t->assertEquals(2, $rcCount, 'Generated monthly report cards for class 10-A');
$rcAvg = (float) Database::fetchColumn('SELECT final_average FROM report_cards WHERE student_id = ? ORDER BY id DESC LIMIT 1', [$stu1Id]);
$t->assertTrue($rcAvg > 80.0, 'Student1 report card average reflects exams + homework (' . $rcAvg . '%)');

$stuRating = PerformanceRatingService::evaluateStudent($stu1Id);
$t->assertTrue($stuRating['total_score'] > 70.0, 'Student rating engine computes weighted score (' . $stuRating['total_score'] . ')');
$tchRating = PerformanceRatingService::evaluateTeacher($teacher1Id);
$t->assertTrue($tchRating['total_score'] > 60.0, 'Teacher rating engine computes weighted score (' . $tchRating['total_score'] . ')');

echo "\n[7] Security: File Upload Validation & Multilingual Parity\n";
$tmpBad = tempnam(sys_get_temp_dir(), 'bad');
file_put_contents($tmpBad, "<?php echo 'hack'; ?>");
$rejected = false;
try {
    Uploader::upload([
        'name' => 'shell.php',
        'tmp_name' => $tmpBad,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tmpBad),
    ], 'homework');
} catch (\Throwable) {
    $rejected = true;
}
@unlink($tmpBad);
$t->assertTrue($rejected, 'Uploader blocks dangerous PHP file upload');

$consistency = Translator::checkConsistency();
$t->assertTrue($consistency['ok'], 'Persian (fa) and English (en) translation files have 100% key parity and zero empty values');
$t->assertEquals([], $consistency['missing_in_fa'], 'No English keys missing in Persian (fa)');
$t->assertEquals([], $consistency['missing_in_en'], 'No Persian keys missing in English (en)');
$t->assertEquals(['fa', 'en'], \App\Core\Config::get('app.supported_locales'), 'Supported locales strictly limited to Persian (fa) and English (en)');

Translator::setLocale('ar');
$t->assertEquals('fa', Translator::locale(), 'Unsupported locale (ar) safely falls back to primary Persian (fa)');
$t->assertEquals('rtl', Translator::direction(), 'Persian sets RTL layout direction');
$t->assertEquals('درسیار', Translator::get('app.name'), 'Primary Persian brand name resolved');
$t->assertEquals('۱۴۰۴/۰۹/۰۳', DateHelper::format('2025-11-24', false), 'Persian Jalali date & digit localization works');
$t->assertEquals('۴۲', DateHelper::number(42), 'Persian localized digits work');
$t->assertEquals('87.5', DateHelper::number('87.5', 1) === '۸۷.۵' ? '87.5' : 'mismatch', 'Persian decimal number formatting works');
$t->assertEquals('۸۷.۵٪', DateHelper::percent(87.5), 'Persian percentage formatting works');

Translator::setLocale('en');
$t->assertEquals('ltr', Translator::direction(), 'English sets LTR layout direction');
$t->assertEquals('DarsYar', Translator::get('app.name'), 'English brand name resolved');
$t->assertEquals('2025-11-24', DateHelper::format('2025-11-24', false), 'English Gregorian date formatting works');

echo "\n[8] DarsYar Extensions: Schools, Counseling Notes, Study Plan Requests & Skyroom Diagnostic\n";
$schoolsCount = (int) Database::fetchColumn('SELECT COUNT(*) FROM schools WHERE code = ?', ['SCH-001']);
$t->assertTrue($schoolsCount >= 1, 'Default school SCH-001 seeded in schools table');

$assignmentsCount = (int) Database::fetchColumn('SELECT COUNT(*) FROM counselor_assignments');
$notesCount = (int) Database::fetchColumn('SELECT COUNT(*) FROM counseling_notes');
$t->assertTrue($assignmentsCount >= 1, 'Counselor assignments seeded cleanly');
$t->assertTrue($notesCount >= 1, 'Counseling notes seeded cleanly');

$nowTs = date('Y-m-d H:i:s');
$reqId = Database::insert(
    'INSERT INTO study_plan_requests (student_id, note, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
    [1, 'Need extra math practice test schedule', 'pending', $nowTs, $nowTs]
);
$t->assertTrue($reqId > 0, 'Study plan request record created');

$skyDiag = \App\Modules\Classrooms\SkyroomService::testConnection();
$t->assertEquals(false, $skyDiag['ok'], 'Skyroom diagnostic does not fake success without API credentials');
$t->assertEquals(false, $skyDiag['configured'], 'Skyroom diagnostic accurately reports unconfigured status');

$badRegRole = AuthService::registerPublic([
    'role' => 'super_admin',
    'full_name' => 'Fake Admin',
    'username' => 'fake_admin_reg',
    'password' => 'StrongPass99',
], '127.0.0.1');
$t->assertEquals(false, $badRegRole['ok'], 'Public registration blocks privileged role escalation');

$stuReg = AuthService::registerPublic([
    'role' => 'student',
    'full_name' => 'نرگس محمدی',
    'username' => 'narges_std',
    'phone' => '09123334455',
    'password' => 'StrongPass99',
], '127.0.0.1');
$t->assertEquals(true, $stuReg['ok'], 'Public student self-registration creates user and student profile');
$t->assertEquals('student', $stuReg['role'] ?? '', 'Registered student assigned student role');

$dupReg = AuthService::registerPublic([
    'role' => 'parent',
    'full_name' => 'تکراری',
    'username' => 'narges_std',
    'password' => 'StrongPass99',
], '127.0.0.1');
$t->assertEquals(false, $dupReg['ok'], 'Public registration rejects duplicate username');

$prtReg = AuthService::registerPublic([
    'role' => 'parent',
    'full_name' => 'مریم کریمی',
    'username' => 'maryam_prt',
    'phone' => '09127778899',
    'password' => 'StrongPass99',
], '127.0.0.1');
$t->assertEquals(true, $prtReg['ok'], 'Public parent self-registration creates user and parent profile');
$t->assertEquals('parent', $prtReg['role'] ?? '', 'Registered parent assigned parent role');
AuthService::logout();

exit($t->summary());
