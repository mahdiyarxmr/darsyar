<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$groups = $groups ?? [];
$assignments = $assignments ?? [];
$activeGroupId = $activeGroupId ?? 0;
$messages = $messages ?? [];
$role = $role ?? '';
?>
<?= Ui::breadcrumbs([['label' => Translator::get('nav.dashboard'), 'url' => '/dashboard'], ['label' => Translator::get('nav.discussions')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('nav.discussions')) ?></h1></div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <div>
        <?php if ($role === 'teacher'): ?>
            <div class="card" style="margin-bottom:18px;">
                <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('plus', 17) ?><span><?= View::e(Translator::get('discussions.create_group')) ?></span></h2>
                <form method="POST" action="<?= View::e(View::url('/discussions/create')) ?>">
                    <?= Csrf::field() ?>
                    <div class="form-group">
                        <label class="form-label"><?= View::e(Translator::get('teacher.class_subject')) ?></label>
                        <select class="form-control" name="assignment_pair" required>
                            <?php foreach ($assignments as $a): ?>
                                <option value="<?= View::e($a['class_id'] . ':' . $a['subject_id']) ?>"><?= View::e($a['class_name'] . ' — ' . $a['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label"><?= View::e(Translator::get('discussions.group_title')) ?></label><input class="form-control" name="title" required></div>
                    <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.description')) ?></label><input class="form-control" name="description"></div>
                    <button type="submit" class="btn btn-primary btn-sm"><?= View::e(Translator::get('common.create')) ?></button>
                </form>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('message-square', 17) ?><span><?= View::e(Translator::get('discussions.groups_list')) ?></span></h2>
            <?php if (empty($groups)): ?>
                <?= Ui::emptyState(Translator::get('common.no_data'), 'message-square') ?>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ($groups as $g): ?>
                        <a href="<?= View::e(View::url('/discussions?group_id=' . $g['id'])) ?>" class="nav-item <?= (int) $g['id'] === $activeGroupId ? 'active' : '' ?>" style="border:1px solid var(--border);">
                            <div>
                                <div style="font-weight:700;"><?= View::e($g['title']) ?></div>
                                <small style="color:var(--text-muted);"><?= View::e($g['class_name'] . ' · ' . $g['subject_name']) ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('discussions.messages_title')) ?></h2>
        <?php if ($activeGroupId > 0): ?>
            <div style="max-height:380px;overflow-y:auto;display:flex;flex-direction:column;gap:10px;margin-bottom:14px;">
                <?php if (empty($messages)): ?>
                    <?= Ui::emptyState(Translator::get('discussions.no_messages'), 'message-square') ?>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                        <div style="padding:10px 12px;border-radius:var(--radius-sm);background:var(--surface-2);border:1px solid var(--border);">
                            <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:4px;">
                                <strong><?= View::e($m['full_name']) ?></strong>
                                <span style="color:var(--text-muted);"><?= View::e(DateHelper::format($m['created_at'])) ?></span>
                            </div>
                            <div style="font-size:0.9rem;"><?= View::e($m['message']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form method="POST" action="<?= View::e(View::url('/discussions/' . $activeGroupId . '/messages')) ?>" style="display:flex;gap:8px;">
                <?= Csrf::field() ?>
                <input class="form-control" name="message" placeholder="<?= View::e(Translator::get('discussions.write_message')) ?>" required>
                <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.submit')) ?></button>
            </form>
        <?php else: ?>
            <?= Ui::emptyState(Translator::get('discussions.select_group'), 'message-square') ?>
        <?php endif; ?>
    </div>
</div>
