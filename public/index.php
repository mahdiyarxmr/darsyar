<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\App;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\ForcePasswordChangeMiddleware;
use App\Middleware\RoleMiddleware;
use App\Modules\Administrators\DirectorController;
use App\Modules\Administrators\SuperAdminController;
use App\Modules\Auth\AuthController;
use App\Modules\CMS\PublicController;
use App\Modules\Counselors\CounselorController;
use App\Modules\Discussions\DiscussionController;
use App\Modules\Notifications\NotificationController;
use App\Modules\Parents\ParentController;
use App\Modules\Reports\ReportCardController;
use App\Modules\Students\StudentController;
use App\Modules\Teachers\TeacherController;
use App\Modules\Users\VicePrincipalController;

$router = new Router();

// Public Website & Media
$router->get('/', [PublicController::class, 'home']);
$router->get('/news', [PublicController::class, 'news']);
$router->get('/events', [PublicController::class, 'events']);
$router->get('/media/{id}', [PublicController::class, 'servePublicMedia']);

// Authentication, Locale & Theme
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/locale/{lang}', [AuthController::class, 'switchLocale']);
$router->post('/api/theme', [AuthController::class, 'switchTheme']);

$auth = [AuthMiddleware::class, ForcePasswordChangeMiddleware::class];

// Shared Authenticated Routes
$router->get('/dashboard', [AuthController::class, 'dashboardRedirect'], $auth);
$router->get('/account/password', [AuthController::class, 'showChangePassword'], [AuthMiddleware::class]);
$router->post('/account/password', [AuthController::class, 'updatePassword'], [AuthMiddleware::class]);

$router->get('/notifications', [NotificationController::class, 'index'], $auth);
$router->post('/notifications/read-all', [NotificationController::class, 'markAllRead'], $auth);
$router->post('/api/notifications/{id}/read', [NotificationController::class, 'markRead'], $auth);

$router->get('/discussions', [DiscussionController::class, 'index'], array_merge($auth, [[RoleMiddleware::class, 'teacher|student']]));
$router->post('/discussions/create', [DiscussionController::class, 'createGroup'], array_merge($auth, [[RoleMiddleware::class, 'teacher']]));
$router->post('/discussions/{id}/messages', [DiscussionController::class, 'postMessage'], array_merge($auth, [[RoleMiddleware::class, 'teacher|student']]));

$router->get('/report-cards/{id}/print', [ReportCardController::class, 'printCard'], $auth);
$router->get('/files/{category}/{id}', [ReportCardController::class, 'downloadFile'], $auth);

// PANEL 1 — Super Admin
$sa = array_merge($auth, [[RoleMiddleware::class, 'super_admin']]);
$router->get('/admin/dashboard', [SuperAdminController::class, 'dashboard'], $sa);
$router->get('/admin/schools', [SuperAdminController::class, 'schools'], $sa);
$router->post('/admin/schools/create', [SuperAdminController::class, 'createSchool'], $sa);
$router->post('/admin/schools/{id}/toggle', [SuperAdminController::class, 'toggleSchoolStatus'], $sa);
$router->get('/admin/admins', [SuperAdminController::class, 'admins'], $sa);
$router->post('/admin/admins/create', [SuperAdminController::class, 'createAdmin'], $sa);
$router->post('/admin/admins/{id}/toggle', [SuperAdminController::class, 'toggleAdminStatus'], $sa);
$router->get('/admin/cms', [SuperAdminController::class, 'cms'], $sa);
$router->post('/admin/cms/post', [SuperAdminController::class, 'saveCmsPost'], $sa);
$router->post('/admin/cms/media', [SuperAdminController::class, 'uploadMedia'], $sa);
$router->post('/admin/cms/post/{id}/delete', [SuperAdminController::class, 'deleteCmsPost'], $sa);
$router->get('/admin/announcements', [SuperAdminController::class, 'announcements'], $sa);
$router->post('/admin/announcements/create', [SuperAdminController::class, 'createAnnouncement'], $sa);
$router->post('/admin/announcements/{id}/delete', [SuperAdminController::class, 'deleteAnnouncement'], $sa);
$router->get('/admin/events', [SuperAdminController::class, 'events'], $sa);
$router->post('/admin/events/create', [SuperAdminController::class, 'createEvent'], $sa);
$router->post('/admin/events/{id}/delete', [SuperAdminController::class, 'deleteEvent'], $sa);
$router->get('/admin/settings', [SuperAdminController::class, 'settings'], $sa);
$router->post('/admin/settings', [SuperAdminController::class, 'saveSettings'], $sa);
$router->post('/admin/settings/test-skyroom', [SuperAdminController::class, 'testSkyroom'], $sa);
$router->get('/admin/audit-logs', [SuperAdminController::class, 'auditLogs'], $sa);
$router->get('/admin/health', [SuperAdminController::class, 'health'], $sa);

