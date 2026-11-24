<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$tests = $tests ?? [];
$history = $history ?? [];
$subjectStats = $subjectStats ?? [];

$chartPayload = array_map(static fn ($s) => [
    'label' => $s['subject_name'],
    'value' => (float) $s['avg_score'],
], $subjectStats);
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.student'), 'url' => '/student/dashboard'], ['label' => Translator::get('menu.daily_tests')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.daily_tests')) ?></h1></div>
</div>

<div class="grid grid-2" style="margin-bottom:20px;align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('target', 17) ?><span><?= View::e(Translator::get('student.available_daily_tests')) ?></span></h2>
        <?php if (empty($tests)): ?>
            <?= Ui::emptyState(Translator::get('common.no_data'), 'target') ?>
        <?php else: foreach ($tests as $t): ?>
            <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;gap:10px;">
                <div>
                    <strong><?= View::e($t['title']) ?></strong>
                    <div style="font-size:0.84rem;color:var(--text-muted);"><?= View::e($t['subject_name']) ?> · <?= View::e(DateHelper::number($t['q_count'])) ?> <?= View::e(Translator::get('student.questions_count')) ?></div>
                </div>
                <a href="<?= View::e(View::url('/student/daily-tests/' . $t['id'] . '/take')) ?>" class="btn btn-primary btn-sm"><?= View::e(Translator::get('student.start_practice')) ?></a>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('bar-chart', 17) ?><span><?= View::e(Translator::get('student.subject_accuracy_chart')) ?></span></h2>
        <?php if (empty($chartPayload)): ?>
            <?= Ui::emptyState(Translator::get('student.no_attempts'), 'bar-chart') ?>
        <?php else: ?>
            <div class="chart-box" data-bar-chart="<?= View::e(json_encode($chartPayload, JSON_UNESCAPED_UNICODE)) ?>"></div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('student.practice_history')) ?></h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.title')) ?></th>
                    <th><?= View::e(Translator::get('common.subject')) ?></th>
                    <th><?= View::e(Translator::get('parent.correct_answers')) ?></th>
                    <th><?= View::e(Translator::get('common.score')) ?></th>
                    <th><?= View::e(Translator::get('student.time_spent')) ?></th>
                    <th><?= View::e(Translator::get('common.date')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr><td colspan="6"><?= View::e(Translator::get('common.no_data')) ?></td></tr>
                <?php else: foreach ($history as $h): ?>
                    <tr>
                        <td><?= View::e($h['title']) ?></td>
                        <td><?= View::e($h['subject_name']) ?></td>
                        <td><?= View::e(DateHelper::number($h['correct_count'])) ?> / <?= View::e(DateHelper::number($h['total_questions'])) ?></td>
                        <td><span class="badge badge-success"><?= View::e(DateHelper::percent($h['score_percentage'])) ?></span></td>
                        <td><?= View::e(DateHelper::number($h['time_spent_seconds'])) ?> <?= View::e(Translator::get('common.seconds')) ?></td>
                        <td><?= View::e(DateHelper::format($h['completed_at'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
