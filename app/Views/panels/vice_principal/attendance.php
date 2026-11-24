<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$classes = $classes ?? [];
$selectedClassId = $selectedClassId ?? 0;
$selectedDate = $selectedDate ?? date('Y-m-d');
$students = $students ?? [];
$monthStats = $monthStats ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.vice_principal'), 'url' => '/vice-principal/dashboard'], ['label' => Translator::get('menu.attendance')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.attendance')) ?></h1></div>
</div>

<div class="filter-panel">
    <form method="GET" action="<?= View::e(View::url('/vice-principal/attendance')) ?>" class="form-row" style="align-items:end;">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label"><?= View::e(Translator::get('common.class')) ?></label>
            <select class="form-control" name="class_id">
                <?php foreach ($classes as $c): ?>
                    <option value="<?= View::e((string) $c['id']) ?>" <?= (int) $c['id'] === $selectedClassId ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group date-picker-wrap" style="margin-bottom:0;">
            <label class="form-label"><?= View::e(Translator::get('common.date')) ?></label>
            <input class="form-control" type="date" name="date" value="<?= View::e($selectedDate) ?>">
        </div>
        <div><button type="submit" class="btn btn-secondary btn-sm"><?= Icon::svg('filter', 14) ?><span><?= View::e(Translator::get('common.filter')) ?></span></button></div>
    </form>
</div>

<?php if (!empty($monthStats)): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center;">
        <span style="font-size:0.84rem;color:var(--text-muted);font-weight:600;"><?= View::e(Translator::get('vp.monthly_summary')) ?>:</span>
        <?php foreach ($monthStats as $ms): ?>
            <span class="badge <?= $ms['status'] === 'present' ? 'badge-success' : ($ms['status'] === 'absent' ? 'badge-danger' : 'badge-warning') ?>">
                <?= View::e(Ui::statusLabel($ms['status'])) ?>: <?= View::e(DateHelper::number($ms['cnt'])) ?>
            </span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="<?= View::e(View::url('/vice-principal/attendance/save')) ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="class_id" value="<?= View::e((string) $selectedClassId) ?>">
        <input type="hidden" name="attendance_date" value="<?= View::e($selectedDate) ?>">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= View::e(Translator::get('vp.student_code')) ?></th><th><?= View::e(Translator::get('common.student')) ?></th><th><?= View::e(Translator::get('common.status')) ?></th><th><?= View::e(Translator::get('common.notes')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($students as $s): $cur = $s['today_status'] ?? 'present'; ?>
                        <tr>
                            <td><code><?= View::e($s['student_code']) ?></code></td>
                            <td><?= View::e($s['full_name']) ?></td>
                            <td>
                                <select class="form-control" name="status[<?= View::e((string) $s['id']) ?>]">
                                    <option value="present" <?= $cur === 'present' ? 'selected' : '' ?>><?= View::e(Translator::get('status.present')) ?></option>
                                    <option value="absent" <?= $cur === 'absent' ? 'selected' : '' ?>><?= View::e(Translator::get('status.absent')) ?></option>
                                    <option value="late" <?= $cur === 'late' ? 'selected' : '' ?>><?= View::e(Translator::get('status.late')) ?></option>
                                    <option value="excused" <?= $cur === 'excused' ? 'selected' : '' ?>><?= View::e(Translator::get('status.excused')) ?></option>
                                </select>
                            </td>
                            <td><input class="form-control" name="note[<?= View::e((string) $s['id']) ?>]"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($students)): ?>
            <button type="submit" class="btn btn-primary" style="margin-top:14px;"><?= Icon::svg('check', 16) ?><span><?= View::e(Translator::get('vp.attendance_save_notify')) ?></span></button>
        <?php endif; ?>
    </form>
</div>
