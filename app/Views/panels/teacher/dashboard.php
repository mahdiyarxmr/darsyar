<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$assignments = $assignments ?? [];
$stats = $stats ?? [];
$rankings = $rankings ?? [];

$chartData = array_map(static fn ($r) => [
    'label' => $r['full_name'],
    'value' => (float) $r['total_score'],
], array_slice($rankings, 0, 8));
?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('panel.teacher')) ?></h1></div>
    <a href="<?= View::e(View::url('/teacher/classes')) ?>" class="btn btn-secondary btn-sm"><?= Icon::svg('users', 15) ?><span><?= View::e(Translator::get('menu.my_classes')) ?></span></a>
</div>

<div class="grid grid-4" style="margin-bottom:20px;">
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('teacher.assigned_classes')) ?></span><span class="stat-icon"><?= Icon::svg('layers', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::number($stats['assigned_classes'] ?? 0)) ?></span></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('teacher.exams_count')) ?></span><span class="stat-icon"><?= Icon::svg('file-text', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::number($stats['exams_count'] ?? 0)) ?></span></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('teacher.homework_count')) ?></span><span class="stat-icon"><?= Icon::svg('book-open', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::number($stats['homework_count'] ?? 0)) ?></span></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('teacher.pending_grading')) ?></span><span class="stat-icon"><?= Icon::svg('clock', 16) ?></span></div><span class="stat-value" style="color:var(--warning);"><?= View::e(DateHelper::number($stats['pending_grading'] ?? 0)) ?></span></div>
</div>

<?php if (!empty($chartData)): ?>
    <div class="card" style="margin-bottom:20px;">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('bar-chart', 17) ?><span><?= View::e(Translator::get('teacher.student_rankings')) ?></span></h2>
        <div class="chart-box" data-bar-chart="<?= View::e(json_encode($chartData, JSON_UNESCAPED_UNICODE)) ?>"></div>
    </div>
<?php endif; ?>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('teacher.assigned_classes_table')) ?></h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= View::e(Translator::get('common.class')) ?></th><th><?= View::e(Translator::get('common.grade')) ?></th><th><?= View::e(Translator::get('common.subject')) ?></th><th><?= View::e(Translator::get('common.code')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td><span class="badge badge-primary"><?= View::e($a['class_name']) ?></span></td>
                            <td><?= View::e($a['grade_name']) ?></td>
                            <td><?= View::e($a['subject_name']) ?></td>
                            <td><code><?= View::e($a['subject_code']) ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('teacher.student_rankings')) ?></h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= View::e(Translator::get('common.rank')) ?></th><th><?= View::e(Translator::get('common.student')) ?></th><th><?= View::e(Translator::get('menu.exams')) ?></th><th><?= View::e(Translator::get('menu.homework')) ?></th><th><?= View::e(Translator::get('common.score')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($rankings as $idx => $r): ?>
                        <tr>
                            <td><?= View::e(DateHelper::number($idx + 1)) ?></td>
                            <td><?= View::e($r['full_name']) ?></td>
                            <td><?= View::e(DateHelper::percent($r['academic_score'])) ?></td>
                            <td><?= View::e(DateHelper::percent($r['homework_score'])) ?></td>
                            <td><strong><?= View::e(DateHelper::number($r['total_score'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
