<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\View;
?>
<div class="container" style="padding:60px 20px;">
    <div class="card" style="max-width:500px;margin:0 auto;text-align:center;padding:34px;">
        <div class="empty-state-icon" style="background:var(--danger-soft);color:var(--danger);margin-bottom:14px;">
            <?= Icon::svg('shield-check', 26) ?>
        </div>
        <h1 style="margin:0 0 8px;font-size:1.5rem;"><?= View::e(DateHelper::number(403)) ?> — <?= View::e(Translator::get('errors.forbidden_title')) ?></h1>
        <p style="color:var(--text-muted);margin:0 0 20px;"><?= View::e(Translator::get('errors.forbidden')) ?></p>
        <a href="<?= View::e(View::url('/dashboard')) ?>" class="btn btn-primary"><?= View::e(Translator::get('nav.dashboard')) ?></a>
    </div>
</div>
