<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$students = $students ?? [];
$subjects = $subjects ?? [];
$plans = $plans ?? [];
$studentFilter = $studentFilter ?? 0;
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.counselor'), 'url' => '/counselor/dashboard'], ['label' => Translator::get('menu.study_plans')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.study_plans')) ?></h1></div>
    <button type="button" class="btn btn-primary btn-sm" data-drawer-toggle="planCreateDrawer" aria-expanded="false">
        <?= Icon::svg('plus', 15) ?>
        <span><?= View::e(Translator::get('counselor.create_plan')) ?></span>
    </button>
</div>

<div id="planCreateDrawer" class="action-drawer">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('target', 17) ?><span><?= View::e(Translator::get('counselor.create_plan')) ?></span></h2>
            <button type="button" class="btn btn-ghost btn-sm btn-icon" data-drawer-toggle="planCreateDrawer" aria-label="<?= View::e(Translator::get('common.close')) ?>"><?= Icon::svg('x', 16) ?></button>
        </div>
        <form method="POST" action="<?= View::e(View::url('/counselor/study-plans/create')) ?>">
            <?= Csrf::field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('common.student')) ?></label>
                    <select class="form-control" name="student_id" required>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= View::e((string) $s['id']) ?>" <?= (int) $s['id'] === $studentFilter ? 'selected' : '' ?>>
                                <?= View::e($s['full_name']) ?> (<?= View::e($s['student_code']) ?> - <?= View::e($s['class_name'] ?? '-') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('counselor.plan_title')) ?></label><input class="form-control" name="title" required></div>
                <div class="form-group date-picker-wrap"><label class="form-label"><?= View::e(Translator::get('counselor.week_start')) ?></label><input class="form-control" type="date" name="week_start_date" value="<?= date('Y-m-d') ?>" required></div>
                <div class="form-group date-picker-wrap"><label class="form-label"><?= View::e(Translator::get('counselor.week_end')) ?></label><input class="form-control" type="date" name="week_end_date" value="<?= date('Y-m-d', strtotime('+6 days')) ?>" required></div>
            </div>
            <div class="form-row" style="align-items:end;">
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('counselor.weekly_goal')) ?></label><input class="form-control" name="weekly_goal"></div>
                <div class="form-group"><button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.create')) ?></button></div>
            </div>
        </form>
    </div>
</div>

<?php if (empty($plans)): ?>
    <div class="card"><?= Ui::emptyState(Translator::get('common.no_data'), 'target') ?></div>
<?php else: foreach ($plans as $plan): $prog = $plan['progress']; ?>
    <div id="plan-<?= View::e((string) $plan['id']) ?>" class="card" style="margin-bottom:18px;">
        <div class="card-header">
            <div>
                <h3 class="card-title"><?= View::e($plan['title']) ?> — <?= View::e($plan['student_name']) ?></h3>
                <div style="font-size:0.82rem;color:var(--text-muted);margin-top:2px;"><?= View::e(DateHelper::format($plan['week_start_date'], false)) ?> — <?= View::e(DateHelper::format($plan['week_end_date'], false)) ?></div>
            </div>
            <span class="badge badge-primary"><?= View::e(DateHelper::number($prog['completed'])) ?>/<?= View::e(DateHelper::number($prog['total'])) ?> (<?= View::e(DateHelper::percent($prog['percentage'])) ?>)</span>
        </div>

        <div class="progress" style="margin-bottom:14px;"><div class="progress-bar" style="width:<?= View::e((string) $prog['percentage']) ?>%;"></div></div>

        <div class="table-wrap" style="margin-bottom:14px;">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= View::e(Translator::get('counselor.day_of_week')) ?></th>
                        <th><?= View::e(Translator::get('common.subject')) ?></th>
                        <th><?= View::e(Translator::get('counselor.task_title')) ?></th>
                        <th><?= View::e(Translator::get('counselor.start_time')) ?></th>
                        <th><?= View::e(Translator::get('common.minutes')) ?></th>
                        <th><?= View::e(Translator::get('common.status')) ?></th>
                        <th><?= View::e(Translator::get('common.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plan['tasks'] as $t): ?>
                        <tr>
                            <td><?= View::e(Ui::weekdayLabel((string) $t['day_of_week'])) ?></td>
                            <td><?= View::e($t['subject_name'] ?? '-') ?></td>
                            <td><?= View::e($t['task_title']) ?></td>
                            <td><?= View::e(DateHelper::localizeDigits(($t['start_time'] ?? '') . ($t['end_time'] ? ' - ' . $t['end_time'] : ''))) ?></td>
                            <td><?= View::e(DateHelper::number($t['duration_minutes'])) ?> <?= View::e(Translator::get('common.minutes')) ?></td>
                            <td>
                                <span class="badge <?= (int) $t['is_completed'] === 1 ? 'badge-success' : 'badge-warning' ?>">
                                    <?= View::e(Translator::get((int) $t['is_completed'] === 1 ? 'status.completed' : 'status.pending')) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="<?= View::e(View::url('/counselor/study-plans/tasks/' . $t['id'] . '/delete')) ?>" data-confirm="<?= View::e(Translator::get('common.confirm_delete')) ?>">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-ghost btn-sm btn-icon" aria-label="<?= View::e(Translator::get('common.delete')) ?>"><?= Icon::svg('trash', 15) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <form method="POST" action="<?= View::e(View::url('/counselor/study-plans/' . $plan['id'] . '/tasks')) ?>" class="form-row" style="align-items:end;">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('counselor.day_of_week')) ?></label>
                <select class="form-control" name="day_of_week">
                    <?php foreach (['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $d): ?>
                        <option value="<?= View::e($d) ?>"><?= View::e(Ui::weekdayLabel($d)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.subject')) ?></label>
                <select class="form-control" name="subject_id">
                    <option value=""><?= View::e(Translator::get('common.all')) ?></option>
                    <?php foreach ($subjects as $sub): ?><option value="<?= View::e((string) $sub['id']) ?>"><?= View::e($sub['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('counselor.task_title')) ?></label><input class="form-control" name="task_title" required></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('counselor.start_time')) ?></label><input class="form-control" name="start_time" placeholder="16:00"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('counselor.end_time')) ?></label><input class="form-control" name="end_time" placeholder="17:00"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.minutes')) ?></label><input class="form-control" type="number" name="duration_minutes" value="60"></div>
            <div class="form-group"><button type="submit" class="btn btn-secondary btn-sm"><?= Icon::svg('plus', 14) ?><span><?= View::e(Translator::get('counselor.add_task')) ?></span></button></div>
        </form>
    </div>
<?php endforeach; endif; ?>
