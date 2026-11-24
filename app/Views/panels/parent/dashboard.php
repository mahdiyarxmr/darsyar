<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$children = $children ?? [];
$activeChild = $activeChild ?? null;
$rating = $rating ?? [];
$announcements = $announcements ?? [];
$onlineParticipation = $onlineParticipation ?? [];
$counselorNotes = $counselorNotes ?? [];

$overviewChart = $activeChild ? [
    ['label' => Translator::get('student.exam_avg'), 'value' => (float) ($rating['academic_score'] ?? 0)],
    ['label' => Translator::get('director.hw_completion'), 'value' => (float) ($rating['homework_score'] ?? 0)],
    ['label' => Translator::get('student.study_plan_progress'), 'value' => (float) ($rating['study_plan_score'] ?? 0)],
    ['label' => Translator::get('student.attendance_rate'), 'value' => (float) ($rating['attendance_score'] ?? 100)],
] : [];
?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('panel.parent')) ?></h1></div>
</div>

<?= View::partial('parent_child_switcher', ['children' => $children, 'activeChild' => $activeChild, 'basePath' => '/parent/dashboard']) ?>

<?php if (!$activeChild): ?>
    <div class="card"><?= Ui::emptyState(Translator::get('parent.no_child_linked'), 'users') ?></div>
<?php else: ?>
    <div class="grid grid-4" style="margin-bottom:20px;">
        <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('parent.overall_rating')) ?></span><span class="stat-icon"><?= Icon::svg('award', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::number($rating['total_score'] ?? 0)) ?></span><span class="stat-meta"><?= View::e(Ui::ratingLabel($rating['category'] ?? null)) ?></span></div>
        <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('student.exam_avg')) ?></span><span class="stat-icon"><?= Icon::svg('file-text', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::percent($rating['academic_score'] ?? 0)) ?></span></div>
        <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('student.study_plan_progress')) ?></span><span class="stat-icon"><?= Icon::svg('target', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::percent($rating['study_plan_score'] ?? 0)) ?></span></div>
        <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('student.attendance_rate')) ?></span><span class="stat-icon"><?= Icon::svg('check-circle', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::percent($rating['attendance_score'] ?? 100)) ?></span></div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('bar-chart', 17) ?><span><?= View::e(Translator::get('menu.my_progress')) ?></span></h2>
        <div class="chart-box" data-bar-chart="<?= View::e(json_encode($overviewChart, JSON_UNESCAPED_UNICODE)) ?>"></div>
    </div>

    <div class="grid grid-2" style="margin-bottom:20px;">
        <div class="card">
            <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('video', 17) ?><span><?= View::e(Translator::get('parent.online_participation')) ?></span></h2>
            <?php if (empty($onlineParticipation)): ?>
                <?= Ui::emptyState(Translator::get('common.no_data'), 'video') ?>
            <?php else: foreach ($onlineParticipation as $op): ?>
                <div style="padding:8px 0;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;">
                    <div>
                        <strong><?= View::e($op['title']) ?></strong>
                        <div style="font-size:0.83rem;color:var(--text-muted);"><?= View::e($op['subject_name']) ?> · <?= View::e(DateHelper::format($op['scheduled_start'])) ?></div>
                    </div>
                    <span class="badge badge-success"><?= View::e(Ui::statusLabel($op['status'])) ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="card">
            <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('megaphone', 17) ?><span><?= View::e(Translator::get('parent.school_announcements')) ?></span></h2>
            <?php if (empty($announcements)): ?>
                <?= Ui::emptyState(Translator::get('common.no_data'), 'megaphone') ?>
            <?php else: foreach ($announcements as $a): ?>
                <div style="padding:10px 0;border-bottom:1px solid var(--border);">
                    <strong><?= View::e($a['title']) ?></strong>
                    <div style="font-size:0.85rem;color:var(--text-muted);"><?= View::e($a['body']) ?></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <?php if (!empty($counselorNotes)): ?>
        <div class="card">
            <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('notebook', 17) ?><span><?= View::e(Translator::get('parent.counselor_notes_title')) ?></span></h2>
            <?php foreach ($counselorNotes as $cn): ?>
                <div style="padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:8px;">
                    <strong><?= View::e($cn['title']) ?></strong> <small style="color:var(--text-muted);">(<?= View::e($cn['counselor_name']) ?>)</small>
                    <p style="margin:4px 0;font-size:0.87rem;color:var(--text-muted);"><?= View::e($cn['note']) ?></p>
                    <small style="color:var(--text-muted);"><?= View::e(DateHelper::format($cn['created_at'], false)) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