// PANEL 2 — School Director
$dir = array_merge($auth, [[RoleMiddleware::class, 'director']]);
$router->get('/director/dashboard', [DirectorController::class, 'dashboard'], $dir);
$router->get('/director/vice-principals', [DirectorController::class, 'vicePrincipals'], $dir);
$router->post('/director/vice-principals/create', [DirectorController::class, 'createVicePrincipal'], $dir);
$router->post('/director/vice-principals/{id}/toggle', [DirectorController::class, 'toggleVicePrincipal'], $dir);
$router->get('/director/teacher-ratings', [DirectorController::class, 'teacherRatings'], $dir);
$router->post('/director/teacher-ratings/weights', [DirectorController::class, 'saveTeacherWeights'], $dir);
$router->get('/director/student-ratings', [DirectorController::class, 'studentRatings'], $dir);
$router->post('/director/student-ratings/weights', [DirectorController::class, 'saveStudentWeights'], $dir);
$router->get('/director/class-comparison', [DirectorController::class, 'classComparison'], $dir);
$router->get('/director/reports', [DirectorController::class, 'reports'], $dir);
$router->get('/director/reports/export-csv', [DirectorController::class, 'exportReportsCsv'], $dir);
$router->get('/director/announcements', [DirectorController::class, 'announcements'], $dir);
$router->post('/director/announcements/create', [DirectorController::class, 'createAnnouncement'], $dir);
$router->get('/director/timetables', [DirectorController::class, 'timetables'], $dir);

