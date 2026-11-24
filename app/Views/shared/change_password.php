<?php

use App\Core\Csrf;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;
?>
<?= Ui::breadcrumbs([['label' => Translator::get('nav.dashboard'), 'url' => '/dashboard'], ['label' => Translator::get('nav.password')]]) ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= View::e(Translator::get('nav.password')) ?></h1>
    </div>
</div>

<div class="card" style="max-width:500px;">
    <form method="POST" action="<?= View::e(View::url('/account/password')) ?>">
        <?= Csrf::field() ?>
        <div class="form-group">
            <label class="form-label" for="current_password"><?= View::e(Translator::get('auth.current_password')) ?></label>
            <input class="form-control" id="current_password" name="current_password" type="password" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="new_password"><?= View::e(Translator::get('auth.new_password')) ?></label>
            <input class="form-control" id="new_password" name="new_password" type="password" minlength="8" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="confirm_password"><?= View::e(Translator::get('auth.confirm_password')) ?></label>
            <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-primary">
            <?= Icon::svg('key-round', 16) ?>
            <span><?= View::e(Translator::get('common.save')) ?></span>
        </button>
    </form>
</div>
