<?php

use App\Core\Csrf;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$teachers = $teachers ?? [];
$counselors = $counselors ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.vice_principal'), 'url' => '/vice-principal/dashboard'], ['label' => Translator::get('menu.users_staff')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.users_staff')) ?></h1></div>
</div>

<div class="card" style="margin-bottom:20px;">
    <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('plus', 17) ?><span><?= View::e(Translator::get('vp.create_staff')) ?></span></h2>
    <form method="POST" action="<?= View::e(View::url('/vice-principal/staff/create')) ?>">
        <?= Csrf::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.role')) ?></label>
                <select class="form-control" name="role">
                    <option value="teacher"><?= View::e(Translator::get('roles.teacher')) ?></option>
                    <option value="counselor"><?= View::e(Translator::get('roles.counselor')) ?></option>
                </select>
            </div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.full_name')) ?></label><input class="form-control" name="full_name" required></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.username')) ?></label><input class="form-control" name="username" required></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('vp.employee_code')) ?></label><input class="form-control" name="employee_code" required></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('vp.specialization')) ?></label><input class="form-control" name="specialization"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('auth.new_password')) ?></label><input class="form-control" type="password" name="password" minlength="8" required></div>
        </div>
        <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.create')) ?></button>
    </form>
</div>

<div class="grid grid-2">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('vp.teachers_list')) ?></h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= View::e(Translator::get('common.code')) ?></th><th><?= View::e(Translator::get('common.full_name')) ?></th><th><?= View::e(Translator::get('common.username')) ?></th><th><?= View::e(Translator::get('common.status')) ?></th><th><?= View::e(Translator::get('common.actions')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($teachers as $t): ?>
                        <tr>
                            <td><code><?= View::e($t['employee_code']) ?></code></td>
                            <td><?= View::e($t['full_name']) ?></td>
                            <td><?= View::e($t['username']) ?></td>
                            <td><span class="badge <?= $t['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= View::e(Ui::statusLabel($t['status'])) ?></span></td>
                            <td>
                                <form method="POST" action="<?= View::e(View::url('/vice-principal/staff/' . $t['user_id'] . '/toggle')) ?>">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-secondary btn-sm"><?= View::e(Translator::get($t['status'] === 'active' ? 'admin.toggle_deactivate' : 'admin.toggle_activate')) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('vp.counselors_list')) ?></h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= View::e(Translator::get('common.code')) ?></th><th><?= View::e(Translator::get('common.full_name')) ?></th><th><?= View::e(Translator::get('common.username')) ?></th><th><?= View::e(Translator::get('common.status')) ?></th><th><?= View::e(Translator::get('common.actions')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($counselors as $c): ?>
                        <tr>
                            <td><code><?= View::e($c['employee_code']) ?></code></td>
                            <td><?= View::e($c['full_name']) ?></td>
                            <td><?= View::e($c['username']) ?></td>
                            <td><span class="badge <?= $c['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= View::e(Ui::statusLabel($c['status'])) ?></span></td>
                            <td>
                                <form method="POST" action="<?= View::e(View::url('/vice-principal/staff/' . $c['user_id'] . '/toggle')) ?>">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-secondary btn-sm"><?= View::e(Translator::get($c['status'] === 'active' ? 'admin.toggle_deactivate' : 'admin.toggle_activate')) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
