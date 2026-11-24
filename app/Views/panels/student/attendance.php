<?php

use App\Core\DateHelper;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$summary = $summary ?? ['records' => [], 'rate' => 100, 'present' => 0, 'absent' => 0, 'late' => 0];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.student'), 'url' => '/student/dashboard'], ['label' => Translator::get('menu.attendance')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.attendance')) ?></h1></div>
</div>

<div class="grid grid-4" style="margin-bottom:20px;">
    <div class="stat-card"><span class="stat-label"><?= View::e(Translator::get('student.attendance_rate')) ?></span><span class="stat-value"><?= View::e(DateHelper::percent($summary['rate'])) ?></span></div>
    <div class="stat-card"><span class="stat-label"><?= View::e(Translator::get('student.attendance_present')) ?></span><span class="stat-value"><?= View::e(DateHelper::number($summary['present'])) ?></span></div>
    <div class="stat-card"><span class="stat-label"><?= View::e(Translator::get('student.attendance_absent')) ?></span><span class="stat-value" style="color:var(--danger);"><?= View::e(DateHelper::number($summary['absent'])) ?></span></div>
    <div class="stat-card"><span class="stat-label"><?= View::e(Translator::get('student.attendance_late')) ?></span><span class="stat-value" style="color:var(--warning);"><?= View::e(DateHelper::number($summary['late'])) ?></span></div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.date')) ?></th>
                    <th><?= View::e(Translator::get('admin.category')) ?></th>
                    <th><?= View::e(Translator::get('common.subject')) ?></th>
                    <th><?= View::e(Translator::get('common.status')) ?></th>
                    <th><?= View::e(Translator::get('common.notes')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($summary['records'])): ?>
                    <tr><td colspan="5"><?= View::e(Translator::get('common.no_data')) ?></td></tr>
                <?php else: foreach ($summary['records'] as $r): ?>
                    <tr>
                        <td><?= View::e(DateHelper::format($r['attendance_date'], false)) ?></td>
                        <td><?= View::e(Ui::statusLabel($r['scope'])) ?></td>
                        <td><?= View::e($r['subject_name'] ?? Translator::get('student.school_daily')) ?></td>
                        <td><span class="badge <?= $r['status'] === 'present' ? 'badge-success' : ($r['status'] === 'absent' ? 'badge-danger' : 'badge-warning') ?>"><?= View::e(Ui::statusLabel($r['status'])) ?></span></td>
                        <td><?= View::e($r['note'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
