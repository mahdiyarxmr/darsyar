<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$rankings = $rankings ?? [];
$weights = $weights ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.director'), 'url' => '/director/dashboard'], ['label' => Translator::get('menu.teacher_ratings')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.teacher_ratings')) ?></h1></div>
</div>

<div class="card" style="margin-bottom:20px;">
    <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('settings', 17) ?><span><?= View::e(Translator::get('director.teacher_weights_title')) ?></span></h2>
    <form method="POST" action="<?= View::e(View::url('/director/teacher-ratings/weights')) ?>">
        <?= Csrf::field() ?>
        <div class="form-row">
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('director.hw_checking')) ?></label><input class="form-control" type="number" name="homework" value="<?= View::e((string) ($weights['homework'] ?? 25)) ?>"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('director.exams_created')) ?></label><input class="form-control" type="number" name="exams" value="<?= View::e((string) ($weights['exams'] ?? 25)) ?>"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('director.student_improvement')) ?></label><input class="form-control" type="number" name="improvement" value="<?= View::e((string) ($weights['improvement'] ?? 25)) ?>"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('director.attendance_logging')) ?></label><input class="form-control" type="number" name="attendance" value="<?= View::e((string) ($weights['attendance'] ?? 15)) ?>"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('director.teaching_activity')) ?></label><input class="form-control" type="number" name="activity" value="<?= View::e((string) ($weights['activity'] ?? 10)) ?>"></div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= View::e(Translator::get('director.recalculate_weights')) ?></button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.rank')) ?></th>
                    <th><?= View::e(Translator::get('common.teacher')) ?></th>
                    <th><?= View::e(Translator::get('common.code')) ?></th>
                    <th><?= View::e(Translator::get('director.hw_checking')) ?></th>
                    <th><?= View::e(Translator::get('menu.exams')) ?></th>
                    <th><?= View::e(Translator::get('director.student_improvement')) ?></th>
                    <th><?= View::e(Translator::get('menu.attendance')) ?></th>
                    <th><?= View::e(Translator::get('director.teaching_activity')) ?></th>
                    <th><?= View::e(Translator::get('common.total')) ?></th>
                    <th><?= View::e(Translator::get('report_card.remarks')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rankings as $i => $t): ?>
                    <tr>
                        <td><?= View::e(DateHelper::number($i + 1)) ?></td>
                        <td><?= View::e($t['full_name']) ?></td>
                        <td><code><?= View::e($t['employee_code']) ?></code></td>
                        <td><?= View::e(DateHelper::number($t['homework_score'])) ?></td>
                        <td><?= View::e(DateHelper::number($t['exams_score'])) ?></td>
                        <td><?= View::e(DateHelper::number($t['improvement_score'])) ?></td>
                        <td><?= View::e(DateHelper::number($t['attendance_score'])) ?></td>
                        <td><?= View::e(DateHelper::number($t['activity_score'])) ?></td>
                        <td><strong><?= View::e(DateHelper::number($t['total_score'])) ?></strong></td>
                        <td><span class="badge badge-primary"><?= View::e(Ui::ratingLabel($t['category'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
