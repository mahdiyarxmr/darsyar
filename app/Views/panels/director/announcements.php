<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$announcements = $announcements ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.director'), 'url' => '/director/dashboard'], ['label' => Translator::get('menu.announcements')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.announcements')) ?></h1></div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('megaphone', 17) ?><span><?= View::e(Translator::get('admin.publish_announcement')) ?></span></h2>
        <form method="POST" action="<?= View::e(View::url('/director/announcements/create')) ?>">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.title')) ?></label>
                <input class="form-control" name="title" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.audience')) ?></label>
                <select class="form-control" name="target_role">
                    <option value="all"><?= View::e(Translator::get('admin.audience_all')) ?></option>
                    <option value="teachers"><?= View::e(Translator::get('admin.audience_teachers')) ?></option>
                    <option value="students"><?= View::e(Translator::get('admin.audience_students')) ?></option>
                    <option value="parents"><?= View::e(Translator::get('admin.audience_parents')) ?></option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.description')) ?></label>
                <textarea class="form-control" name="body" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.save')) ?></button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= View::e(Translator::get('admin.announcements_history')) ?></h2>
        <?php if (empty($announcements)): ?>
            <?= Ui::emptyState(Translator::get('common.no_data'), 'megaphone') ?>
        <?php else: foreach ($announcements as $a): ?>
            <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
                    <strong><?= View::e($a['title']) ?></strong>
                    <span class="badge badge-primary"><?= View::e(Translator::get('admin.audience_' . ($a['target_role'] === 'class' ? 'all' : $a['target_role']))) ?></span>
                </div>
                <p style="margin:6px 0;color:var(--text-muted);font-size:0.88rem;"><?= View::e($a['body']) ?></p>
                <small style="color:var(--text-muted);"><?= View::e(DateHelper::format($a['published_at'])) ?></small>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
