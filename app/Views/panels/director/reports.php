<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$subjectStats = $subjectStats ?? [];
$attendanceBreakdown = $attendanceBreakdown ?? [];
$reportCardStats = $reportCardStats ?? [];

$subjectChart = array_map(static fn ($s) => [
    'label' => $s['subject_name'],
    'value' => (float) $s['avg_pct'],
], $subjectStats);

$attDonut = array_map(static fn ($a) => [
    'label' => Ui::statusLabel($a['status']),
    'value' => (int) $a['cnt'],
], $attendanceBreakdown);
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.director'), 'url' => '/director/dashboard'], ['label' => Translator::get('menu.reports')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.reports')) ?></h1></div>
    <a href="<?= View::e(View::url('/director/reports/export-csv')) ?>" class="btn btn-primary btn-sm">
        <?= Icon::svg('download', 15) ?>
        <span><?= View::e(Translator::get('common.export_csv')) ?></span>
    </a>
</div>

<div class="grid grid-2" style="margin-bottom:20px;align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('bar-chart', 17) ?><span><?= View::e(Translator::get('director.subject_stats')) ?></span></h2>
        <div class="chart-box" data-bar-chart="<?= View::e(json_encode($subjectChart, JSON_UNESCAPED_UNICODE)) ?>"></div>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('clipboard-check', 17) ?><span><?= View::e(Translator::get('director.attendance_overview')) ?></span></h2>
        <?php if (empty($attDonut)): ?>
            <?= Ui::emptyState(Translator::get('common.no_data'), 'clipboard-check') ?>
        <?php else: ?>
            <div data-donut-chart="<?= View::e(json_encode($attDonut, JSON_UNESCAPED_UNICODE)) ?>"></div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('director.subject_stats')) ?></h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= View::e(Translator::get('common.code')) ?></th><th><?= View::e(Translator::get('common.subject')) ?></th><th><?= View::e(Translator::get('director.total_exams')) ?></th><th><?= View::e(Translator::get('director.exam_avg_pct')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($subjectStats as $s): ?>
                        <tr>
                            <td><code><?= View::e($s['code']) ?></code></td>
                            <td><?= View::e($s['subject_name']) ?></td>
                            <td><?= View::e(DateHelper::number($s['exam_count'])) ?></td>
                            <td><strong><?= View::e(DateHelper::percent($s['avg_pct'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('director.report_card_summary')) ?></h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= View::e(Translator::get('vp.report_type')) ?></th><th><?= View::e(Translator::get('common.total')) ?></th><th><?= View::e(Translator::get('report_card.final_average')) ?></th></tr></thead>
                <tbody>
                    <?php if (empty($reportCardStats)): ?>
                        <tr><td colspan="3"><?= View::e(Translator::get('common.no_data')) ?></td></tr>
                    <?php else: foreach ($reportCardStats as $rc): ?>
                        <tr>
                            <td><span class="badge badge-primary"><?= View::e(Ui::statusLabel($rc['report_type'])) ?></span></td>
                            <td><?= View::e(DateHelper::number($rc['cnt'])) ?></td>
                            <td><strong><?= View::e(DateHelper::percent($rc['avg_score'])) ?></strong></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
