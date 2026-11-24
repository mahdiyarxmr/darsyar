<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$test = $test ?? [];
$questions = $questions ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.student'), 'url' => '/student/dashboard'], ['label' => Translator::get('menu.daily_tests'), 'url' => '/student/daily-tests'], ['label' => $test['title'] ?? '']]) ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= View::e($test['title']) ?></h1>
        <p class="page-subtitle"><?= View::e($test['subject_name']) ?></p>
    </div>
</div>

<form method="POST" action="<?= View::e(View::url('/student/daily-tests/' . $test['id'] . '/submit')) ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="started_ts" value="<?= time() ?>">

    <?php foreach ($questions as $idx => $q): ?>
        <?php $opts = json_decode((string) $q['options_json'], true) ?: []; ?>
        <div class="card" style="margin-bottom:14px;">
            <p style="margin-top:0;font-weight:700;"><?= View::e(DateHelper::number($idx + 1)) ?>. <?= View::e($q['prompt']) ?></p>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <?php foreach ($opts as $optIdx => $optTxt): ?>
                    <label class="radio-card">
                        <input type="radio" name="answers[<?= View::e((string) $q['id']) ?>]" value="<?= View::e((string) $optIdx) ?>">
                        <span><?= View::e($optTxt) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('student.finish_check_score')) ?></button>
</form>
