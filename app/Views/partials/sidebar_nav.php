<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\View;

$role = $role ?? '';
$currentPath = $currentPath ?? '/';
$unreadCount = $unreadCount ?? 0;

$menus = [
    'super_admin' => [
        ['label' => 'menu.overview', 'url' => '/admin/dashboard', 'icon' => 'layout-dashboard'],
        ['label' => 'menu.schools', 'url' => '/admin/schools', 'icon' => 'building'],
        ['label' => 'menu.admins', 'url' => '/admin/admins', 'icon' => 'users'],
        ['label' => 'menu.cms', 'url' => '/admin/cms', 'icon' => 'layers'],
        ['label' => 'menu.announcements', 'url' => '/admin/announcements', 'icon' => 'megaphone'],
        ['label' => 'menu.events', 'url' => '/admin/events', 'icon' => 'calendar'],
        ['label' => 'menu.settings', 'url' => '/admin/settings', 'icon' => 'settings'],
        ['label' => 'menu.audit_logs', 'url' => '/admin/audit-logs', 'icon' => 'shield-check'],
        ['label' => 'menu.health', 'url' => '/admin/health', 'icon' => 'activity'],
    ],
    'director' => [
        ['label' => 'menu.overview', 'url' => '/director/dashboard', 'icon' => 'layout-dashboard'],
        ['label' => 'menu.vice_principals', 'url' => '/director/vice-principals', 'icon' => 'users'],
        ['label' => 'menu.teacher_ratings', 'url' => '/director/teacher-ratings', 'icon' => 'award'],
        ['label' => 'menu.student_ratings', 'url' => '/director/student-ratings', 'icon' => 'graduation-cap'],
        ['label' => 'menu.class_comparison', 'url' => '/director/class-comparison', 'icon' => 'bar-chart'],
        ['label' => 'menu.reports', 'url' => '/director/reports', 'icon' => 'file-text'],
        ['label' => 'menu.announcements', 'url' => '/director/announcements', 'icon' => 'megaphone'],
        ['label' => 'menu.timetables', 'url' => '/director/timetables', 'icon' => 'calendar'],
    ],
    'vice_principal' => [
        ['label' => 'menu.overview', 'url' => '/vice-principal/dashboard', 'icon' => 'layout-dashboard'],
        ['label' => 'menu.academic', 'url' => '/vice-principal/academic', 'icon' => 'layers'],
        ['label' => 'menu.users_staff', 'url' => '/vice-principal/staff', 'icon' => 'user-check'],
        ['label' => 'menu.students_bulk', 'url' => '/vice-principal/students', 'icon' => 'graduation-cap'],
        ['label' => 'menu.parents_bulk', 'url' => '/vice-principal/parents', 'icon' => 'users'],
        ['label' => 'menu.timetables', 'url' => '/vice-principal/timetables', 'icon' => 'calendar'],
        ['label' => 'menu.attendance', 'url' => '/vice-principal/attendance', 'icon' => 'clipboard-check'],
        ['label' => 'menu.report_cards', 'url' => '/vice-principal/report-cards', 'icon' => 'file-text'],
    ],
    'counselor' => [
        ['label' => 'menu.overview', 'url' => '/counselor/dashboard', 'icon' => 'layout-dashboard'],
        ['label' => 'menu.study_plans', 'url' => '/counselor/study-plans', 'icon' => 'target'],
        ['label' => 'menu.counseling_notes', 'url' => '/counselor/notes', 'icon' => 'notebook'],
    ],
    'teacher' => [
        ['label' => 'menu.overview', 'url' => '/teacher/dashboard', 'icon' => 'layout-dashboard'],
        ['label' => 'menu.my_classes', 'url' => '/teacher/classes', 'icon' => 'users'],
        ['label' => 'menu.questions', 'url' => '/teacher/questions', 'icon' => 'help-circle'],
        ['label' => 'menu.exams', 'url' => '/teacher/exams', 'icon' => 'file-text'],
        ['label' => 'menu.daily_tests', 'url' => '/teacher/daily-tests', 'icon' => 'target'],
        ['label' => 'menu.homework', 'url' => '/teacher/homework', 'icon' => 'book-open'],
        ['label' => 'menu.classrooms', 'url' => '/teacher/classrooms', 'icon' => 'video'],
        ['label' => 'menu.attendance', 'url' => '/teacher/attendance', 'icon' => 'clipboard-check'],
        ['label' => 'nav.discussions', 'url' => '/discussions', 'icon' => 'message-square'],
    ],
    'student' => [
        ['label' => 'menu.overview', 'url' => '/student/dashboard', 'icon' => 'layout-dashboard'],
        ['label' => 'menu.schedule', 'url' => '/student/schedule', 'icon' => 'calendar'],
        ['label' => 'menu.exams', 'url' => '/student/exams', 'icon' => 'file-text'],
        ['label' => 'menu.daily_tests', 'url' => '/student/daily-tests', 'icon' => 'target'],
        ['label' => 'menu.homework', 'url' => '/student/homework', 'icon' => 'book-open'],
        ['label' => 'menu.study_plans', 'url' => '/student/study-plans', 'icon' => 'clipboard-check'],
        ['label' => 'menu.classrooms', 'url' => '/student/classrooms', 'icon' => 'video'],
        ['label' => 'menu.attendance', 'url' => '/student/attendance', 'icon' => 'check-circle'],
        ['label' => 'menu.report_cards', 'url' => '/student/report-cards', 'icon' => 'award'],
        ['label' => 'nav.discussions', 'url' => '/discussions', 'icon' => 'message-square'],
    ],
    'parent' => [
        ['label' => 'menu.overview', 'url' => '/parent/dashboard', 'icon' => 'layout-dashboard'],
        ['label' => 'menu.exams', 'url' => '/parent/exams', 'icon' => 'file-text'],
        ['label' => 'menu.homework', 'url' => '/parent/homework', 'icon' => 'book-open'],
        ['label' => 'menu.study_plans', 'url' => '/parent/study-plans', 'icon' => 'target'],
        ['label' => 'menu.attendance', 'url' => '/parent/attendance', 'icon' => 'clipboard-check'],
        ['label' => 'menu.report_cards', 'url' => '/parent/report-cards', 'icon' => 'award'],
        ['label' => 'menu.timetables', 'url' => '/parent/timetables', 'icon' => 'calendar'],
    ],
];

