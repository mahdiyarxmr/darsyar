<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$students = $students ?? [];
$notes = $notes ?? [];
$studentFilter = $studentFilter ?? 0;
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.counselor'), 'url' => '/counselor/dashboard'], ['label' => Translator::get('menu.counseling_notes')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('counselor.counseling_notes')) ?></h1></div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('notebook', 17) ?><span><?= View::e(Translator::get('counselor.add_note')) ?></span></h2>
        <form method="POST" action="<?= View::e(View::url('/counselor/notes/create')) ?>">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.student')) ?></label>
                <select class="form-control" name="student_id" required>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= View::e((string) $s['id']) ?>" <?= (int) $s['id'] === $studentFilter ? 'selected' : '' ?>>
                            <?= View::e($s['full_name']) ?> (<?= View::e($s['student_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('counselor.note_title')) ?></label><input class="form-control" name="title" required></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('counselor.note_body')) ?></label><textarea class="form-control" name="note" rows="4" required></textarea></div>
            <div class="form-group date-picker-wrap"><label class="form-label"><?= View::e(Translator::get('counselor.follow_up_date')) ?></label><input class="form-control" type="date" name="follow_up_date"></div>
            <div class="form-group">
                <label class="form-check"><input type="checkbox" name="visible_to_parent" value="1" checked> <span><?= View::e(Translator::get('counselor.visible_to_parent')) ?></span></label>
            </div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.save')) ?></button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= View::e(Translator::get('counselor.counseling_notes')) ?></h2>
        <?php if (empty($notes)): ?>
            <?= Ui::emptyState(Translator::get('common.no_data'), 'notebook') ?>
        <?php else: foreach ($notes as $n): ?>
            <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
                    <strong><?= View::e($n['title']) ?> — <?= View::e($n['student_name']) ?></strong>
                    <?php if ((int) $n['visible_to_parent'] === 1): ?>
                        <span class="badge badge-info"><?= View::e(Translator::get('counselor.visible_to_parent')) ?></span>
                    <?php endif; ?>
                </div>
                <p style="margin:6px 0;font-size:0.88rem;color:var(--text-muted);"><?= View::e($n['note']) ?></p>
                <div style="font-size:0.78rem;color:var(--text-muted);">
                    <?= View::e(DateHelper::format($n['created_at'])) ?>
                    <?php if (!empty($n['follow_up_date'])): ?>
                        · <?= View::e(Translator::get('counselor.follow_up_date')) ?>: <?= View::e(DateHelper::format($n['follow_up_date'], false)) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
