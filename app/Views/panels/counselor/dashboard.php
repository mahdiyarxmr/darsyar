<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$students = $students ?? [];
$atRisk = $atRisk ?? [];
$requests = $requests ?? [];
$classes = $classes ?? [];
$search = $search ?? '';
$classFilter = $classFilter ?? 0;

$chartData = [];
foreach (array_slice($students, 0, 8) as $st) {
    $chartData[] = [
        'label' => $st['full_name'],
        'value' => (float) $st['completion_rate'],
    ];
}
?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('panel.counselor')) ?></h1></div>
    <div class="page-header-actions">
        <a href="<?= View::e(View::url('/counselor/study-plans')) ?>" class="btn btn-primary btn-sm"><?= Icon::svg('target', 15) ?><span><?= View::e(Translator::get('menu.study_plans')) ?></span></a>
        <a href="<?= View::e(View::url('/counselor/notes')) ?>" class="btn btn-secondary btn-sm"><?= Icon::svg('notebook', 15) ?><span><?= View::e(Translator::get('menu.counseling_notes')) ?></span></a>
    </div>
</div>

<?php if (!empty($atRisk)): ?>
    <div class="alert alert-warning">
        <?= Icon::svg('alert-triangle', 18) ?>
        <div>
            <strong><?= View::e(Translator::get('counselor.at_risk_alert')) ?></strong>
            <?php foreach ($atRisk as $r): ?>
                <span class="badge badge-danger" style="margin-inline-start:6px;"><?= View::e($r['full_name']) ?> (<?= View::e(DateHelper::percent($r['completion_rate'])) ?>)</span>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($requests)): ?>
    <div class="card" style="margin-bottom:18px;border-color:var(--primary);">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('bell', 17) ?><span><?= View::e(Translator::get('counselor.study_requests')) ?></span></h2>
        <div style="display:grid;gap:8px;">
            <?php foreach ($requests as $req): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);flex-wrap:wrap;">
                    <div>
                        <strong><?= View::e($req['student_name']) ?></strong> (<code><?= View::e($req['student_code']) ?></code>)
                        <div style="font-size:0.84rem;color:var(--text-muted);"><?= View::e($req['note'] ?? '-') ?></div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <a href="<?= View::e(View::url('/counselor/study-plans?student_id=' . $req['student_id'])) ?>" class="btn btn-primary btn-sm"><?= View::e(Translator::get('counselor.manage_plan')) ?></a>
                        <form method="POST" action="<?= View::e(View::url('/counselor/requests/' . $req['id'] . '/fulfill')) ?>">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('counselor.fulfill_request')) ?></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($chartData)): ?>
    <div class="card" style="margin-bottom:18px;">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('bar-chart', 17) ?><span><?= View::e(Translator::get('counselor.completion_rate')) ?></span></h2>
        <div class="chart-box" data-bar-chart="<?= View::e(json_encode($chartData, JSON_UNESCAPED_UNICODE)) ?>"></div>
    </div>
<?php endif; ?>

<div class="filter-panel">
    <form method="GET" action="<?= View::e(View::url('/counselor/dashboard')) ?>" class="form-row" style="align-items:end;">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label"><?= View::e(Translator::get('common.search')) ?></label>
            <div class="search-field">
                <span class="search-field-icon"><?= Icon::svg('search', 15) ?></span>
                <input class="form-control" name="q" value="<?= View::e($search) ?>" placeholder="<?= View::e(Translator::get('counselor.search_placeholder')) ?>">
            </div>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label"><?= View::e(Translator::get('common.class')) ?></label>
            <select class="form-control" name="class_id">
                <option value="0"><?= View::e(Translator::get('director.all_classes')) ?></option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= View::e((string) $c['id']) ?>" <?= (int) $c['id'] === $classFilter ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><button type="submit" class="btn btn-secondary btn-sm"><?= Icon::svg('filter', 14) ?><span><?= View::e(Translator::get('common.filter')) ?></span></button></div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('vp.student_code')) ?></th>
                    <th><?= View::e(Translator::get('common.student')) ?></th>
                    <th><?= View::e(Translator::get('common.class')) ?></th>
                    <th><?= View::e(Translator::get('counselor.study_plans_count')) ?></th>
                    <th><?= View::e(Translator::get('counselor.completion_rate')) ?></th>
                    <th><?= View::e(Translator::get('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><code><?= View::e($s['student_code']) ?></code></td>
                        <td>
                            <?= View::e($s['full_name']) ?>
                            <?php if ((int) ($s['is_assigned'] ?? 0) > 0): ?>
                                <span class="badge badge-info"><?= View::e(Translator::get('counselor.assigned_students')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= View::e($s['class_name'] ?? '-') ?></td>
                        <td><?= View::e(DateHelper::number($s['plan_count'])) ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="progress" style="width:100px;"><div class="progress-bar" style="width:<?= View::e((string) $s['completion_rate']) ?>%;"></div></div>
                                <span><?= View::e(DateHelper::percent($s['completion_rate'])) ?></span>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <a href="<?= View::e(View::url('/counselor/study-plans?student_id=' . $s['id'])) ?>" class="btn btn-primary btn-sm"><?= View::e(Translator::get('counselor.manage_plan')) ?></a>
                                <a href="<?= View::e(View::url('/counselor/students/' . $s['id'])) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('counselor.view_analysis')) ?></a>
                                <?php if ((int) ($s['is_assigned'] ?? 0) === 0): ?>
                                    <form method="POST" action="<?= View::e(View::url('/counselor/students/assign')) ?>">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="student_id" value="<?= View::e((string) $s['id']) ?>">
                                        <button type="submit" class="btn btn-ghost btn-sm"><?= Icon::svg('plus', 14) ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
