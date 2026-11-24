<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\View;
?>
<div class="container" style="padding:60px 20px;">
    <div class="card" style="max-width:540px;margin:0 auto;text-align:center;padding:34px;">
        <div class="empty-state-icon" style="background:var(--danger-soft);color:var(--danger);margin-bottom:14px;">
            <?= Icon::svg('alert-triangle', 26) ?>
        </div>
        <h1 style="margin:0 0 8px;font-size:1.5rem;"><?= View::e(DateHelper::number(500)) ?> — <?= View::e(Translator::get('errors.server_error_title')) ?></h1>
        <p style="color:var(--text-muted);margin:0 0 20px;"><?= View::e($message ?? Translator::get('errors.server_error')) ?></p>
        <a href="<?= View::e(View::url('/')) ?>" class="btn btn-primary"><?= View::e(Translator::get('nav.home')) ?></a>
    </div>
</div>
