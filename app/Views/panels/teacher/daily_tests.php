<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$tests = $tests ?? [];
$subjects = $subjects ?? [];
$assignments = $assignments ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.teacher'), 'url' => '/teacher/dashboard'], ['label' => Translator::get('menu.daily_tests')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.daily_tests')) ?></h1></div>
</div>

<div class="card" style="margin-bottom:20px;">
    <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('plus', 17) ?><span><?= View::e(Translator::get('teacher.create_daily_test')) ?></span></h2>
    <form method="POST" action="<?= View::e(View::url('/teacher/daily-tests/create')) ?>">
        <?= Csrf::field() ?>
        <div class="form-row">
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.title')) ?></label><input class="form-control" name="title" required></div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.subject')) ?></label>
                <select class="form-control" name="subject_id" required>
                    <?php foreach ($subjects as $s): ?><option value="<?= View::e((string) $s['id']) ?>"><?= View::e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.class')) ?></label>
                <select class="form-control" name="class_id">
                    <option value=""><?= View::e(Translator::get('common.all')) ?></option>
                    <?php foreach ($assignments as $a): ?><option value="<?= View::e((string) $a['class_id']) ?>"><?= View::e($a['class_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.first_question_opt')) ?></label><input class="form-control" name="prompt"></div>
        <div class="form-row">
            <div class="form-group"><input class="form-control" name="opt_0" placeholder="<?= View::e(Translator::get('teacher.opt_a')) ?>"></div>
            <div class="form-group"><input class="form-control" name="opt_1" placeholder="<?= View::e(Translator::get('teacher.opt_b')) ?>"></div>
            <div class="form-group"><input class="form-control" name="opt_2" placeholder="<?= View::e(Translator::get('teacher.opt_c')) ?>"></div>
            <div class="form-group">
                <select class="form-control" name="correct_index">
                    <option value="0"><?= View::e(Translator::get('teacher.opt_a')) ?></option>
                    <option value="1"><?= View::e(Translator::get('teacher.opt_b')) ?></option>
                    <option value="2"><?= View::e(Translator::get('teacher.opt_c')) ?></option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.create')) ?></button>
    </form>
</div>

<?php foreach ($tests as $t): ?>
    <div class="card" style="margin-bottom:14px;">
        <div class="card-header">
            <div>
                <h3 class="card-title"><?= View::e($t['title']) ?></h3>
                <small style="color:var(--text-muted);"><?= View::e($t['subject_name']) ?> · <?= View::e($t['class_name'] ?? Translator::get('common.all')) ?></small>
            </div>
            <span class="badge badge-primary"><?= View::e(DateHelper::number($t['q_count'])) ?> <?= View::e(Translator::get('student.questions_count')) ?></span>
        </div>
        <?php if (!empty($t['questions'])): ?>
            <div style="margin-bottom:12px;display:grid;gap:6px;">
                <?php foreach ($t['questions'] as $qi => $q): ?>
                    <div style="font-size:0.85rem;padding:8px 10px;background:var(--surface-2);border-radius:var(--radius-xs);">
                        <strong><?= View::e(DateHelper::number($qi + 1)) ?>.</strong> <?= View::e($q['prompt']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="<?= View::e(View::url('/teacher/daily-tests/' . $t['id'] . '/questions')) ?>" class="form-row" style="align-items:end;">
            <?= Csrf::field() ?>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.add_practice_q')) ?></label><input class="form-control" name="prompt" required></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.opt_a')) ?></label><input class="form-control" name="opt_0" required></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.opt_b')) ?></label><input class="form-control" name="opt_1" required></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.opt_c')) ?></label><input class="form-control" name="opt_2"></div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('teacher.correct_option')) ?></label>
                <select class="form-control" name="correct_index">
                    <option value="0"><?= View::e(Translator::get('teacher.opt_a')) ?></option>
                    <option value="1"><?= View::e(Translator::get('teacher.opt_b')) ?></option>
                    <option value="2"><?= View::e(Translator::get('teacher.opt_c')) ?></option>
                </select>
            </div>
            <div class="form-group"><button type="submit" class="btn btn-secondary btn-sm"><?= Icon::svg('plus', 14) ?></button></div>
        </form>
    </div>
<?php endforeach; ?>
