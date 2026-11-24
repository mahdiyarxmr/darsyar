<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\View;
?>
<div class="container" style="padding:60px 20px;">
    <div class="card" style="max-width:500px;margin:0 auto;text-align:center;padding:34px;">
        <div class="empty-state-icon" style="margin-bottom:14px;">
            <?= Icon::svg('search', 26) ?>
        </div>
        <h1 style="margin:0 0 8px;font-size:1.5rem;"><?= View::e(DateHelper::number(404)) ?> — <?= View::e(Translator::get('errors.not_found_title')) ?></h1>
        <p style="color:var(--text-muted);margin:0 0 20px;"><?= View::e(Translator::get('errors.not_found')) ?></p>
        <a href="<?= View::e(View::url('/')) ?>" class="btn btn-primary"><?= View::e(Translator::get('nav.home')) ?></a>
    </div>
</div>
