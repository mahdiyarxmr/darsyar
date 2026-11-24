<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$plans = $plans ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.student'), 'url' => '/student/dashboard'], ['label' => Translator::get('menu.study_plans')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.study_plans')) ?></h1></div>
</div>

<div class="card" style="margin-bottom:18px;">
    <h2 class="card-title" style="margin-bottom:10px;"><?= Icon::svg('target', 17) ?><span><?= View::e(Translator::get('student.request_study_plan')) ?></span></h2>
    <form method="POST" action="<?= View::e(View::url('/student/study-plans/request')) ?>" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <?= Csrf::field() ?>
        <input class="form-control" name="note" style="flex:1;min-width:240px;" placeholder="<?= View::e(Translator::get('student.request_note')) ?>">
        <button type="submit" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.submit')) ?></button>
    </form>
</div>

<?php if (empty($plans)): ?>
    <div class="card"><?= Ui::emptyState(Translator::get('common.no_data'), 'target') ?></div>
<?php else: foreach ($plans as $plan): $prog = $plan['progress']; ?>
    <div class="card" style="margin-bottom:18px;">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= View::e($plan['title']) ?></h2>
                <small style="color:var(--text-muted);"><?= View::e(Translator::get('roles.counselor')) ?>: <?= View::e($plan['counselor_name']) ?> · <?= View::e(DateHelper::format($plan['week_start_date'], false)) ?> — <?= View::e(DateHelper::format($plan['week_end_date'], false)) ?></small>
            </div>
            <div style="display:flex;gap:6px;align-items:center;">
                <span id="planCountsLabel" data-plan-counts class="badge badge-muted"><?= View::e(DateHelper::number($prog['completed'])) ?> / <?= View::e(DateHelper::number($prog['total'])) ?></span>
                <span id="planCompletionPct" data-plan-pct class="badge badge-primary"><?= View::e(DateHelper::percent($prog['percentage'])) ?></span>
            </div>
        </div>
        <div class="progress" style="margin-bottom:14px;">
            <div id="planCompletionBar" data-plan-bar class="progress-bar" style="width:<?= View::e((string) $prog['percentage']) ?>%;"></div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= View::e(Translator::get('student.task_done')) ?></th>
                        <th><?= View::e(Translator::get('counselor.day_of_week')) ?></th>
                        <th><?= View::e(Translator::get('common.subject')) ?></th>
                        <th><?= View::e(Translator::get('counselor.task_title')) ?></th>
                        <th><?= View::e(Translator::get('counselor.start_time')) ?></th>
                        <th><?= View::e(Translator::get('common.minutes')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plan['tasks'] as $t): ?>
                        <tr>
                            <td>
                                <input type="checkbox" data-toggle-task="<?= View::e(View::url('/api/student/study-tasks/' . $t['id'] . '/toggle')) ?>" <?= (int) $t['is_completed'] === 1 ? 'checked' : '' ?> aria-label="<?= View::e($t['task_title']) ?>">
                            </td>
                            <td><?= View::e(Ui::weekdayLabel((string) $t['day_of_week'])) ?></td>
                            <td><?= View::e($t['subject_name'] ?? '-') ?></td>
                            <td><?= View::e($t['task_title']) ?></td>
                            <td><?= View::e(DateHelper::localizeDigits(($t['start_time'] ?? '') . ($t['end_time'] ? ' - ' . $t['end_time'] : ''))) ?></td>
                            <td><?= View::e(DateHelper::number($t['duration_minutes'])) ?> <?= View::e(Translator::get('common.minutes')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; endif; ?>
