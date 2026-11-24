<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$attempt = $attempt ?? [];
$answers = $answers ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.teacher'), 'url' => '/teacher/dashboard'], ['label' => Translator::get('menu.exams'), 'url' => '/teacher/exams'], ['label' => Translator::get('teacher.grade_attempt_title')]]) ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= View::e(Translator::get('teacher.grade_attempt_title')) ?>: <?= View::e($attempt['student_name']) ?></h1>
        <p class="page-subtitle"><?= View::e($attempt['exam_title']) ?> · <?= View::e(Translator::get('teacher.current_auto_score')) ?>: <?= View::e(DateHelper::number($attempt['auto_score'])) ?> / <?= View::e(DateHelper::number($attempt['max_score'])) ?></p>
    </div>
</div>

<div class="card">
    <form method="POST" action="<?= View::e(View::url('/teacher/exams/attempt/' . $attempt['id'] . '/grade')) ?>">
        <?= Csrf::field() ?>
        <?php foreach ($answers as $idx => $ans): ?>
            <div style="padding:14px;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:14px;">
                <div style="display:flex;justify-content:space-between;">
                    <strong><?= View::e(DateHelper::number($idx + 1)) ?>. (<?= View::e(Ui::statusLabel($ans['type'])) ?>)</strong>
                    <span class="badge badge-muted"><?= View::e(DateHelper::number($ans['points'])) ?></span>
                </div>
                <p style="margin:8px 0;font-weight:600;"><?= View::e($ans['prompt']) ?></p>
                <?php if ($ans['type'] === 'mcq'): ?>
                    <div><?= View::e(Translator::get('teacher.selected_answer')) ?>: <strong><?= View::e($ans['selected_option_text'] ?? Translator::get('teacher.no_answer')) ?></strong> · <?= View::e(Translator::get('common.score')) ?>: <?= View::e(DateHelper::number($ans['score_awarded'])) ?></div>
                <?php else: ?>
                    <div style="padding:10px;background:var(--surface-2);border-radius:var(--radius-sm);margin-bottom:10px;">
                        <?= View::e($ans['answer_text'] ?: Translator::get('teacher.no_answer')) ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?= View::e(Translator::get('teacher.score_awarded')) ?> (0 - <?= View::e(DateHelper::number($ans['points'])) ?>)</label>
                            <input class="form-control" type="number" step="0.25" min="0" max="<?= View::e((string) $ans['points']) ?>" name="scores[<?= View::e((string) $ans['id']) ?>]" value="<?= View::e((string) $ans['score_awarded']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= View::e(Translator::get('teacher.teacher_comment')) ?></label>
                            <input class="form-control" name="comments[<?= View::e((string) $ans['id']) ?>]" value="<?= View::e((string) ($ans['teacher_comment'] ?? '')) ?>">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('teacher.save_final_grade')) ?></button>
    </form>
</div>
