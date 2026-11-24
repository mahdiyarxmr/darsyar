<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$stats = $stats ?? [];
$classEnrollment = $classEnrollment ?? [];
$chartPayload = array_map(static fn ($c) => [
    'label' => $c['class_name'],
    'value' => (int) $c['student_count'],
], $classEnrollment);
?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('panel.vice_principal')) ?></h1></div>
    <a href="<?= View::e(View::url('/vice-principal/parents')) ?>" class="btn btn-primary btn-sm">
        <?= Icon::svg('users', 15) ?>
        <span><?= View::e(Translator::get('bulk_parents.button')) ?></span>
    </a>
</div>

<div class="grid grid-3" style="margin-bottom:20px;">
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('vp.active_classes')) ?></span><span class="stat-icon"><?= Icon::svg('layers', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::number($stats['classes'] ?? 0)) ?></span></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('admin.active_teachers')) ?></span><span class="stat-icon"><?= Icon::svg('user-check', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::number($stats['teachers'] ?? 0)) ?></span></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('admin.enrolled_students')) ?></span><span class="stat-icon"><?= Icon::svg('graduation-cap', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::number($stats['students'] ?? 0)) ?></span></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('vp.parent_accounts')) ?></span><span class="stat-icon"><?= Icon::svg('users', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::number($stats['parents'] ?? 0)) ?></span></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('vp.unlinked_students')) ?></span><span class="stat-icon"><?= Icon::svg('alert-triangle', 16) ?></span></div><span class="stat-value" style="color:var(--warning);"><?= View::e(DateHelper::number($stats['unlinked_students'] ?? 0)) ?></span></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('vp.published_cards')) ?></span><span class="stat-icon"><?= Icon::svg('file-text', 16) ?></span></div><span class="stat-value"><?= View::e(DateHelper::number($stats['report_cards'] ?? 0)) ?></span></div>
</div>

<?php if (!empty($chartPayload)): ?>
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('bar-chart', 17) ?><span><?= View::e(Translator::get('director.students_count')) ?></span></h2>
        </div>
        <div class="chart-box" data-bar-chart="<?= View::e(json_encode($chartPayload, JSON_UNESCAPED_UNICODE)) ?>"></div>
    </div>
<?php endif; ?>

<div class="grid grid-3">
    <a href="<?= View::e(View::url('/vice-principal/students')) ?>" class="card card-interactive" style="color:inherit;">
        <h3 class="card-title"><?= Icon::svg('graduation-cap', 17) ?><span><?= View::e(Translator::get('menu.students_bulk')) ?></span></h3>
        <p style="color:var(--text-muted);font-size:0.86rem;margin:8px 0 0;"><?= View::e(Translator::get('vp.bulk_csv_card_desc')) ?></p>
    </a>
    <a href="<?= View::e(View::url('/vice-principal/parents')) ?>" class="card card-interactive" style="color:inherit;">
        <h3 class="card-title"><?= Icon::svg('users', 17) ?><span><?= View::e(Translator::get('bulk_parents.title')) ?></span></h3>
        <p style="color:var(--text-muted);font-size:0.86rem;margin:8px 0 0;"><?= View::e(Translator::get('vp.bulk_parent_card_desc')) ?></p>
    </a>
    <a href="<?= View::e(View::url('/vice-principal/report-cards')) ?>" class="card card-interactive" style="color:inherit;">
        <h3 class="card-title"><?= Icon::svg('file-text', 17) ?><span><?= View::e(Translator::get('menu.report_cards')) ?></span></h3>
        <p style="color:var(--text-muted);font-size:0.86rem;margin:8px 0 0;"><?= View::e(Translator::get('vp.report_card_desc')) ?></p>
    </a>
</div>
