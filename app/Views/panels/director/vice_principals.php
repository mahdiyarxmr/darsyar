<?php

use App\Core\Csrf;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$vicePrincipals = $vicePrincipals ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.director'), 'url' => '/director/dashboard'], ['label' => Translator::get('menu.vice_principals')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.vice_principals')) ?></h1></div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('plus', 17) ?><span><?= View::e(Translator::get('director.create_vp')) ?></span></h2>
        <form method="POST" action="<?= View::e(View::url('/director/vice-principals/create')) ?>">
            <?= Csrf::field() ?>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.full_name')) ?></label><input class="form-control" name="full_name" required></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.username')) ?></label><input class="form-control" name="username" required></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.email')) ?></label><input class="form-control" type="email" name="email"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.department')) ?></label><input class="form-control" name="department"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('auth.new_password')) ?></label><input class="form-control" type="password" name="password" minlength="8" required></div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.create')) ?></button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('director.vp_list')) ?></h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= View::e(Translator::get('common.full_name')) ?></th>
                        <th><?= View::e(Translator::get('common.username')) ?></th>
                        <th><?= View::e(Translator::get('common.department')) ?></th>
                        <th><?= View::e(Translator::get('common.status')) ?></th>
                        <th><?= View::e(Translator::get('common.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vicePrincipals as $vp): ?>
                        <tr>
                            <td><?= View::e($vp['full_name']) ?></td>
                            <td><code><?= View::e($vp['username']) ?></code></td>
                            <td><?= View::e($vp['department'] ?? '-') ?></td>
                            <td><span class="badge <?= $vp['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= View::e(Ui::statusLabel($vp['status'])) ?></span></td>
                            <td>
                                <form method="POST" action="<?= View::e(View::url('/director/vice-principals/' . $vp['id'] . '/toggle')) ?>">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        <?= View::e(Translator::get($vp['status'] === 'active' ? 'admin.toggle_deactivate' : 'admin.toggle_activate')) ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
