<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$items = $items ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.student'), 'url' => '/student/dashboard'], ['label' => Translator::get('menu.homework')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.homework')) ?></h1></div>
</div>

<?php if (empty($items)): ?>
    <div class="card"><?= Ui::emptyState(Translator::get('common.no_data'), 'book-open') ?></div>
<?php else: foreach ($items as $hw): ?>
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= View::e($hw['title']) ?></h2>
                <small style="color:var(--text-muted);"><?= View::e($hw['subject_name']) ?> · <?= View::e(Translator::get('teacher.deadline')) ?>: <?= View::e(DateHelper::format($hw['due_at'])) ?> · <?= View::e(Translator::get('teacher.max_score')) ?>: <?= View::e(DateHelper::number($hw['max_score'])) ?></small>
            </div>
            <span class="badge <?= ($hw['sub_status'] ?? '') === 'accepted' ? 'badge-success' : (($hw['sub_status'] ?? '') === 'needs_revision' ? 'badge-danger' : 'badge-primary') ?>">
                <?= View::e(Ui::statusLabel($hw['sub_status'] ?? 'pending')) ?>
            </span>
        </div>
        <p style="margin-top:0;"><?= View::e($hw['description']) ?></p>
        <?php if (!empty($hw['requirements'])): ?>
            <div style="font-size:0.84rem;color:var(--text-muted);margin-bottom:10px;"><?= View::e(Translator::get('teacher.requirements')) ?>: <?= View::e($hw['requirements']) ?></div>
        <?php endif; ?>
        <?php if (!empty($hw['attachment_path'])): ?>
            <a href="<?= View::e(View::url('/files/homework/' . $hw['id'])) ?>" class="btn btn-secondary btn-sm" style="margin-bottom:12px;"><?= Icon::svg('download', 14) ?><span><?= View::e(Translator::get('student.download_assignment')) ?></span></a>
        <?php endif; ?>

        <?php if ($hw['graded_score'] !== null): ?>
            <div class="alert alert-success">
                <?= Icon::svg('check-circle', 18) ?>
                <div>
                    <strong><?= View::e(Translator::get('common.score')) ?>: <?= View::e(DateHelper::number($hw['graded_score'])) ?> / <?= View::e(DateHelper::number($hw['max_score'])) ?></strong>
                    <?php if (!empty($hw['feedback_text'])): ?> — <?= View::e($hw['feedback_text']) ?><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= View::e(View::url('/student/homework/' . $hw['id'] . '/submit')) ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="form-row" style="align-items:end;">
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('student.your_notes')) ?></label><input class="form-control" name="submission_text" value="<?= View::e((string) ($hw['submission_text'] ?? '')) ?>"></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('student.upload_file')) ?></label><div class="file-upload"><input class="form-control" type="file" name="submission_file" accept=".pdf,.png,.jpg,.jpeg,.webp"></div></div>
                <div class="form-group"><button type="submit" class="btn btn-primary btn-sm"><?= View::e(Translator::get('student.submit_hw_btn')) ?></button></div>
            </div>
        </form>
    </div>
<?php endforeach; endif; ?>
