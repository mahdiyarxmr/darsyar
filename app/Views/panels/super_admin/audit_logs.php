<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$logs = $logs ?? [];
$actions = $actions ?? [];
$q = $q ?? '';
$actionFilter = $actionFilter ?? '';
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$total = $total ?? count($logs);
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.super_admin'), 'url' => '/admin/dashboard'], ['label' => Translator::get('menu.audit_logs')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.audit_logs')) ?></h1></div>
    <span class="badge badge-muted"><?= View::e(DateHelper::number($total)) ?> <?= View::e(Translator::get('common.items')) ?></span>
</div>

<div class="filter-panel">
    <form method="GET" action="<?= View::e(View::url('/admin/audit-logs')) ?>" class="form-row" style="align-items:end;">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label"><?= View::e(Translator::get('common.search')) ?></label>
            <div class="search-field">
                <span class="search-field-icon"><?= Icon::svg('search', 15) ?></span>
                <input class="form-control" name="q" value="<?= View::e($q) ?>" placeholder="<?= View::e(Translator::get('common.username')) ?> / IP">
            </div>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label"><?= View::e(Translator::get('admin.filter_action')) ?></label>
            <select class="form-control" name="action">
                <option value=""><?= View::e(Translator::get('common.all')) ?></option>
                <?php foreach ($actions as $act): ?>
                    <option value="<?= View::e($act['action']) ?>" <?= $actionFilter === $act['action'] ? 'selected' : '' ?>><?= View::e($act['action']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary btn-sm"><?= Icon::svg('filter', 14) ?><span><?= View::e(Translator::get('common.filter')) ?></span></button>
            <a href="<?= View::e(View::url('/admin/audit-logs')) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.reset')) ?></a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.code')) ?></th>
                    <th><?= View::e(Translator::get('common.username')) ?></th>
                    <th><?= View::e(Translator::get('common.actions')) ?></th>
                    <th><?= View::e(Translator::get('admin.entity')) ?></th>
                    <th><?= View::e(Translator::get('admin.metadata')) ?></th>
                    <th><?= View::e(Translator::get('admin.ip_address')) ?></th>
                    <th><?= View::e(Translator::get('common.date')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7"><?= View::e(Translator::get('common.no_data')) ?></td></tr>
                <?php else: foreach ($logs as $log): ?>
                    <tr>
                        <td><?= View::e(DateHelper::number($log['id'])) ?></td>
                        <td><?= View::e($log['username'] ?? 'system') ?></td>
                        <td><span class="badge badge-primary"><?= View::e($log['action']) ?></span></td>
                        <td><?= View::e($log['entity_type']) ?> #<?= View::e(DateHelper::number($log['entity_id'] ?? '-')) ?></td>
                        <td><small><?= View::e($log['metadata_json'] ?? '') ?></small></td>
                        <td><code><?= View::e($log['ip_address'] ?? '-') ?></code></td>
                        <td><?= View::e(DateHelper::format($log['created_at'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= Ui::pagination($page, $totalPages, '/admin/audit-logs?q=' . urlencode($q) . '&action=' . urlencode($actionFilter)) ?>
</div>
