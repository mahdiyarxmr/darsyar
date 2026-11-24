<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$phpVersion = $phpVersion ?? PHP_VERSION;
$dbDriver = $dbDriver ?? 'mysql';
$dbOk = $dbOk ?? false;
$dbLatencyMs = $dbLatencyMs ?? 0;
$extStatus = $extStatus ?? [];
$sessionWritable = $sessionWritable ?? false;
$uploadsWritable = $uploadsWritable ?? false;
$parity = $parity ?? ['ok' => false, 'total_fa' => 0, 'total_en' => 0];
$skyroomDiag = $skyroomDiag ?? ['ok' => false, 'configured' => false, 'message' => ''];
$supportedLocales = $supportedLocales ?? ['fa', 'en'];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.super_admin'), 'url' => '/admin/dashboard'], ['label' => Translator::get('admin.system_health')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('admin.system_health')) ?></h1></div>
    <a href="<?= View::e(View::url('/admin/health')) ?>" class="btn btn-secondary btn-sm">
        <?= Icon::svg('refresh', 15) ?>
        <span><?= View::e(Translator::get('admin.run_diag')) ?></span>
    </a>
</div>

<div class="grid grid-3" style="margin-bottom:20px;">
    <div class="stat-card">
        <span class="stat-label"><?= View::e(Translator::get('admin.php_version')) ?></span>
        <span class="stat-value" style="font-size:1.35rem;"><?= View::e($phpVersion) ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label"><?= View::e(Translator::get('admin.db_status')) ?> (<?= View::e(strtoupper($dbDriver)) ?>)</span>
        <span class="stat-value" style="font-size:1.25rem;color:<?= $dbOk ? 'var(--success)' : 'var(--danger)' ?>;">
            <?= View::e(Translator::get($dbOk ? 'admin.healthy' : 'admin.error_status')) ?>
        </span>
        <span class="stat-meta"><?= View::e(Translator::get('admin.db_latency')) ?>: <?= View::e(DateHelper::number($dbLatencyMs)) ?> ms</span>
    </div>
    <div class="stat-card">
        <span class="stat-label"><?= View::e(Translator::get('admin.translation_parity')) ?></span>
        <span class="stat-value" style="font-size:1.25rem;color:<?= $parity['ok'] ? 'var(--success)' : 'var(--danger)' ?>;">
            <?= View::e(Translator::get($parity['ok'] ? 'admin.healthy' : 'admin.error_status')) ?>
        </span>
        <span class="stat-meta"><?= View::e(DateHelper::number($parity['total_fa'])) ?> / <?= View::e(DateHelper::number($parity['total_en'])) ?> (<?= View::e(implode(', ', $supportedLocales)) ?>)</span>
    </div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('shield-check', 17) ?><span><?= View::e(Translator::get('admin.extensions_status')) ?></span></h2>
        <div style="display:grid;gap:8px;">
            <?php foreach ($extStatus as $ext => $loaded): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);">
                    <code><?= View::e($ext) ?></code>
                    <span class="badge <?= $loaded ? 'badge-success' : 'badge-danger' ?>"><?= View::e(Translator::get($loaded ? 'common.yes' : 'common.no')) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('activity', 17) ?><span><?= View::e(Translator::get('admin.storage_writable')) ?> &amp; <?= View::e(Translator::get('admin.skyroom_diag')) ?></span></h2>
        <div style="display:grid;gap:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);">
                <span><code>storage/sessions</code></span>
                <span class="badge <?= $sessionWritable ? 'badge-success' : 'badge-danger' ?>"><?= View::e(Translator::get($sessionWritable ? 'admin.healthy' : 'admin.error_status')) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);">
                <span><code>storage/uploads</code></span>
                <span class="badge <?= $uploadsWritable ? 'badge-success' : 'badge-danger' ?>"><?= View::e(Translator::get($uploadsWritable ? 'admin.healthy' : 'admin.error_status')) ?></span>
            </div>
            <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <strong><?= View::e(Translator::get('admin.skyroom_diag')) ?></strong>
                    <span class="badge <?= $skyroomDiag['ok'] ? 'badge-success' : ($skyroomDiag['configured'] ? 'badge-danger' : 'badge-warning') ?>">
                        <?= View::e(Translator::get($skyroomDiag['ok'] ? 'skyroom.status_connected' : ($skyroomDiag['configured'] ? 'admin.error_status' : 'skyroom.status_not_configured'))) ?>
                    </span>
                </div>
                <div style="font-size:0.84rem;color:var(--text-muted);"><?= View::e($skyroomDiag['message']) ?></div>
            </div>
        </div>
    </div>
</div>
