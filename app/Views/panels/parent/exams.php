<?php

use App\Core\DateHelper;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$children = $children ?? [];
$activeChild = $activeChild ?? null;
$examResults = $examResults ?? [];
$dailyStats = $dailyStats ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.parent'), 'url' => '/parent/dashboard'], ['label' => Translator::get('menu.exams')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.exams')) ?></h1></div>
</div>

<?= View::partial('parent_child_switcher', ['children' => $children, 'activeChild' => $activeChild, 'basePath' => '/parent/exams']) ?>

<div class="card" style="margin-bottom:20px;">
    <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('parent.exam_scores_title')) ?></h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('teacher.exam_title')) ?></th>
                    <th><?= View::e(Translator::get('teacher.exam_mode')) ?></th>
                    <th><?= View::e(Translator::get('common.subject')) ?></th>
                    <th><?= View::e(Translator::get('common.score')) ?></th>
                    <th><?= View::e(Translator::get('common.percentage')) ?></th>
                    <th><?= View::e(Translator::get('common.notes')) ?></th>
                    <th><?= View::e(Translator::get('common.date')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($examResults)): ?>
                    <tr><td colspan="7"><?= View::e(Translator::get('common.no_data')) ?></td></tr>
                <?php else: foreach ($examResults as $r): ?>
                    <tr>
                        <td><strong><?= View::e($r['exam_title']) ?></strong></td>
                        <td><span class="badge badge-primary"><?= View::e(Ui::statusLabel($r['exam_mode'])) ?></span></td>
                        <td><?= View::e($r['subject_name']) ?></td>
                        <td><?= View::e(DateHelper::number($r['score'])) ?> / <?= View::e(DateHelper::number($r['max_score'])) ?></td>
                        <td><span class="badge badge-success"><?= View::e(DateHelper::percent($r['percentage'])) ?></span></td>
                        <td><?= View::e($r['remarks'] ?? '-') ?></td>
                        <td><?= View::e(DateHelper::format($r['published_at'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('parent.daily_stats_title')) ?></h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.subject')) ?></th>
                    <th><?= View::e(Translator::get('parent.tests_completed')) ?></th>
                    <th><?= View::e(Translator::get('report_card.final_average')) ?></th>
                    <th><?= View::e(Translator::get('parent.correct_answers')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dailyStats)): ?>
                    <tr><td colspan="4"><?= View::e(Translator::get('common.no_data')) ?></td></tr>
                <?php else: foreach ($dailyStats as $d): ?>
                    <tr>
                        <td><?= View::e($d['subject_name']) ?></td>
                        <td><?= View::e(DateHelper::number($d['attempts_count'])) ?></td>
                        <td><strong><?= View::e(DateHelper::percent($d['avg_score'])) ?></strong></td>
                        <td><?= View::e(DateHelper::number($d['total_correct'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
