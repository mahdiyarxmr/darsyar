<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$exam = $exam ?? [];
$attempt = $attempt ?? [];
$questions = $questions ?? [];
$expiresTs = strtotime((string) ($attempt['expires_at'] ?? 'now'));
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.student'), 'url' => '/student/dashboard'], ['label' => Translator::get('menu.exams'), 'url' => '/student/exams'], ['label' => $exam['title'] ?? '']]) ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= View::e($exam['title']) ?></h1>
        <p class="page-subtitle"><?= View::e($exam['subject_name']) ?> · <?= View::e(Translator::get('teacher.max_score')) ?>: <?= View::e(DateHelper::number($exam['max_score'])) ?></p>
    </div>
    <div class="card" style="padding:10px 18px;border-color:var(--primary);">
        <span style="font-size:0.78rem;color:var(--text-muted);display:flex;align-items:center;gap:6px;"><?= Icon::svg('clock', 14) ?><span><?= View::e(Translator::get('student.time_remaining')) ?></span></span>
        <div style="font-size:1.3rem;font-weight:800;color:var(--primary-text);font-variant-numeric:tabular-nums;" data-exam-expires="<?= View::e((string) $expiresTs) ?>">--:--</div>
    </div>
</div>

<form id="examTakeForm" method="POST" action="<?= View::e(View::url('/student/exams/' . $exam['id'] . '/submit')) ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="attempt_id" value="<?= View::e((string) $attempt['id']) ?>">

    <?php foreach ($questions as $idx => $q): ?>
        <div class="card" style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <strong><?= View::e(DateHelper::number($idx + 1)) ?>.</strong>
                <span class="badge badge-muted"><?= View::e(DateHelper::number($q['points'])) ?></span>
            </div>
            <p style="margin-top:0;font-weight:600;"><?= View::e($q['prompt']) ?></p>

            <?php if ($q['type'] === 'mcq'): ?>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ($q['options'] as $opt): ?>
                        <label class="radio-card">
                            <input type="radio" name="answers[<?= View::e((string) $q['id']) ?>]" value="<?= View::e((string) $opt['id']) ?>">
                            <span><?= View::e($opt['option_text']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <textarea class="form-control" name="answers[<?= View::e((string) $q['id']) ?>]" rows="4" placeholder="<?= View::e(Translator::get('student.answer_placeholder')) ?>"></textarea>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary"><?= Icon::svg('check', 16) ?><span><?= View::e(Translator::get('student.submit_exam_btn')) ?></span></button>
</form>