// PANEL 3 — Vice Principal
$vp = array_merge($auth, [[RoleMiddleware::class, 'vice_principal']]);
$router->get('/vice-principal/dashboard', [VicePrincipalController::class, 'dashboard'], $vp);
$router->get('/vice-principal/academic', [VicePrincipalController::class, 'academic'], $vp);
$router->post('/vice-principal/academic/years', [VicePrincipalController::class, 'createAcademicYear'], $vp);
$router->post('/vice-principal/academic/semesters', [VicePrincipalController::class, 'createSemester'], $vp);
$router->post('/vice-principal/academic/classes', [VicePrincipalController::class, 'createClass'], $vp);
$router->post('/vice-principal/academic/subjects', [VicePrincipalController::class, 'createSubject'], $vp);
$router->post('/vice-principal/academic/assign-teacher', [VicePrincipalController::class, 'assignTeacher'], $vp);
$router->post('/vice-principal/academic/assign-teacher/{id}/delete', [VicePrincipalController::class, 'removeAssignment'], $vp);
$router->get('/vice-principal/staff', [VicePrincipalController::class, 'staff'], $vp);
$router->post('/vice-principal/staff/create', [VicePrincipalController::class, 'createStaff'], $vp);
$router->post('/vice-principal/staff/{id}/toggle', [VicePrincipalController::class, 'toggleStaffStatus'], $vp);
$router->get('/vice-principal/students', [VicePrincipalController::class, 'students'], $vp);
$router->post('/vice-principal/students/create', [VicePrincipalController::class, 'createStudent'], $vp);
$router->post('/vice-principal/students/{id}/class', [VicePrincipalController::class, 'reassignStudentClass'], $vp);
$router->get('/vice-principal/students/sample-csv', [VicePrincipalController::class, 'sampleStudentsCsv'], $vp);
$router->post('/vice-principal/students/import', [VicePrincipalController::class, 'importStudentsCsv'], $vp);
$router->get('/vice-principal/parents', [VicePrincipalController::class, 'parents'], $vp);
$router->post('/vice-principal/parents/generate-bulk', [VicePrincipalController::class, 'generateBulkParents'], $vp);
$router->get('/vice-principal/parents/export-csv', [VicePrincipalController::class, 'exportGeneratedParentsCsv'], $vp);
$router->get('/vice-principal/parents/print', [VicePrincipalController::class, 'printGeneratedParents'], $vp);
$router->post('/vice-principal/parents/link-sibling', [VicePrincipalController::class, 'linkSibling'], $vp);
$router->get('/vice-principal/timetables', [VicePrincipalController::class, 'timetables'], $vp);
$router->post('/vice-principal/timetables/save', [VicePrincipalController::class, 'saveTimetable'], array_merge($auth, [[RoleMiddleware::class, 'vice_principal|director']]));
$router->get('/vice-principal/attendance', [VicePrincipalController::class, 'attendance'], $vp);
$router->post('/vice-principal/attendance/save', [VicePrincipalController::class, 'saveAttendance'], $vp);
$router->get('/vice-principal/report-cards', [VicePrincipalController::class, 'reportCards'], $vp);
$router->post('/vice-principal/report-cards/generate', [VicePrincipalController::class, 'generateReportCards'], $vp);
$router->post('/vice-principal/report-cards/{id}/comment', [VicePrincipalController::class, 'updateReportCardComment'], $vp);

// PANEL 4 — Educational Counselor
$cns = array_merge($auth, [[RoleMiddleware::class, 'counselor']]);
$router->get('/counselor/dashboard', [CounselorController::class, 'dashboard'], $cns);
$router->post('/counselor/students/assign', [CounselorController::class, 'assignStudent'], $cns);
$router->post('/counselor/requests/{id}/fulfill', [CounselorController::class, 'fulfillRequest'], $cns);
$router->get('/counselor/study-plans', [CounselorController::class, 'studyPlans'], $cns);
$router->post('/counselor/study-plans/create', [CounselorController::class, 'createStudyPlan'], $cns);
$router->post('/counselor/study-plans/{id}/tasks', [CounselorController::class, 'addStudyTask'], $cns);
$router->post('/counselor/study-plans/tasks/{id}/delete', [CounselorController::class, 'deleteStudyTask'], $cns);
$router->get('/counselor/notes', [CounselorController::class, 'notes'], $cns);
$router->post('/counselor/notes/create', [CounselorController::class, 'addNote'], $cns);
$router->get('/counselor/students/{id}', [CounselorController::class, 'studentAnalysis'], $cns);

