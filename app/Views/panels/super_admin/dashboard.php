<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$stats = $stats ?? [];
$roleRows = $roleRows ?? [];
$recentLogs = $recentLogs ?? [];
$skyroomConfigured = $skyroomConfigured ?? false;

$donutPayload = array_map(static fn ($r) => [
    'label' => Translator::get('roles.' . $r['slug']),
    'value' => (int) $r['cnt'],
], $roleRows);
?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('panel.super_admin')) ?></h1></div>
    <a href="<?= View::e(View::url('/admin/health')) ?>" class="btn btn-secondary btn-sm">
        <?= Icon::svg('activity', 16) ?>
        <span><?= View::e(Translator::get('menu.health')) ?></span>
    </a>
</div>

<div class="grid grid-3" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('admin.total_accounts')) ?></span><span class="stat-icon"><?= Icon::svg('users', 16) ?></span></div>
        <span class="stat-value"><?= View::e(DateHelper::number($stats['total_users'] ?? 0)) ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('admin.enrolled_students')) ?></span><span class="stat-icon"><?= Icon::svg('graduation-cap', 16) ?></span></div>
        <span class="stat-value"><?= View::e(DateHelper::number($stats['active_students'] ?? 0)) ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('admin.active_teachers')) ?></span><span class="stat-icon"><?= Icon::svg('user-check', 16) ?></span></div>
        <span class="stat-value"><?= View::e(DateHelper::number($stats['active_teachers'] ?? 0)) ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('menu.schools')) ?></span><span class="stat-icon"><?= Icon::svg('building', 16) ?></span></div>
        <span class="stat-value"><?= View::e(DateHelper::number($stats['schools'] ?? 0)) ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('admin.announcements_count')) ?></span><span class="stat-icon"><?= Icon::svg('megaphone', 16) ?></span></div>
        <span class="stat-value"><?= View::e(DateHelper::number($stats['announcements'] ?? 0)) ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label"><?= View::e(Translator::get('admin.audit_entries')) ?></span><span class="stat-icon"><?= Icon::svg('shield-check', 16) ?></span></div>
        <span class="stat-value"><?= View::e(DateHelper::number($stats['audit_logs'] ?? 0)) ?></span>
    </div>
</div>

<div class="grid grid-2" style="margin-bottom:20px;align-items:start;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('bar-chart', 17) ?><span><?= View::e(Translator::get('admin.roles_distribution')) ?></span></h2>
        </div>
        <div data-donut-chart="<?= View::e(json_encode($donutPayload, JSON_UNESCAPED_UNICODE)) ?>"></div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('activity', 17) ?><span><?= View::e(Translator::get('admin.system_health')) ?></span></h2>
            <a href="<?= View::e(View::url('/admin/health')) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.details')) ?></a>
        </div>
        <div style="display:grid;gap:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);">
                <span><?= View::e(Translator::get('admin.db_status')) ?></span>
                <span class="badge badge-success"><?= View::e(Translator::get('admin.healthy')) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);">
                <span><?= View::e(Translator::get('admin.skyroom_diag')) ?></span>
                <span class="badge <?= $skyroomConfigured ? 'badge-success' : 'badge-warning' ?>">
                    <?= View::e(Translator::get($skyroomConfigured ? 'skyroom.status_connected' : 'skyroom.status_not_configured')) ?>
                </span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);">
                <span><?= View::e(Translator::get('admin.published_events')) ?></span>
                <span class="badge badge-primary"><?= View::e(DateHelper::number($stats['published_events'] ?? 0)) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= Icon::svg('shield-check', 17) ?><span><?= View::e(Translator::get('admin.recent_audit_logs')) ?></span></h2>
        <a href="<?= View::e(View::url('/admin/audit-logs')) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.view')) ?></a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.code')) ?></th>
                    <th><?= View::e(Translator::get('common.username')) ?></th>
                    <th><?= View::e(Translator::get('common.actions')) ?></th>
                    <th><?= View::e(Translator::get('admin.entity')) ?></th>
                    <th><?= View::e(Translator::get('admin.ip_address')) ?></th>
                    <th><?= View::e(Translator::get('common.date')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td><?= View::e(DateHelper::number($log['id'])) ?></td>
                        <td><?= View::e($log['username'] ?? 'system') ?></td>
                        <td><span class="badge badge-primary"><?= View::e($log['action']) ?></span></td>
                        <td><?= View::e($log['entity_type']) ?> #<?= View::e(DateHelper::number($log['entity_id'] ?? '-')) ?></td>
                        <td><code><?= View::e($log['ip_address'] ?? '-') ?></code></td>
                        <td><?= View::e(DateHelper::format($log['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