$items = $menus[$role] ?? [];
?>
<?php foreach ($items as $item): ?>
    <?php $active = str_starts_with($currentPath, $item['url']); ?>
    <a href="<?= View::e(View::url($item['url'])) ?>" class="nav-item <?= $active ? 'active' : '' ?>">
        <?= Icon::svg($item['icon'], 17) ?>
        <span><?= View::e(Translator::get($item['label'])) ?></span>
    </a>
<?php endforeach; ?>

<hr style="border:none;border-top:1px solid var(--border-subtle);margin:10px 6px;">
<a href="<?= View::e(View::url('/notifications')) ?>" class="nav-item <?= str_starts_with($currentPath, '/notifications') ? 'active' : '' ?>">
    <?= Icon::svg('bell', 17) ?>
    <span><?= View::e(Translator::get('nav.notifications')) ?></span>
    <?php if ($unreadCount > 0): ?>
        <span class="badge badge-primary" style="margin-inline-start:auto;"><?= View::e(DateHelper::number($unreadCount)) ?></span>
    <?php endif; ?>
</a>
<a href="<?= View::e(View::url('/account/password')) ?>" class="nav-item <?= str_starts_with($currentPath, '/account/password') ? 'active' : '' ?>">
    <?= Icon::svg('key-round', 17) ?>
    <span><?= View::e(Translator::get('nav.password')) ?></span>
</a>
