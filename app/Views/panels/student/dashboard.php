<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$rating = $rating ?? [];
$upcomingExams = $upcomingExams ?? [];
$recentResults = $recentResults ?? [];
$dailyStats = $dailyStats ?? [];

$chartPayload = array_map(static fn ($d) => [
    'label' => $d['subject_name'],
    'value' => (float) $d['avg_score'],
], $dailyStats);
?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('panel.student')) ?></h1></div>
    <div class="page-header-actions">
        <span class="badge badge-muted"><?= View::e($rating['class_name'] ?? '-') ?></span>
        <span class="badge badge-primary"><?= View::e(Ui::ratingLabel($rating['category'] ?? null)) ?></span>
    </div>
</div>

<div class="grid grid-4" style="margin-bottom:20px;">
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('student.overall_score')) ?></span><span class="stat-icon"><?= Icon::svg('award', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::number($rating['total_score'] ?? 0)) ?></span></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('student.exam_avg')) ?></span><span class="stat-icon"><?= Icon::svg('file-text', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::percent($rating['academic_score'] ?? 0)) ?></span></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('student.study_plan_progress')) ?></span><span class="stat-icon"><?= Icon::svg('target', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::percent($rating['study_plan_score'] ?? 0)) ?></span></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('student.attendance_rate')) ?></span><span class="stat-icon"><?= Icon::svg('check-circle', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::percent($rating['attendance_score'] ?? 100)) ?></span></div>
</div>

<div class="grid grid-2" style="margin-bottom:20px;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('file-text', 17) ?><span><?= View::e(Translator::get('menu.exams')) ?></span></h2>
            <a href="<?= View::e(View::url('/student/exams')) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.view')) ?></a>
        </div>
        <?php if (empty($upcomingExams)): ?>
            <?= Ui::emptyState(Translator::get('common.no_data'), 'file-text') ?>
        <?php else: ?>
            <?php foreach ($upcomingExams as $e): ?>
                <div style="padding:10px 0;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <strong><?= View::e($e['title']) ?></strong>
                        <div style="font-size:0.83rem;color:var(--text-muted);"><?= View::e($e['subject_name']) ?> · <?= View::e(DateHelper::format($e['starts_at'])) ?></div>
                    </div>
                    <span class="badge badge-primary"><?= View::e(Ui::statusLabel($e['exam_mode'])) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('bar-chart', 17) ?><span><?= View::e(Translator::get('student.daily_practice_chart')) ?></span></h2>
            <a href="<?= View::e(View::url('/student/daily-tests')) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('student.practice_btn')) ?></a>
        </div>
        <?php if (empty($chartPayload)): ?>
            <?= Ui::emptyState(Translator::get('student.practice_empty'), 'target') ?>
        <?php else: ?>
            <div class="chart-box" data-bar-chart="<?= View::e(json_encode($chartPayload, JSON_UNESCAPED_UNICODE)) ?>"></div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('student.latest_results')) ?></h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('teacher.exam_title')) ?></th>
                    <th><?= View::e(Translator::get('common.subject')) ?></th>
                    <th><?= View::e(Translator::get('common.score')) ?></th>
                    <th><?= View::e(Translator::get('common.percentage')) ?></th>
                    <th><?= View::e(Translator::get('common.date')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentResults)): ?>
                    <tr><td colspan="5"><?= View::e(Translator::get('common.no_data')) ?></td></tr>
                <?php else: foreach ($recentResults as $r): ?>
                    <tr>
                        <td><?= View::e($r['exam_title']) ?></td>
                        <td><?= View::e($r['subject_name']) ?></td>
                        <td><?= View::e(DateHelper::number($r['score'])) ?> / <?= View::e(DateHelper::number($r['max_score'])) ?></td>
                        <td><span class="badge badge-success"><?= View::e(DateHelper::percent($r['percentage'])) ?></span></td>
                        <td><?= View::e(DateHelper::format($r['published_at'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
