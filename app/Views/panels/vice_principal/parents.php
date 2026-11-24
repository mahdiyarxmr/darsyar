<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$classes = $classes ?? [];
$parents = $parents ?? [];
$students = $students ?? [];
$generated = $generated ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.vice_principal'), 'url' => '/vice-principal/dashboard'], ['label' => Translator::get('menu.parents_bulk')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('bulk_parents.title')) ?></h1></div>
</div>

<div class="grid grid-2" style="margin-bottom:20px;align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('users', 17) ?><span><?= View::e(Translator::get('bulk_parents.button')) ?></span></h2>
        <form method="POST" action="<?= View::e(View::url('/vice-principal/parents/generate-bulk')) ?>">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('bulk_parents.select_class')) ?></label>
                <select class="form-control" name="class_id">
                    <option value="0"><?= View::e(Translator::get('bulk_parents.all_unlinked')) ?></option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= View::e((string) $c['id']) ?>"><?= View::e($c['name']) ?> (<?= View::e($c['grade_name']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-check"><input type="checkbox" name="link_siblings_by_phone" value="1" checked> <span><?= View::e(Translator::get('bulk_parents.link_siblings')) ?></span></label>
            </div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('bulk_parents.button')) ?></button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('plus', 17) ?><span><?= View::e(Translator::get('bulk_parents.link_existing_title')) ?></span></h2>
        <form method="POST" action="<?= View::e(View::url('/vice-principal/parents/link-sibling')) ?>">
            <?= Csrf::field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('common.parent')) ?></label>
                    <select class="form-control" name="parent_id">
                        <?php foreach ($parents as $p): ?>
                            <option value="<?= View::e((string) $p['id']) ?>"><?= View::e($p['full_name']) ?> (<?= View::e($p['username']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('common.student')) ?></label>
                    <select class="form-control" name="student_id">
                        <?php foreach ($students as $s): ?>
                            <option value="<?= View::e((string) $s['id']) ?>"><?= View::e($s['full_name']) ?> (<?= View::e($s['student_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('bulk_parents.relationship')) ?></label>
                <select class="form-control" name="relationship">
                    <option value="father"><?= View::e(Translator::get('status.father')) ?></option>
                    <option value="mother"><?= View::e(Translator::get('status.mother')) ?></option>
                    <option value="guardian"><?= View::e(Translator::get('status.guardian')) ?></option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary"><?= View::e(Translator::get('bulk_parents.link_btn')) ?></button>
        </form>
    </div>
</div>

<?php if (!empty($generated)): ?>
    <div class="card" style="margin-bottom:20px;border-color:var(--primary);">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= View::e(Translator::get('bulk_parents.generated_title')) ?></h2>
                <small style="color:var(--text-muted);"><?= View::e(Translator::get('bulk_parents.export_hint')) ?></small>
            </div>
            <div style="display:flex;gap:8px;">
                <a href="<?= View::e(View::url('/vice-principal/parents/export-csv')) ?>" class="btn btn-success btn-sm"><?= Icon::svg('download', 14) ?><span><?= View::e(Translator::get('common.export_csv')) ?></span></a>
                <a href="<?= View::e(View::url('/vice-principal/parents/print')) ?>" class="btn btn-primary btn-sm" target="_blank"><?= Icon::svg('printer', 14) ?><span><?= View::e(Translator::get('common.print_pdf')) ?></span></a>
            </div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= View::e(Translator::get('vp.student_code')) ?></th>
                        <th><?= View::e(Translator::get('common.student')) ?></th>
                        <th><?= View::e(Translator::get('common.class')) ?></th>
                        <th><?= View::e(Translator::get('common.username')) ?></th>
                        <th><?= View::e(Translator::get('common.password')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($generated as $cred): ?>
                        <tr>
                            <td><code><?= View::e($cred['student_code']) ?></code></td>
                            <td><?= View::e($cred['student_name']) ?></td>
                            <td><?= View::e($cred['class_name']) ?></td>
                            <td><strong><code><?= View::e($cred['parent_username']) ?></code></strong></td>
                            <td><strong><code><?= View::e($cred['is_new'] ? $cred['temporary_password'] : Translator::get('bulk_parents.existing_linked_badge')) ?></code></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('bulk_parents.all_parents')) ?> (<?= View::e(DateHelper::number(count($parents))) ?>)</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.full_name')) ?></th>
                    <th><?= View::e(Translator::get('common.username')) ?></th>
                    <th><?= View::e(Translator::get('common.phone')) ?></th>
                    <th><?= View::e(Translator::get('menu.children')) ?></th>
                    <th><?= View::e(Translator::get('bulk_parents.first_login_status')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parents as $p): ?>
                    <tr>
                        <td><?= View::e($p['full_name']) ?></td>
                        <td><code><?= View::e($p['username']) ?></code></td>
                        <td><?= View::e($p['emergency_phone'] ?? '-') ?></td>
                        <td><?= View::e($p['children_names'] ?? '-') ?></td>
                        <td>
                            <span class="badge <?= (int) $p['must_change_password'] === 1 ? 'badge-warning' : 'badge-success' ?>">
                                <?= View::e(Translator::get((int) $p['must_change_password'] === 1 ? 'bulk_parents.pending_rotation' : 'bulk_parents.verified')) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
