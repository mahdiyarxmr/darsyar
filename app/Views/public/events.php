<?php

use App\Core\DateHelper;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$events = $events ?? [];
$gallery = $gallery ?? [];
?>
<div class="container" style="padding:32px 20px;">
    <?= Ui::breadcrumbs([['label' => Translator::get('nav.home'), 'url' => '/'], ['label' => Translator::get('public.all_events')]]) ?>
    <div class="page-header">
        <div><h1 class="page-title"><?= View::e(Translator::get('public.upcoming_events')) ?></h1></div>
    </div>

    <?php if (empty($events)): ?>
        <div class="card"><?= Ui::emptyState(Translator::get('public.no_events'), 'calendar') ?></div>
    <?php else: ?>
        <div class="grid grid-2" style="margin-bottom:28px;">
            <?php foreach ($events as $ev): ?>
                <article class="card">
                    <?php if (!empty($ev['cover_image'])): ?>
                        <img src="<?= View::e(View::url($ev['cover_image'])) ?>" alt="<?= View::e($ev['title']) ?>" style="width:100%;height:190px;object-fit:cover;border-radius:var(--radius-sm);margin-bottom:12px;">
                    <?php endif; ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px;">
                        <span class="badge badge-primary"><?= View::e(DateHelper::format($ev['event_date'], false)) ?></span>
                        <?php if (!empty($ev['location'])): ?>
                            <small style="color:var(--text-muted);"><?= View::e($ev['location']) ?></small>
                        <?php endif; ?>
                    </div>
                    <h2 class="card-title" style="margin-bottom:6px;"><?= View::e($ev['title']) ?></h2>
                    <p style="margin:0;color:var(--text-muted);font-size:0.89rem;"><?= View::e($ev['body'] ?: $ev['summary']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($gallery)): ?>
        <div class="card">
            <h2 class="card-title" style="margin-bottom:14px;"><?= View::e(Translator::get('public.event_gallery')) ?></h2>
            <div class="grid grid-4" style="gap:12px;">
                <?php foreach ($gallery as $img): ?>
                    <img src="<?= View::e(View::url('/media/' . $img['id'])) ?>" alt="<?= View::e($img['alt_text'] ?: $img['title']) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border);">
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