// PANEL 5 — Teacher
$tch = array_merge($auth, [[RoleMiddleware::class, 'teacher']]);
$router->get('/teacher/dashboard', [TeacherController::class, 'dashboard'], $tch);
$router->get('/teacher/classes', [TeacherController::class, 'classes'], $tch);
$router->get('/teacher/questions', [TeacherController::class, 'questions'], $tch);
$router->post('/teacher/questions/create', [TeacherController::class, 'createQuestion'], $tch);
$router->get('/teacher/exams', [TeacherController::class, 'exams'], $tch);
$router->post('/teacher/exams/create', [TeacherController::class, 'createExam'], $tch);
$router->get('/teacher/exams/{id}', [TeacherController::class, 'examDetail'], $tch);
$router->post('/teacher/exams/{id}/questions', [TeacherController::class, 'addExamQuestion'], $tch);
$router->get('/teacher/exams/attempt/{id}/grade', [TeacherController::class, 'gradeAttemptView'], $tch);
$router->post('/teacher/exams/attempt/{id}/grade', [TeacherController::class, 'submitAttemptGrade'], $tch);
$router->post('/teacher/exams/{id}/offline-scores', [TeacherController::class, 'saveOfflineScores'], $tch);
$router->post('/teacher/exams/{id}/offline-import', [TeacherController::class, 'importOfflineScoresCsv'], $tch);
$router->get('/teacher/daily-tests', [TeacherController::class, 'dailyTests'], $tch);
$router->post('/teacher/daily-tests/create', [TeacherController::class, 'createDailyTest'], $tch);
$router->post('/teacher/daily-tests/{id}/questions', [TeacherController::class, 'addDailyTestQuestion'], $tch);
$router->get('/teacher/homework', [TeacherController::class, 'homework'], $tch);
$router->post('/teacher/homework/create', [TeacherController::class, 'createHomework'], $tch);
$router->post('/teacher/homework/submission/{id}/grade', [TeacherController::class, 'gradeHomework'], $tch);
$router->get('/teacher/classrooms', [TeacherController::class, 'classrooms'], $tch);
$router->post('/teacher/classrooms/create', [TeacherController::class, 'createClassroom'], $tch);
$router->post('/teacher/classrooms/{id}/status', [TeacherController::class, 'updateClassroomStatus'], $tch);
$router->get('/teacher/classrooms/{id}/join', [TeacherController::class, 'joinClassroom'], $tch);
$router->get('/teacher/attendance', [TeacherController::class, 'attendance'], $tch);
$router->post('/teacher/attendance/save', [TeacherController::class, 'saveAttendance'], $tch);

// PANEL 6 — Student
$stu = array_merge($auth, [[RoleMiddleware::class, 'student']]);
$router->get('/student/dashboard', [StudentController::class, 'dashboard'], $stu);
$router->get('/student/schedule', [StudentController::class, 'schedule'], $stu);
$router->get('/student/exams', [StudentController::class, 'exams'], $stu);
$router->get('/student/exams/{id}/take', [StudentController::class, 'takeExam'], $stu);
$router->post('/student/exams/{id}/submit', [StudentController::class, 'submitExam'], $stu);
$router->get('/student/exams/{id}/result', [StudentController::class, 'examResult'], $stu);
$router->get('/student/daily-tests', [StudentController::class, 'dailyTests'], $stu);
$router->get('/student/daily-tests/{id}/take', [StudentController::class, 'takeDailyTest'], $stu);
$router->post('/student/daily-tests/{id}/submit', [StudentController::class, 'submitDailyTest'], $stu);
$router->get('/student/homework', [StudentController::class, 'homework'], $stu);
$router->post('/student/homework/{id}/submit', [StudentController::class, 'submitHomework'], $stu);
$router->get('/student/study-plans', [StudentController::class, 'studyPlans'], $stu);
$router->post('/student/study-plans/request', [StudentController::class, 'requestStudyPlan'], $stu);
$router->post('/api/student/study-tasks/{id}/toggle', [StudentController::class, 'toggleStudyTask'], $stu);
$router->get('/student/classrooms', [StudentController::class, 'classrooms'], $stu);
$router->get('/student/classrooms/{id}/join', [StudentController::class, 'joinClassroom'], $stu);
$router->get('/student/attendance', [StudentController::class, 'attendance'], $stu);
$router->get('/student/report-cards', [StudentController::class, 'reportCards'], $stu);

// PANEL 7 — Parent / Family
$prt = array_merge($auth, [[RoleMiddleware::class, 'parent']]);
$router->get('/parent/dashboard', [ParentController::class, 'dashboard'], $prt);
$router->get('/parent/exams', [ParentController::class, 'exams'], $prt);
$router->get('/parent/homework', [ParentController::class, 'homework'], $prt);
$router->get('/parent/study-plans', [ParentController::class, 'studyPlans'], $prt);
$router->get('/parent/attendance', [ParentController::class, 'attendance'], $prt);
$router->get('/parent/report-cards', [ParentController::class, 'reportCards'], $prt);
$router->get('/parent/timetables', [ParentController::class, 'timetables'], $prt);

$app = new App($router);
$app->run();
