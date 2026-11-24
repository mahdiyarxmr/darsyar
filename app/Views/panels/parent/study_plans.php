<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$children = $children ?? [];
$activeChild = $activeChild ?? null;
$plans = $plans ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.parent'), 'url' => '/parent/dashboard'], ['label' => Translator::get('menu.study_plans')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.study_plans')) ?></h1></div>
</div>

<?= View::partial('parent_child_switcher', ['children' => $children, 'activeChild' => $activeChild, 'basePath' => '/parent/study-plans']) ?>

<?php if (empty($plans)): ?>
    <div class="card"><?= Ui::emptyState(Translator::get('common.no_data'), 'target') ?></div>
<?php else: foreach ($plans as $plan): $prog = $plan['progress']; ?>
    <div class="card" style="margin-bottom:18px;">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= Icon::svg('target', 17) ?><span><?= View::e($plan['title']) ?></span></h2>
                <small style="color:var(--text-muted);"><?= View::e(Translator::get('roles.counselor')) ?>: <?= View::e($plan['counselor_name']) ?> · <?= View::e(DateHelper::format($plan['week_start_date'], false)) ?> — <?= View::e(DateHelper::format($plan['week_end_date'], false)) ?></small>
            </div>
            <div style="display:flex;gap:6px;align-items:center;">
                <span class="badge badge-muted"><?= View::e(DateHelper::number($prog['completed'])) ?> / <?= View::e(DateHelper::number($prog['total'])) ?></span>
                <span class="badge badge-primary"><?= View::e(DateHelper::percent($prog['percentage'])) ?></span>
            </div>
        </div>
        <div class="progress" style="margin-bottom:14px;">
            <div class="progress-bar" style="width:<?= View::e((string) $prog['percentage']) ?>%;"></div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= View::e(Translator::get('counselor.day_of_week')) ?></th>
                        <th><?= View::e(Translator::get('common.subject')) ?></th>
                        <th><?= View::e(Translator::get('counselor.task_title')) ?></th>
                        <th><?= View::e(Translator::get('counselor.start_time')) ?></th>
                        <th><?= View::e(Translator::get('common.minutes')) ?></th>
                        <th><?= View::e(Translator::get('common.status')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($plan['tasks'])): ?>
                        <tr><td colspan="6"><?= View::e(Translator::get('common.no_data')) ?></td></tr>
                    <?php else: foreach ($plan['tasks'] as $t): ?>
                        <tr>
                            <td><?= View::e(Ui::weekdayLabel((string) $t['day_of_week'])) ?></td>
                            <td><?= View::e($t['subject_name'] ?? '-') ?></td>
                            <td><?= View::e($t['task_title']) ?></td>
                            <td><?= View::e(DateHelper::localizeDigits(($t['start_time'] ?? '') . (!empty($t['end_time']) ? ' - ' . $t['end_time'] : ''))) ?: '-' ?></td>
                            <td><?= View::e(DateHelper::number($t['duration_minutes'])) ?> <?= View::e(Translator::get('common.minutes')) ?></td>
                            <td><span class="badge <?= (int) $t['is_completed'] === 1 ? 'badge-success' : 'badge-warning' ?>"><?= View::e(Ui::statusLabel((int) $t['is_completed'] === 1 ? 'completed' : 'pending')) ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; endif; ?>
