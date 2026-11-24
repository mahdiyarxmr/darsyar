<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$students = $students ?? [];
$classes = $classes ?? [];
$q = $q ?? '';
$classFilter = $classFilter ?? 0;
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.vice_principal'), 'url' => '/vice-principal/dashboard'], ['label' => Translator::get('menu.students_bulk')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.students_bulk')) ?></h1></div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-primary btn-sm" data-drawer-toggle="studentCreateDrawer" aria-expanded="false">
            <?= Icon::svg('plus', 15) ?>
            <span><?= View::e(Translator::get('vp.register_student')) ?></span>
        </button>
        <button type="button" class="btn btn-secondary btn-sm" data-drawer-toggle="studentCsvDrawer" aria-expanded="false">
            <?= Icon::svg('upload', 15) ?>
            <span><?= View::e(Translator::get('vp.bulk_import_csv')) ?></span>
        </button>
        <a href="<?= View::e(View::url('/vice-principal/parents')) ?>" class="btn btn-secondary btn-sm">
            <?= Icon::svg('users', 15) ?>
            <span><?= View::e(Translator::get('bulk_parents.button')) ?></span>
        </a>
    </div>
</div>

<div id="studentCreateDrawer" class="action-drawer">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('user-plus', 17) ?><span><?= View::e(Translator::get('vp.register_student')) ?></span></h2>
            <button type="button" class="btn btn-ghost btn-sm btn-icon" data-drawer-toggle="studentCreateDrawer" aria-label="<?= View::e(Translator::get('common.close')) ?>"><?= Icon::svg('x', 16) ?></button>
        </div>
        <form method="POST" action="<?= View::e(View::url('/vice-principal/students/create')) ?>">
            <?= Csrf::field() ?>
            <div class="form-row">
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.full_name')) ?></label><input class="form-control" name="full_name" required></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.username')) ?></label><input class="form-control" name="username" required></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('vp.student_code')) ?></label><input class="form-control" name="student_code" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('vp.guardian_phone')) ?></label><input class="form-control" name="guardian_phone"></div>
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('common.class')) ?></label>
                    <select class="form-control" name="class_id">
                        <?php foreach ($classes as $c): ?><option value="<?= View::e((string) $c['id']) ?>"><?= View::e($c['name']) ?> (<?= View::e($c['grade_name']) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.password')) ?></label><input class="form-control" type="password" name="password" minlength="6" required></div>
            </div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.create')) ?></button>
        </form>
    </div>
</div>

<div id="studentCsvDrawer" class="action-drawer">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('upload', 17) ?><span><?= View::e(Translator::get('vp.bulk_import_csv')) ?></span></h2>
            <a href="<?= View::e(View::url('/vice-principal/students/sample-csv')) ?>" class="btn btn-secondary btn-sm"><?= Icon::svg('download', 14) ?><span><?= View::e(Translator::get('vp.download_sample_csv')) ?></span></a>
        </div>
        <form method="POST" action="<?= View::e(View::url('/vice-principal/students/import')) ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="form-row" style="align-items:end;">
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('vp.target_class')) ?></label>
                    <select class="form-control" name="class_id">
                        <?php foreach ($classes as $c): ?><option value="<?= View::e((string) $c['id']) ?>"><?= View::e($c['name']) ?> (<?= View::e($c['grade_name']) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('vp.csv_file')) ?></label>
                    <div class="file-upload"><input class="form-control" type="file" name="csv_file" accept=".csv,text/csv" required></div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-success"><?= View::e(Translator::get('vp.import_btn')) ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="filter-panel">
    <form method="GET" action="<?= View::e(View::url('/vice-principal/students')) ?>" class="form-row" style="align-items:end;">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label"><?= View::e(Translator::get('common.search')) ?></label>
            <div class="search-field">
                <span class="search-field-icon"><?= Icon::svg('search', 15) ?></span>
                <input class="form-control" name="q" value="<?= View::e($q) ?>" placeholder="<?= View::e(Translator::get('counselor.search_placeholder')) ?>">
            </div>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label"><?= View::e(Translator::get('common.class')) ?></label>
            <select class="form-control" name="class_id">
                <option value="0"><?= View::e(Translator::get('director.all_classes')) ?></option>
                <?php foreach ($classes as $c): ?><option value="<?= View::e((string) $c['id']) ?>" <?= (int) $c['id'] === $classFilter ? 'selected' : '' ?>><?= View::e($c['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-secondary btn-sm"><?= Icon::svg('filter', 14) ?><span><?= View::e(Translator::get('common.filter')) ?></span></button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= View::e(Translator::get('vp.registered_students')) ?></h2>
        <span class="badge badge-muted"><?= View::e(DateHelper::number(count($students))) ?></span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('vp.student_code')) ?></th>
                    <th><?= View::e(Translator::get('common.full_name')) ?></th>
                    <th><?= View::e(Translator::get('common.username')) ?></th>
                    <th><?= View::e(Translator::get('common.class')) ?></th>
                    <th><?= View::e(Translator::get('vp.guardian_phone')) ?></th>
                    <th><?= View::e(Translator::get('vp.parent_linked')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><code><?= View::e($s['student_code']) ?></code></td>
                        <td><strong><?= View::e($s['full_name']) ?></strong></td>
                        <td><?= View::e($s['username']) ?></td>
                        <td>
                            <form method="POST" action="<?= View::e(View::url('/vice-principal/students/' . $s['id'] . '/class')) ?>" style="display:flex;gap:6px;align-items:center;">
                                <?= Csrf::field() ?>
                                <select class="form-control" name="class_id" style="min-height:34px;padding:3px 8px;width:auto;">
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= View::e((string) $c['id']) ?>" <?= (int) $c['id'] === (int) $s['class_id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.save')) ?></button>
                            </form>
                        </td>
                        <td dir="ltr"><?= View::e($s['guardian_phone'] ?? '-') ?></td>
                        <td>
                            <span class="badge <?= (int) $s['parent_count'] > 0 ? 'badge-success' : 'badge-warning' ?>">
                                <?= (int) $s['parent_count'] > 0 ? View::e(Translator::get('vp.linked_count', ['count' => DateHelper::number($s['parent_count'])])) : View::e(Translator::get('vp.unlinked')) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
