<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$assignments = $assignments ?? [];
$selectedPair = $selectedPair ?? '';
$selectedDate = $selectedDate ?? date('Y-m-d');
$students = $students ?? [];
$history = $history ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.teacher'), 'url' => '/teacher/dashboard'], ['label' => Translator::get('menu.attendance')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.attendance')) ?></h1></div>
</div>

<div class="filter-panel">
    <form method="GET" action="<?= View::e(View::url('/teacher/attendance')) ?>" class="form-row" style="align-items:end;">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label"><?= View::e(Translator::get('teacher.class_subject')) ?></label>
            <select class="form-control" name="pair">
                <?php foreach ($assignments as $a): $val = $a['class_id'] . ':' . $a['subject_id']; ?>
                    <option value="<?= View::e($val) ?>" <?= $val === $selectedPair ? 'selected' : '' ?>><?= View::e($a['class_name'] . ' — ' . $a['subject_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group date-picker-wrap" style="margin-bottom:0;"><label class="form-label"><?= View::e(Translator::get('common.date')) ?></label><input class="form-control" type="date" name="date" value="<?= View::e($selectedDate) ?>"></div>
        <div><button type="submit" class="btn btn-secondary btn-sm"><?= Icon::svg('filter', 14) ?><span><?= View::e(Translator::get('common.filter')) ?></span></button></div>
    </form>
</div>

<div class="card" style="margin-bottom:20px;">
    <form method="POST" action="<?= View::e(View::url('/teacher/attendance/save')) ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="pair" value="<?= View::e($selectedPair) ?>">
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
            <button type="submit" class="btn btn-primary" style="margin-top:14px;"><?= Icon::svg('check', 16) ?><span><?= View::e(Translator::get('teacher.save_class_attendance')) ?></span></button>
        <?php endif; ?>
    </form>
</div>

<?php if (!empty($history)): ?>
    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('counselor.attendance_summary')) ?></h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= View::e(Translator::get('common.date')) ?></th><th><?= View::e(Translator::get('common.status')) ?></th><th><?= View::e(Translator::get('common.total')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= View::e(DateHelper::format($h['attendance_date'], false)) ?></td>
                            <td><span class="badge badge-muted"><?= View::e(Ui::statusLabel($h['status'])) ?></span></td>
                            <td><?= View::e(DateHelper::number($h['cnt'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
