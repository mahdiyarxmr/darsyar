<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$children = $children ?? [];
$activeChild = $activeChild ?? null;
$timetables = $timetables ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.parent'), 'url' => '/parent/dashboard'], ['label' => Translator::get('menu.timetables')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.timetables')) ?></h1></div>
</div>

<?= View::partial('parent_child_switcher', ['children' => $children, 'activeChild' => $activeChild, 'basePath' => '/parent/timetables']) ?>

<div class="card">
    <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('calendar', 17) ?><span><?= View::e(Translator::get('student.class_timetable')) ?></span></h2>
    <?php if (empty($timetables)): ?>
        <?= Ui::emptyState(Translator::get('common.no_data'), 'calendar') ?>
    <?php else: foreach ($timetables as $t): $sched = json_decode((string) ($t['schedule_json'] ?? '{}'), true) ?: []; ?>
        <div style="padding:14px;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:12px;">
            <strong><?= View::e($t['title']) ?></strong> (<?= View::e(DateHelper::localizeDigits($t['year_name'])) ?>)
            <div style="margin-top:8px;font-size:0.88rem;display:grid;gap:4px;">
                <?php foreach ($sched as $day => $line): if ($line !== ''): ?>
                    <div><strong><?= View::e(Ui::weekdayLabel((string) $day)) ?>:</strong> <?= View::e($line) ?></div>
                <?php endif; endforeach; ?>
            </div>
            <?php if (!empty($t['file_path'])): ?>
                <a href="<?= View::e(View::url('/files/timetable/' . $t['id'])) ?>" class="btn btn-secondary btn-sm" style="margin-top:10px;"><?= Icon::svg('download', 14) ?><span><?= View::e(Translator::get('common.download')) ?></span></a>
            <?php endif; ?>
        </div>
    <?php endforeach; endif; ?>
</div>
