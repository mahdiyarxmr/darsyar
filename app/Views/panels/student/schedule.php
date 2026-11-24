<?php

use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$timetables = $timetables ?? [];
$subjects = $subjects ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.student'), 'url' => '/student/dashboard'], ['label' => Translator::get('menu.schedule')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.schedule')) ?></h1></div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('calendar', 17) ?><span><?= View::e(Translator::get('student.class_timetable')) ?></span></h2>
        <?php if (empty($timetables)): ?>
            <?= Ui::emptyState(Translator::get('common.no_data'), 'calendar') ?>
        <?php else: foreach ($timetables as $t): $sched = json_decode((string) ($t['schedule_json'] ?? '{}'), true) ?: []; ?>
            <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:12px;">
                <strong><?= View::e($t['title']) ?></strong> (<?= View::e($t['year_name']) ?>)
                <div style="margin-top:8px;font-size:0.86rem;display:grid;gap:4px;">
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

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('book-open', 17) ?><span><?= View::e(Translator::get('student.my_subjects')) ?></span></h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= View::e(Translator::get('common.code')) ?></th><th><?= View::e(Translator::get('common.subject')) ?></th><th><?= View::e(Translator::get('common.teacher')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($subjects as $s): ?>
                        <tr>
                            <td><code><?= View::e($s['code']) ?></code></td>
                            <td><?= View::e($s['name']) ?></td>
                            <td><?= View::e($s['teacher_name'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
