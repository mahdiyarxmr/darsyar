<?php

use App\Core\Csrf;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$timetables = $timetables ?? [];
$classes = $classes ?? [];
$years = $years ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('nav.dashboard'), 'url' => '/dashboard'], ['label' => Translator::get('menu.timetables')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.timetables')) ?></h1></div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('calendar', 17) ?><span><?= View::e(Translator::get('vp.create_timetable')) ?></span></h2>
        <form method="POST" action="<?= View::e(View::url('/vice-principal/timetables/save')) ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('common.year')) ?></label>
                    <select class="form-control" name="academic_year_id">
                        <?php foreach ($years as $y): ?><option value="<?= View::e((string) $y['id']) ?>"><?= View::e($y['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('common.class')) ?></label>
                    <select class="form-control" name="class_id">
                        <?php foreach ($classes as $c): ?><option value="<?= View::e((string) $c['id']) ?>"><?= View::e($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('vp.timetable_title')) ?></label><input class="form-control" name="title" required></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('weekday.saturday')) ?></label><input class="form-control" name="sat"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('weekday.sunday')) ?></label><input class="form-control" name="sun"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('weekday.monday')) ?></label><input class="form-control" name="mon"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('weekday.tuesday')) ?></label><input class="form-control" name="tue"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('weekday.wednesday')) ?></label><input class="form-control" name="wed"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('vp.timetable_file_hint')) ?></label><div class="file-upload"><input class="form-control" type="file" name="timetable_file" accept=".pdf,.png,.jpg,.jpeg"></div></div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.save')) ?></button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('vp.published_timetables')) ?></h2>
        <?php if (empty($timetables)): ?>
            <?= Ui::emptyState(Translator::get('common.no_data'), 'calendar') ?>
        <?php else: foreach ($timetables as $t): ?>
            <?php $sched = json_decode((string) ($t['schedule_json'] ?? '{}'), true) ?: []; ?>
            <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                    <strong><?= View::e($t['title']) ?></strong>
                    <span class="badge badge-primary"><?= View::e($t['class_name']) ?> · <?= View::e($t['year_name']) ?></span>
                </div>
                <div style="font-size:0.84rem;color:var(--text-muted);margin-top:8px;display:grid;gap:4px;">
                    <?php foreach ($sched as $day => $line): if ($line !== ''): ?>
                        <div><strong><?= View::e(Ui::weekdayLabel((string) $day)) ?>:</strong> <?= View::e($line) ?></div>
                    <?php endif; endforeach; ?>
                </div>
                <?php if (!empty($t['file_path'])): ?>
                    <a href="<?= View::e(View::url('/files/timetable/' . $t['id'])) ?>" class="btn btn-secondary btn-sm" style="margin-top:8px;"><?= Icon::svg('download', 14) ?><span><?= View::e(Translator::get('common.download')) ?></span></a>
                <?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
