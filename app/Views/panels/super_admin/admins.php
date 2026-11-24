<?php

use App\Core\Csrf;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$admins = $admins ?? [];
$roleFilter = $roleFilter ?? '';
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.super_admin'), 'url' => '/admin/dashboard'], ['label' => Translator::get('menu.admins')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.admins')) ?></h1></div>
    <div class="page-header-actions">
        <form method="GET" action="<?= View::e(View::url('/admin/admins')) ?>" style="display:flex;gap:8px;align-items:center;">
            <select class="form-control" name="role" style="width:auto;min-height:36px;">
                <option value=""><?= View::e(Translator::get('common.all')) ?></option>
                <option value="super_admin" <?= $roleFilter === 'super_admin' ? 'selected' : '' ?>><?= View::e(Translator::get('roles.super_admin')) ?></option>
                <option value="director" <?= $roleFilter === 'director' ? 'selected' : '' ?>><?= View::e(Translator::get('roles.director')) ?></option>
                <option value="vice_principal" <?= $roleFilter === 'vice_principal' ? 'selected' : '' ?>><?= View::e(Translator::get('roles.vice_principal')) ?></option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.filter')) ?></button>
        </form>
        <button type="button" class="btn btn-primary btn-sm" data-drawer-toggle="adminCreateDrawer" aria-expanded="false">
            <?= Icon::svg('plus', 15) ?>
            <span><?= View::e(Translator::get('admin.create_admin')) ?></span>
        </button>
    </div>
</div>

<div id="adminCreateDrawer" class="action-drawer">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('user-plus', 17) ?><span><?= View::e(Translator::get('admin.create_admin')) ?></span></h2>
            <button type="button" class="btn btn-ghost btn-sm btn-icon" data-drawer-toggle="adminCreateDrawer" aria-label="<?= View::e(Translator::get('common.close')) ?>"><?= Icon::svg('x', 16) ?></button>
        </div>
        <form method="POST" action="<?= View::e(View::url('/admin/admins/create')) ?>">
            <?= Csrf::field() ?>
            <div class="form-row">
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.full_name')) ?></label><input class="form-control" name="full_name" required></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.username')) ?></label><input class="form-control" name="username" required></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.email')) ?></label><input class="form-control" type="email" name="email"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('common.role')) ?></label>
                    <select class="form-control" name="role">
                        <option value="director"><?= View::e(Translator::get('roles.director')) ?></option>
                        <option value="vice_principal"><?= View::e(Translator::get('roles.vice_principal')) ?></option>
                        <option value="super_admin"><?= View::e(Translator::get('roles.super_admin')) ?></option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('auth.new_password')) ?></label><input class="form-control" type="password" name="password" minlength="8" required></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.title')) ?></label><input class="form-control" name="title"></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.department')) ?></label><input class="form-control" name="department"></div>
            </div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.create')) ?></button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.full_name')) ?></th>
                    <th><?= View::e(Translator::get('common.username')) ?></th>
                    <th><?= View::e(Translator::get('common.role')) ?></th>
                    <th><?= View::e(Translator::get('common.status')) ?></th>
                    <th><?= View::e(Translator::get('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $a): ?>
                    <tr>
                        <td><?= View::e($a['full_name']) ?></td>
                        <td><code><?= View::e($a['username']) ?></code></td>
                        <td><span class="badge badge-primary"><?= View::e(Translator::get('roles.' . $a['role_slug'])) ?></span></td>
                        <td><span class="badge <?= $a['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= View::e(Ui::statusLabel($a['status'])) ?></span></td>
                        <td>
                            <form method="POST" action="<?= View::e(View::url('/admin/admins/' . $a['id'] . '/toggle')) ?>" style="display:inline;">
                                <?= Csrf::field() ?>
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <?= View::e(Translator::get($a['status'] === 'active' ? 'admin.toggle_deactivate' : 'admin.toggle_activate')) ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
