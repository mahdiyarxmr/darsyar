<?php

use App\Core\DateHelper;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$classStats = $classStats ?? [];
$chartPayload = array_map(static fn ($c) => [
    'label' => $c['class_name'],
    'value' => (float) $c['avg_exam_pct'],
], $classStats);
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.director'), 'url' => '/director/dashboard'], ['label' => Translator::get('menu.class_comparison')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.class_comparison')) ?></h1></div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="chart-box" data-bar-chart="<?= View::e(json_encode($chartPayload, JSON_UNESCAPED_UNICODE)) ?>"></div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.class')) ?></th>
                    <th><?= View::e(Translator::get('common.grade')) ?></th>
                    <th><?= View::e(Translator::get('director.students_count')) ?></th>
                    <th><?= View::e(Translator::get('director.exam_avg_pct')) ?></th>
                    <th><?= View::e(Translator::get('director.daily_practice_count')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classStats as $c): ?>
                    <tr>
                        <td><strong><?= View::e($c['class_name']) ?></strong></td>
                        <td><?= View::e($c['grade_name']) ?></td>
                        <td><?= View::e(DateHelper::number($c['student_count'])) ?></td>
                        <td><?= View::e(DateHelper::percent($c['avg_exam_pct'])) ?></td>
                        <td><?= View::e(DateHelper::number($c['daily_test_count'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
