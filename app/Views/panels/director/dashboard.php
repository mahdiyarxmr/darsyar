<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$stats = $stats ?? [];
$classStats = $classStats ?? [];
$topTeachers = $topTeachers ?? [];
$topStudents = $topStudents ?? [];

$chartPayload = array_map(static fn ($c) => [
    'label' => $c['class_name'],
    'value' => (float) $c['avg_exam_pct'],
], $classStats);
?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('panel.director')) ?></h1></div>
    <a href="<?= View::e(View::url('/director/reports')) ?>" class="btn btn-secondary btn-sm">
        <?= Icon::svg('file-text', 15) ?>
        <span><?= View::e(Translator::get('menu.reports')) ?></span>
    </a>
</div>

<div class="grid grid-3" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('admin.enrolled_students')) ?></span><span class="stat-icon"><?= Icon::svg('graduation-cap', 16) ?></span></div>
        <span class="stat-value"><?= View::e(DateHelper::number($stats['students'] ?? 0)) ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('admin.active_teachers')) ?></span><span class="stat-icon"><?= Icon::svg('user-check', 16) ?></span></div>
        <span class="stat-value"><?= View::e(DateHelper::number($stats['teachers'] ?? 0)) ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('director.school_exam_avg')) ?></span><span class="stat-icon"><?= Icon::svg('bar-chart', 16) ?></span></div>
        <span class="stat-value"><?= View::e(DateHelper::percent($stats['avg_exam_pct'] ?? 0)) ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('director.total_exams')) ?></span><span class="stat-icon"><?= Icon::svg('file-text', 16) ?></span></div>
        <span class="stat-value"><?= View::e(DateHelper::number($stats['exams_total'] ?? 0)) ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('director.homework_subs')) ?></span><span class="stat-icon"><?= Icon::svg('book-open', 16) ?></span></div>
        <span class="stat-value"><?= View::e(DateHelper::number($stats['homework_subs'] ?? 0)) ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('director.daily_attempts')) ?></span><span class="stat-icon"><?= Icon::svg('target', 16) ?></span></div>
        <span class="stat-value"><?= View::e(DateHelper::number($stats['daily_test_attempts'] ?? 0)) ?></span>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h2 class="card-title"><?= Icon::svg('bar-chart', 17) ?><span><?= View::e(Translator::get('director.class_avg_chart')) ?></span></h2>
        <a href="<?= View::e(View::url('/director/class-comparison')) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.view')) ?></a>
    </div>
    <?php if (empty($chartPayload)): ?>
        <?= Ui::emptyState(Translator::get('common.no_data'), 'bar-chart') ?>
    <?php else: ?>
        <div class="chart-box" data-bar-chart="<?= View::e(json_encode($chartPayload, JSON_UNESCAPED_UNICODE)) ?>"></div>
    <?php endif; ?>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('award', 17) ?><span><?= View::e(Translator::get('menu.teacher_ratings')) ?></span></h2>
            <a href="<?= View::e(View::url('/director/teacher-ratings')) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.view')) ?></a>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= View::e(Translator::get('common.teacher')) ?></th><th><?= View::e(Translator::get('common.score')) ?></th><th><?= View::e(Translator::get('report_card.remarks')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($topTeachers as $t): ?>
                        <tr>
                            <td><?= View::e($t['full_name']) ?></td>
                            <td><strong><?= View::e(DateHelper::number($t['total_score'])) ?></strong></td>
                            <td><span class="badge badge-primary"><?= View::e(Ui::ratingLabel($t['category'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('graduation-cap', 17) ?><span><?= View::e(Translator::get('menu.student_ratings')) ?></span></h2>
            <a href="<?= View::e(View::url('/director/student-ratings')) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.view')) ?></a>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= View::e(Translator::get('common.student')) ?></th><th><?= View::e(Translator::get('common.class')) ?></th><th><?= View::e(Translator::get('common.score')) ?></th><th><?= View::e(Translator::get('report_card.remarks')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($topStudents as $s): ?>
                        <tr>
                            <td><?= View::e($s['full_name']) ?></td>
                            <td><?= View::e($s['class_name']) ?></td>
                            <td><strong><?= View::e(DateHelper::number($s['total_score'])) ?></strong></td>
                            <td><span class="badge badge-success"><?= View::e(Ui::ratingLabel($s['category'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
