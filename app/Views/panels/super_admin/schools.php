<?php

use App\Core\Csrf;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$schools = $schools ?? [];
$directors = $directors ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.super_admin'), 'url' => '/admin/dashboard'], ['label' => Translator::get('menu.schools')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('admin.school_mgmt')) ?></h1></div>
    <button type="button" class="btn btn-primary btn-sm" data-drawer-toggle="schoolCreateDrawer" aria-expanded="false">
        <?= Icon::svg('plus', 15) ?>
        <span><?= View::e(Translator::get('admin.create_school')) ?></span>
    </button>
</div>

<div id="schoolCreateDrawer" class="action-drawer">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('building', 17) ?><span><?= View::e(Translator::get('admin.create_school')) ?></span></h2>
            <button type="button" class="btn btn-ghost btn-sm btn-icon" data-drawer-toggle="schoolCreateDrawer" aria-label="<?= View::e(Translator::get('common.close')) ?>"><?= Icon::svg('x', 16) ?></button>
        </div>
        <form method="POST" action="<?= View::e(View::url('/admin/schools/create')) ?>">
            <?= Csrf::field() ?>
            <div class="form-row">
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('admin.school_code')) ?></label><input class="form-control" name="code" placeholder="SCH-002" required></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('admin.school_name')) ?></label><input class="form-control" name="name" required></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('admin.school_city')) ?></label><input class="form-control" name="city"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('admin.school_director')) ?></label>
                    <select class="form-control" name="director_user_id">
                        <option value="">-- <?= View::e(Translator::get('common.optional')) ?> --</option>
                        <?php foreach ($directors as $d): ?>
                            <option value="<?= View::e((string) $d['id']) ?>"><?= View::e($d['full_name']) ?> (<?= View::e($d['username']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.phone')) ?></label><input class="form-control" name="phone"></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.email')) ?></label><input class="form-control" type="email" name="email"></div>
            </div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('admin.school_address')) ?></label><input class="form-control" name="address"></div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.create')) ?></button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.code')) ?></th>
                    <th><?= View::e(Translator::get('admin.school_name')) ?></th>
                    <th><?= View::e(Translator::get('admin.school_director')) ?></th>
                    <th><?= View::e(Translator::get('common.status')) ?></th>
                    <th><?= View::e(Translator::get('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schools)): ?>
                    <tr><td colspan="5"><?= View::e(Translator::get('common.no_data')) ?></td></tr>
                <?php else: foreach ($schools as $sc): ?>
                    <tr>
                        <td><code><?= View::e($sc['code']) ?></code></td>
                        <td>
                            <strong><?= View::e($sc['name']) ?></strong>
                            <?php if (!empty($sc['city'])): ?><div style="font-size:0.8rem;color:var(--text-muted);"><?= View::e($sc['city']) ?></div><?php endif; ?>
                        </td>
                        <td><?= View::e($sc['director_name'] ?? '-') ?></td>
                        <td><span class="badge <?= $sc['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= View::e(Ui::statusLabel($sc['status'])) ?></span></td>
                        <td>
                            <form method="POST" action="<?= View::e(View::url('/admin/schools/' . $sc['id'] . '/toggle')) ?>">
                                <?= Csrf::field() ?>
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <?= View::e(Translator::get($sc['status'] === 'active' ? 'admin.toggle_deactivate' : 'admin.toggle_activate')) ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
