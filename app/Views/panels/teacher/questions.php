<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$subjects = $subjects ?? [];
$grades = $grades ?? [];
$questions = $questions ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.teacher'), 'url' => '/teacher/dashboard'], ['label' => Translator::get('menu.questions')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.questions')) ?></h1></div>
    <button type="button" class="btn btn-primary btn-sm" data-drawer-toggle="questionCreateDrawer" aria-expanded="false">
        <?= Icon::svg('plus', 15) ?>
        <span><?= View::e(Translator::get('teacher.add_question_bank')) ?></span>
    </button>
</div>

<div id="questionCreateDrawer" class="action-drawer">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('help-circle', 17) ?><span><?= View::e(Translator::get('teacher.add_question_bank')) ?></span></h2>
            <button type="button" class="btn btn-ghost btn-sm btn-icon" data-drawer-toggle="questionCreateDrawer" aria-label="<?= View::e(Translator::get('common.close')) ?>"><?= Icon::svg('x', 16) ?></button>
        </div>
        <form method="POST" action="<?= View::e(View::url('/teacher/questions/create')) ?>">
            <?= Csrf::field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('common.subject')) ?></label>
                    <select class="form-control" name="subject_id" required>
                        <?php foreach ($subjects as $s): ?><option value="<?= View::e((string) $s['id']) ?>"><?= View::e($s['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('admin.post_type')) ?></label>
                    <select class="form-control" name="type">
                        <option value="mcq"><?= View::e(Translator::get('status.mcq')) ?></option>
                        <option value="descriptive"><?= View::e(Translator::get('status.descriptive')) ?></option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.score')) ?></label><input class="form-control" type="number" step="0.5" name="points" value="2"></div>
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('teacher.difficulty')) ?></label>
                    <select class="form-control" name="difficulty">
                        <option value="easy"><?= View::e(Translator::get('status.easy')) ?></option>
                        <option value="medium" selected><?= View::e(Translator::get('status.medium')) ?></option>
                        <option value="hard"><?= View::e(Translator::get('status.hard')) ?></option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.prompt')) ?></label><textarea class="form-control" name="prompt" rows="2" required></textarea></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.opt_a')) ?></label><input class="form-control" name="opt_0"></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.opt_b')) ?></label><input class="form-control" name="opt_1"></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.opt_c')) ?></label><input class="form-control" name="opt_2"></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.opt_d')) ?></label><input class="form-control" name="opt_3"></div>
            </div>
            <div class="form-row" style="align-items:end;">
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('teacher.correct_option')) ?></label>
                    <select class="form-control" name="correct_index">
                        <option value="0"><?= View::e(Translator::get('teacher.opt_a')) ?></option>
                        <option value="1"><?= View::e(Translator::get('teacher.opt_b')) ?></option>
                        <option value="2"><?= View::e(Translator::get('teacher.opt_c')) ?></option>
                        <option value="3"><?= View::e(Translator::get('teacher.opt_d')) ?></option>
                    </select>
                </div>
                <div class="form-group"><button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.save')) ?></button></div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= View::e(Translator::get('teacher.my_question_bank')) ?></h2>
        <span class="badge badge-muted"><?= View::e(DateHelper::number(count($questions))) ?></span>
    </div>
    <?php if (empty($questions)): ?>
        <?= Ui::emptyState(Translator::get('common.no_data'), 'help-circle') ?>
    <?php else: ?>
        <div style="display:grid;gap:10px;">
            <?php foreach ($questions as $q): ?>
                <div style="padding:13px 15px;border:1px solid var(--border-subtle);border-radius:var(--radius-sm);">
                    <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                        <span class="badge badge-primary"><?= View::e($q['subject_name']) ?> · <?= View::e(Ui::statusLabel($q['type'])) ?></span>
                        <span class="badge badge-muted"><?= View::e(DateHelper::number($q['points'])) ?> · <?= View::e(Ui::statusLabel($q['difficulty'])) ?></span>
                    </div>
                    <div style="font-weight:700;"><?= View::e($q['prompt']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
