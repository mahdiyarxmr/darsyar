<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$classes = $classes ?? [];
$years = $years ?? [];
$semesters = $semesters ?? [];
$cards = $cards ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.vice_principal'), 'url' => '/vice-principal/dashboard'], ['label' => Translator::get('menu.report_cards')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.report_cards')) ?></h1></div>
</div>

<div class="card" style="margin-bottom:20px;">
    <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('file-text', 17) ?><span><?= View::e(Translator::get('vp.generate_cards')) ?></span></h2>
    <form method="POST" action="<?= View::e(View::url('/vice-principal/report-cards/generate')) ?>">
        <?= Csrf::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.class')) ?></label>
                <select class="form-control" name="class_id">
                    <?php foreach ($classes as $c): ?><option value="<?= View::e((string) $c['id']) ?>"><?= View::e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.year')) ?></label>
                <select class="form-control" name="academic_year_id">
                    <?php foreach ($years as $y): ?><option value="<?= View::e((string) $y['id']) ?>"><?= View::e($y['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('vp.report_type')) ?></label>
                <select class="form-control" name="report_type">
                    <option value="monthly"><?= View::e(Translator::get('status.monthly')) ?></option>
                    <option value="semester"><?= View::e(Translator::get('status.semester')) ?></option>
                    <option value="annual"><?= View::e(Translator::get('status.annual')) ?></option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('vp.period_label')) ?></label>
                <input class="form-control" name="period_label" value="<?= View::e(DateHelper::format(date('Y-m-d'), false)) ?>" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('vp.generate_publish_btn')) ?></button>
    </form>
</div>

<div class="card">
    <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('vp.generated_cards')) ?></h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.student')) ?></th>
                    <th><?= View::e(Translator::get('common.class')) ?></th>
                    <th><?= View::e(Translator::get('vp.report_type')) ?></th>
                    <th><?= View::e(Translator::get('vp.period_label')) ?></th>
                    <th><?= View::e(Translator::get('report_card.final_average')) ?></th>
                    <th><?= View::e(Translator::get('student.attendance_rate')) ?></th>
                    <th><?= View::e(Translator::get('common.status')) ?></th>
                    <th><?= View::e(Translator::get('common.notes')) ?></th>
                    <th><?= View::e(Translator::get('common.print_pdf')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cards as $rc): ?>
                    <tr>
                        <td><?= View::e($rc['student_name']) ?> (<code><?= View::e($rc['student_code']) ?></code>)</td>
                        <td><?= View::e($rc['class_name'] ?? '-') ?></td>
                        <td><span class="badge badge-primary"><?= View::e(Ui::statusLabel($rc['report_type'])) ?></span></td>
                        <td><?= View::e($rc['period_label']) ?></td>
                        <td><strong><?= View::e(DateHelper::percent($rc['final_average'])) ?></strong></td>
                        <td><?= View::e(DateHelper::percent($rc['attendance_rate'])) ?></td>
                        <td><span class="badge badge-success"><?= View::e(Ui::ratingLabel($rc['academic_status'])) ?></span></td>
                        <td>
                            <form method="POST" action="<?= View::e(View::url('/vice-principal/report-cards/' . $rc['id'] . '/comment')) ?>" style="display:flex;gap:6px;">
                                <?= Csrf::field() ?>
                                <input class="form-control" name="comments" value="<?= View::e((string) ($rc['comments'] ?? '')) ?>" style="min-height:32px;padding:4px 8px;min-width:140px;">
                                <button type="submit" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.save')) ?></button>
                            </form>
                        </td>
                        <td><a href="<?= View::e(View::url('/report-cards/' . $rc['id'] . '/print')) ?>" class="btn btn-secondary btn-sm" target="_blank"><?= Icon::svg('printer', 14) ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
