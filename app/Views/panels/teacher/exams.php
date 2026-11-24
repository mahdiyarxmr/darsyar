<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$assignments = $assignments ?? [];
$exams = $exams ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.teacher'), 'url' => '/teacher/dashboard'], ['label' => Translator::get('menu.exams')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.exams')) ?></h1></div>
    <button type="button" class="btn btn-primary btn-sm" data-drawer-toggle="examCreateDrawer" aria-expanded="false">
        <?= Icon::svg('plus', 15) ?>
        <span><?= View::e(Translator::get('teacher.create_exam')) ?></span>
    </button>
</div>

<div id="examCreateDrawer" class="action-drawer">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= Icon::svg('file-text', 17) ?><span><?= View::e(Translator::get('teacher.create_exam')) ?></span></h2>
            <button type="button" class="btn btn-ghost btn-sm btn-icon" data-drawer-toggle="examCreateDrawer" aria-label="<?= View::e(Translator::get('common.close')) ?>"><?= Icon::svg('x', 16) ?></button>
        </div>
        <form method="POST" action="<?= View::e(View::url('/teacher/exams/create')) ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="form-row">
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.exam_title')) ?></label><input class="form-control" name="title" required></div>
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('teacher.class_subject')) ?></label>
                    <select class="form-control" name="assignment_pair" required>
                        <?php foreach ($assignments as $a): ?>
                            <option value="<?= View::e($a['class_id'] . ':' . $a['subject_id']) ?>"><?= View::e($a['class_name'] . ' — ' . $a['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('teacher.exam_mode')) ?></label>
                    <select class="form-control" name="exam_mode">
                        <option value="online"><?= View::e(Translator::get('status.online')) ?></option>
                        <option value="offline"><?= View::e(Translator::get('status.offline')) ?></option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.start_datetime')) ?></label><input class="form-control" type="datetime-local" name="starts_at" value="<?= date('Y-m-d\TH:i') ?>" required></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.end_datetime')) ?></label><input class="form-control" type="datetime-local" name="ends_at" value="<?= date('Y-m-d\TH:i', strtotime('+2 hours')) ?>" required></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.duration_min')) ?></label><input class="form-control" type="number" name="duration_minutes" value="45"></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.max_score')) ?></label><input class="form-control" type="number" step="0.5" name="max_score" value="20"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.negative_ratio')) ?></label><input class="form-control" type="number" step="0.05" name="negative_marking_ratio" value="0"></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.max_attempts')) ?></label><input class="form-control" type="number" name="max_attempts" value="1"></div>
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('teacher.window_policy')) ?></label>
                    <select class="form-control" name="late_finish_policy">
                        <option value="hard_cutoff"><?= View::e(Translator::get('teacher.hard_cutoff')) ?></option>
                        <option value="allow_finish_duration"><?= View::e(Translator::get('teacher.allow_finish')) ?></option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.practice_sheet')) ?></label><input class="form-control" type="file" name="practice_sheet" accept=".pdf,.png,.jpg,.jpeg"></div>
            </div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.description')) ?></label><textarea class="form-control" name="description" rows="2"></textarea></div>
            <div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:14px;">
                <label class="form-check"><input type="checkbox" name="randomize_questions" value="1"> <span><?= View::e(Translator::get('teacher.randomize_q')) ?></span></label>
                <label class="form-check"><input type="checkbox" name="randomize_options" value="1"> <span><?= View::e(Translator::get('teacher.randomize_opt')) ?></span></label>
            </div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.create')) ?></button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('teacher.exam_title')) ?></th>
                    <th><?= View::e(Translator::get('teacher.exam_mode')) ?></th>
                    <th><?= View::e(Translator::get('teacher.class_subject')) ?></th>
                    <th><?= View::e(Translator::get('student.availability_window')) ?></th>
                    <th><?= View::e(Translator::get('student.questions_count')) ?></th>
                    <th><?= View::e(Translator::get('common.total')) ?></th>
                    <th><?= View::e(Translator::get('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($exams)): ?>
                    <tr><td colspan="7"><?= View::e(Translator::get('common.no_data')) ?></td></tr>
                <?php else: foreach ($exams as $e): ?>
                    <tr>
                        <td><strong><?= View::e($e['title']) ?></strong></td>
                        <td><span class="badge <?= $e['exam_mode'] === 'online' ? 'badge-primary' : 'badge-warning' ?>"><?= View::e(Ui::statusLabel($e['exam_mode'])) ?></span></td>
                        <td><?= View::e($e['class_name']) ?> · <?= View::e($e['subject_name']) ?></td>
                        <td><small><?= View::e(DateHelper::format($e['starts_at'])) ?> — <?= View::e(DateHelper::format($e['ends_at'])) ?></small></td>
                        <td><?= View::e(DateHelper::number($e['q_count'])) ?></td>
                        <td><?= View::e(DateHelper::number($e['attempt_count'])) ?></td>
                        <td><a href="<?= View::e(View::url('/teacher/exams/' . $e['id'])) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('teacher.review_grade')) ?></a></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
