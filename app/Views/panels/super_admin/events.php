<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$events = $events ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.super_admin'), 'url' => '/admin/dashboard'], ['label' => Translator::get('menu.events')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.events')) ?></h1></div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('calendar', 17) ?><span><?= View::e(Translator::get('admin.create_event')) ?></span></h2>
        <form method="POST" action="<?= View::e(View::url('/admin/events/create')) ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.title')) ?></label>
                <input class="form-control" name="title" required>
            </div>
            <div class="form-row">
                <div class="form-group date-picker-wrap">
                    <label class="form-label"><?= View::e(Translator::get('common.date')) ?></label>
                    <input class="form-control" type="date" name="event_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('admin.location')) ?></label>
                    <input class="form-control" name="location">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.summary')) ?></label>
                <input class="form-control" name="summary">
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.details')) ?></label>
                <textarea class="form-control" name="body" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.cover_image')) ?></label>
                <div class="file-upload"><input class="form-control" type="file" name="cover_image" accept=".png,.jpg,.jpeg,.webp"></div>
            </div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.create')) ?></button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= View::e(Translator::get('admin.events_list')) ?></h2>
        <?php if (empty($events)): ?>
            <?= Ui::emptyState(Translator::get('public.no_events'), 'calendar') ?>
        <?php else: foreach ($events as $ev): ?>
            <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                    <strong><?= View::e($ev['title']) ?></strong>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <span class="badge badge-primary"><?= View::e(DateHelper::format($ev['event_date'], false)) ?></span>
                        <form method="POST" action="<?= View::e(View::url('/admin/events/' . $ev['id'] . '/delete')) ?>" data-confirm="<?= View::e(Translator::get('common.confirm_delete')) ?>">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-ghost btn-sm btn-icon" aria-label="<?= View::e(Translator::get('common.delete')) ?>"><?= Icon::svg('trash', 14) ?></button>
                        </form>
                    </div>
                </div>
                <div style="font-size:0.85rem;color:var(--text-muted);margin-top:4px;"><?= View::e($ev['location'] ?? '') ?> — <?= View::e($ev['summary'] ?? '') ?></div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
