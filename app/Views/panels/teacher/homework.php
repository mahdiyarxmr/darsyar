<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$assignments = $assignments ?? [];
$homeworks = $homeworks ?? [];
$submissions = $submissions ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.teacher'), 'url' => '/teacher/dashboard'], ['label' => Translator::get('menu.homework')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.homework')) ?></h1></div>
    <button type="button" class="btn btn-primary btn-sm" data-drawer-toggle="hwCreateDrawer" aria-expanded="false">
        <?= Icon::svg('plus', 15) ?>
        <span><?= View::e(Translator::get('teacher.create_hw')) ?></span>
    </button>
</div>

<div id="hwCreateDrawer" class="action-drawer">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('book-open', 17) ?><span><?= View::e(Translator::get('teacher.create_hw')) ?></span></h2>
            <button type="button" class="btn btn-ghost btn-sm btn-icon" data-drawer-toggle="hwCreateDrawer" aria-label="<?= View::e(Translator::get('common.close')) ?>"><?= Icon::svg('x', 16) ?></button>
        </div>
        <form method="POST" action="<?= View::e(View::url('/teacher/homework/create')) ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('teacher.class_subject')) ?></label>
                    <select class="form-control" name="assignment_pair" required>
                        <?php foreach ($assignments as $a): ?>
                            <option value="<?= View::e($a['class_id'] . ':' . $a['subject_id']) ?>"><?= View::e($a['class_name'] . ' — ' . $a['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.title')) ?></label><input class="form-control" name="title" required></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.deadline')) ?></label><input class="form-control" type="datetime-local" name="due_at" value="<?= date('Y-m-d\TH:i', strtotime('+3 days')) ?>" required></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.max_score')) ?></label><input class="form-control" type="number" step="0.5" name="max_score" value="20"></div>
            </div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.description')) ?></label><textarea class="form-control" name="description" rows="2" required></textarea></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.requirements')) ?></label><input class="form-control" name="requirements"></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.attachment_hint')) ?></label><div class="file-upload"><input class="form-control" type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.webp"></div></div>
            </div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.create')) ?></button>
        </form>
    </div>
</div>

<div class="grid grid-2" style="margin-bottom:20px;align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('teacher.published_hw')) ?></h2>
        <?php if (empty($homeworks)): ?>
            <?= Ui::emptyState(Translator::get('common.no_data'), 'book-open') ?>
        <?php else: foreach ($homeworks as $h): ?>
            <div style="padding:11px 0;border-bottom:1px solid var(--border-subtle);">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <strong><?= View::e($h['title']) ?></strong>
                    <span class="badge badge-primary"><?= View::e(DateHelper::number($h['sub_count'])) ?> <?= View::e(Translator::get('teacher.submissions_count')) ?></span>
                </div>
                <div style="font-size:0.84rem;color:var(--text-muted);margin-top:3px;"><?= View::e($h['class_name'] . ' · ' . $h['subject_name']) ?> · <?= View::e(DateHelper::format($h['due_at'])) ?></div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('teacher.review_submissions')) ?></h2>
        <?php if (empty($submissions)): ?>
            <?= Ui::emptyState(Translator::get('common.no_data'), 'book-open') ?>
        <?php else: foreach ($submissions as $s): ?>
            <div style="padding:12px;border:1px solid var(--border-subtle);border-radius:var(--radius-sm);margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
                    <div><strong><?= View::e($s['student_name']) ?></strong> — <em><?= View::e($s['hw_title']) ?></em></div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <span class="badge badge-primary"><?= View::e(Ui::statusLabel($s['status'])) ?></span>
                        <?php if (!empty($s['attachment_path'])): ?>
                            <a href="<?= View::e(View::url('/files/submission/' . $s['id'])) ?>" class="btn btn-secondary btn-sm"><?= Icon::svg('download', 14) ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($s['submission_text'])): ?>
                    <p style="margin:6px 0;color:var(--text-muted);font-size:0.86rem;"><?= View::e($s['submission_text']) ?></p>
                <?php endif; ?>
                <form method="POST" action="<?= View::e(View::url('/teacher/homework/submission/' . $s['id'] . '/grade')) ?>" class="form-row" style="align-items:end;">
                    <?= Csrf::field() ?>
                    <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.score')) ?></label><input class="form-control" type="number" step="0.5" name="score" value="<?= View::e((string) ($s['graded_score'] ?? $s['max_score'])) ?>"></div>
                    <div class="form-group">
                        <label class="form-label"><?= View::e(Translator::get('teacher.decision')) ?></label>
                        <select class="form-control" name="decision">
                            <option value="accepted" <?= ($s['decision'] ?? '') === 'accepted' ? 'selected' : '' ?>><?= View::e(Translator::get('status.accepted')) ?></option>
                            <option value="needs_revision" <?= ($s['decision'] ?? '') === 'needs_revision' ? 'selected' : '' ?>><?= View::e(Translator::get('status.needs_revision')) ?></option>
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label"><?= View::e(Translator::get('parent.teacher_feedback')) ?></label><input class="form-control" name="feedback_text" value="<?= View::e((string) ($s['feedback_text'] ?? '')) ?>"></div>
                    <div class="form-group"><button type="submit" class="btn btn-success btn-sm"><?= View::e(Translator::get('teacher.save_grade')) ?></button></div>
                </form>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
