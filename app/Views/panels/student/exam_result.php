<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$exam = $exam ?? [];
$attempt = $attempt ?? null;
$result = $result ?? null;
$answers = $answers ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.student'), 'url' => '/student/dashboard'], ['label' => Translator::get('menu.exams'), 'url' => '/student/exams'], ['label' => Translator::get('student.exam_result_review')]]) ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= View::e($exam['title']) ?></h1>
        <p class="page-subtitle"><?= View::e($exam['subject_name']) ?></p>
    </div>
    <a href="<?= View::e(View::url('/student/exams')) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.back')) ?></a>
</div>

<?php if ($attempt): ?>
    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="stat-card"><span class="stat-label"><?= View::e(Translator::get('common.status')) ?></span><span class="stat-value" style="font-size:1.25rem;"><?= View::e(Ui::statusLabel($attempt['status'])) ?></span></div>
        <div class="stat-card"><span class="stat-label"><?= View::e(Translator::get('common.score')) ?></span><span class="stat-value"><?= View::e(DateHelper::number($attempt['total_score'])) ?> / <?= View::e(DateHelper::number($exam['max_score'])) ?></span></div>
        <div class="stat-card"><span class="stat-label"><?= View::e(Translator::get('common.percentage')) ?></span><span class="stat-value"><?= $result ? View::e(DateHelper::percent($result['percentage'])) : '-' ?></span></div>
    </div>

    <?php if ($attempt['status'] === 'submitted'): ?>
        <div class="alert alert-warning"><?= Icon::svg('clock', 18) ?><span><?= View::e(Translator::get('student.descriptive_pending')) ?></span></div>
    <?php endif; ?>

    <div class="card">
        <?php foreach ($answers as $idx => $ans): ?>
            <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                    <strong><?= View::e(DateHelper::number($idx + 1)) ?>. <?= View::e($ans['prompt']) ?></strong>
                    <?php if ($ans['type'] === 'mcq' && $ans['is_correct'] !== null): ?>
                        <span class="badge <?= (int) $ans['is_correct'] === 1 ? 'badge-success' : 'badge-danger' ?>">
                            <?= View::e(Translator::get((int) $ans['is_correct'] === 1 ? 'student.correct_badge' : 'student.incorrect_badge')) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div style="margin-top:6px;font-size:0.88rem;color:var(--text-muted);">
                    <?php if ($ans['type'] === 'mcq'): ?>
                        <?= View::e(Translator::get('teacher.selected_answer')) ?>: <strong><?= View::e($ans['selected_option_text'] ?? Translator::get('teacher.no_answer')) ?></strong>
                    <?php else: ?>
                        <?= View::e(Translator::get('teacher.answer_text')) ?>: <?= View::e($ans['answer_text'] ?: Translator::get('teacher.no_answer')) ?>
                    <?php endif; ?>
                    · <?= View::e(Translator::get('common.score')) ?>: <strong><?= View::e(DateHelper::number($ans['score_awarded'])) ?> / <?= View::e(DateHelper::number($ans['points'])) ?></strong>
                </div>
                <?php if (!empty($ans['teacher_comment'])): ?>
                    <div style="margin-top:6px;font-size:0.85rem;color:var(--primary-text);"><?= View::e(Translator::get('teacher.teacher_comment')) ?>: <?= View::e($ans['teacher_comment']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
