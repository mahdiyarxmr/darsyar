<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$rankings = $rankings ?? [];
$weights = $weights ?? [];
$classes = $classes ?? [];
$selectedClassId = $selectedClassId ?? 0;
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.director'), 'url' => '/director/dashboard'], ['label' => Translator::get('menu.student_ratings')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.student_ratings')) ?></h1></div>
    <form method="GET" action="<?= View::e(View::url('/director/student-ratings')) ?>" style="display:flex;gap:8px;align-items:center;">
        <select class="form-control" name="class_id" style="width:auto;">
            <option value="0"><?= View::e(Translator::get('director.all_classes')) ?></option>
            <?php foreach ($classes as $c): ?>
                <option value="<?= View::e((string) $c['id']) ?>" <?= (int) $c['id'] === $selectedClassId ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm"><?= Icon::svg('filter', 14) ?><span><?= View::e(Translator::get('common.filter')) ?></span></button>
    </form>
</div>

<div class="card" style="margin-bottom:20px;">
    <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('settings', 17) ?><span><?= View::e(Translator::get('director.student_weights_title')) ?></span></h2>
    <form method="POST" action="<?= View::e(View::url('/director/student-ratings/weights')) ?>">
        <?= Csrf::field() ?>
        <div class="form-row">
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('director.exam_results')) ?></label><input class="form-control" type="number" name="academic" value="<?= View::e((string) ($weights['academic'] ?? 35)) ?>"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('director.hw_completion')) ?></label><input class="form-control" type="number" name="homework" value="<?= View::e((string) ($weights['homework'] ?? 20)) ?>"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('director.daily_tests_weight')) ?></label><input class="form-control" type="number" name="daily_tests" value="<?= View::e((string) ($weights['daily_tests'] ?? 15)) ?>"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('director.study_plan_weight')) ?></label><input class="form-control" type="number" name="study_plan" value="<?= View::e((string) ($weights['study_plan'] ?? 15)) ?>"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('director.attendance_weight')) ?></label><input class="form-control" type="number" name="attendance" value="<?= View::e((string) ($weights['attendance'] ?? 10)) ?>"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('director.improvement_weight')) ?></label><input class="form-control" type="number" name="improvement" value="<?= View::e((string) ($weights['improvement'] ?? 5)) ?>"></div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= View::e(Translator::get('director.update_weights')) ?></button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.rank')) ?></th>
                    <th><?= View::e(Translator::get('common.student')) ?></th>
                    <th><?= View::e(Translator::get('common.class')) ?></th>
                    <th><?= View::e(Translator::get('director.exam_results')) ?></th>
                    <th><?= View::e(Translator::get('menu.homework')) ?></th>
                    <th><?= View::e(Translator::get('director.daily_tests_weight')) ?></th>
                    <th><?= View::e(Translator::get('director.study_plan_weight')) ?></th>
                    <th><?= View::e(Translator::get('director.attendance_weight')) ?></th>
                    <th><?= View::e(Translator::get('director.improvement_weight')) ?></th>
                    <th><?= View::e(Translator::get('common.total')) ?></th>
                    <th><?= View::e(Translator::get('report_card.remarks')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rankings as $i => $s): ?>
                    <tr>
                        <td><?= View::e(DateHelper::number($i + 1)) ?></td>
                        <td><?= View::e($s['full_name']) ?></td>
                        <td><?= View::e($s['class_name']) ?></td>
                        <td><?= View::e(DateHelper::number($s['academic_score'])) ?></td>
                        <td><?= View::e(DateHelper::number($s['homework_score'])) ?></td>
                        <td><?= View::e(DateHelper::number($s['participation_score'])) ?></td>
                        <td><?= View::e(DateHelper::number($s['study_plan_score'])) ?></td>
                        <td><?= View::e(DateHelper::number($s['attendance_score'])) ?></td>
                        <td><?= View::e(DateHelper::number($s['improvement_score'])) ?></td>
                        <td><strong><?= View::e(DateHelper::number($s['total_score'])) ?></strong></td>
                        <td><span class="badge badge-success"><?= View::e(Ui::ratingLabel($s['category'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
