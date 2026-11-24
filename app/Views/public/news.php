<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$posts = $posts ?? [];
?>
<div class="container" style="padding:32px 20px;">
    <?= Ui::breadcrumbs([['label' => Translator::get('nav.home'), 'url' => '/'], ['label' => Translator::get('public.all_news')]]) ?>
    <div class="page-header">
        <div><h1 class="page-title"><?= View::e(Translator::get('public.school_news')) ?></h1></div>
    </div>

    <?php if (empty($posts)): ?>
        <div class="card"><?= Ui::emptyState(Translator::get('public.no_news'), 'newspaper') ?></div>
    <?php else: ?>
        <div class="grid grid-2">
            <?php foreach ($posts as $p): ?>
                <article class="card">
                    <?php if (!empty($p['image_path'])): ?>
                        <img src="<?= View::e(View::url($p['image_path'])) ?>" alt="<?= View::e($p['title']) ?>" style="width:100%;height:190px;object-fit:cover;border-radius:var(--radius-sm);margin-bottom:12px;">
                    <?php endif; ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px;">
                        <span class="badge badge-primary"><?= View::e(Ui::statusLabel((string) $p['type'])) ?></span>
                        <small style="color:var(--text-muted);"><?= View::e(DateHelper::format($p['created_at'], false)) ?></small>
                    </div>
                    <h2 class="card-title" style="margin-bottom:6px;"><?= View::e($p['title']) ?></h2>
                    <p style="margin:0;color:var(--text-muted);font-size:0.89rem;"><?= View::e($p['body'] ?: $p['summary']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
