<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$notifications = $notifications ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('nav.dashboard'), 'url' => '/dashboard'], ['label' => Translator::get('nav.notifications')]]) ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= View::e(Translator::get('nav.notifications')) ?></h1>
    </div>
    <form method="POST" action="<?= View::e(View::url('/notifications/read-all')) ?>">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-secondary btn-sm">
            <?= Icon::svg('check', 15) ?>
            <span><?= View::e(Translator::get('notif.mark_all_read')) ?></span>
        </button>
    </form>
</div>

<div class="card">
    <?php if (empty($notifications)): ?>
        <?= Ui::emptyState(Translator::get('common.no_data'), 'bell') ?>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($notifications as $n): ?>
                <div data-notif-row style="padding:14px;border-radius:var(--radius-sm);border:1px solid var(--border);background:<?= (int) $n['is_read'] === 0 ? 'var(--primary-soft)' : 'var(--surface-2)' ?>;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                    <div>
                        <div style="font-weight:700;margin-bottom:4px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span><?= View::e($n['title']) ?></span>
                            <span class="badge badge-muted"><?= View::e($n['type']) ?></span>
                        </div>
                        <div style="font-size:0.89rem;color:var(--text-muted);"><?= View::e($n['body']) ?></div>
                        <div style="font-size:0.78rem;color:var(--text-muted);margin-top:6px;">
                            <?= View::e(DateHelper::format($n['created_at'])) ?>
                            <?php if (!empty($n['link'])): ?>
                                · <a href="<?= View::e(View::url($n['link'])) ?>"><?= View::e(Translator::get('common.view')) ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ((int) $n['is_read'] === 0): ?>
                        <button type="button" class="btn btn-secondary btn-sm" data-mark-read="<?= View::e(View::url('/api/notifications/' . $n['id'] . '/read')) ?>" aria-label="<?= View::e(Translator::get('notif.mark_read')) ?>">
                            <?= Icon::svg('check', 14) ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
