<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$children = $children ?? [];
$activeChild = $activeChild ?? null;
$cards = $cards ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.parent'), 'url' => '/parent/dashboard'], ['label' => Translator::get('menu.report_cards')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.report_cards')) ?></h1></div>
</div>

<?= View::partial('parent_child_switcher', ['children' => $children, 'activeChild' => $activeChild, 'basePath' => '/parent/report-cards']) ?>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.year')) ?></th>
                    <th><?= View::e(Translator::get('vp.report_type')) ?></th>
                    <th><?= View::e(Translator::get('vp.period_label')) ?></th>
                    <th><?= View::e(Translator::get('report_card.final_average')) ?></th>
                    <th><?= View::e(Translator::get('student.attendance_rate')) ?></th>
                    <th><?= View::e(Translator::get('common.status')) ?></th>
                    <th><?= View::e(Translator::get('common.print_pdf')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cards)): ?>
                    <tr><td colspan="7"><?= View::e(Translator::get('common.no_data')) ?></td></tr>
                <?php else: foreach ($cards as $c): ?>
                    <tr>
                        <td><?= View::e(DateHelper::localizeDigits($c['year_name'])) ?></td>
                        <td><span class="badge badge-primary"><?= View::e(Ui::statusLabel($c['report_type'])) ?></span></td>
                        <td><?= View::e($c['period_label']) ?></td>
                        <td><strong><?= View::e(DateHelper::percent($c['final_average'])) ?></strong></td>
                        <td><?= View::e(DateHelper::percent($c['attendance_rate'])) ?></td>
                        <td><span class="badge badge-success"><?= View::e(Ui::ratingLabel($c['academic_status'])) ?></span></td>
                        <td><a href="<?= View::e(View::url('/report-cards/' . $c['id'] . '/print')) ?>" class="btn btn-primary btn-sm" target="_blank"><?= Icon::svg('printer', 14) ?><span><?= View::e(Translator::get('common.print_pdf')) ?></span></a></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
