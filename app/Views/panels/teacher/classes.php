<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$assignments = $assignments ?? [];
$selectedClassId = $selectedClassId ?? 0;
$students = $students ?? [];

$uniqueClasses = [];
foreach ($assignments as $a) {
    $uniqueClasses[(int) $a['class_id']] = $a['class_name'] . ' (' . $a['grade_name'] . ')';
}
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.teacher'), 'url' => '/teacher/dashboard'], ['label' => Translator::get('menu.my_classes')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.my_classes')) ?></h1></div>
</div>

<div class="pill-tabs">
    <?php foreach ($uniqueClasses as $cid => $label): ?>
        <a href="<?= View::e(View::url('/teacher/classes?class_id=' . $cid)) ?>" class="pill-tab <?= $cid === $selectedClassId ? 'active' : '' ?>">
            <?= Icon::svg('users', 14) ?>
            <span><?= View::e($label) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('teacher.class_roster')) ?></h2>
    <?php if (empty($students)): ?>
        <?= Ui::emptyState(Translator::get('common.no_data'), 'users') ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= View::e(Translator::get('vp.student_code')) ?></th>
                        <th><?= View::e(Translator::get('common.student')) ?></th>
                        <th><?= View::e(Translator::get('student.exam_avg')) ?></th>
                        <th><?= View::e(Translator::get('director.hw_completion')) ?></th>
                        <th><?= View::e(Translator::get('student.attendance_rate')) ?></th>
                        <th><?= View::e(Translator::get('common.score')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td><code><?= View::e($s['student_code']) ?></code></td>
                            <td><?= View::e($s['full_name']) ?></td>
                            <td><?= View::e(DateHelper::percent($s['academic_score'])) ?></td>
                            <td><?= View::e(DateHelper::percent($s['homework_score'])) ?></td>
                            <td><?= View::e(DateHelper::percent($s['attendance_score'])) ?></td>
                            <td><strong><?= View::e(DateHelper::number($s['total_score'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
