<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$sessions = $sessions ?? [];
$assignments = $assignments ?? [];
$skyroomConfigured = $skyroomConfigured ?? false;
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.teacher'), 'url' => '/teacher/dashboard'], ['label' => Translator::get('menu.classrooms')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.classrooms')) ?></h1></div>
    <span class="badge <?= $skyroomConfigured ? 'badge-success' : 'badge-warning' ?>">
        <?= View::e(Translator::get($skyroomConfigured ? 'skyroom.status_connected' : 'skyroom.status_not_configured')) ?>
    </span>
</div>

<?php if (!$skyroomConfigured): ?>
    <div class="alert alert-warning"><?= Icon::svg('alert-triangle', 18) ?><span><?= View::e(Translator::get('skyroom.not_configured')) ?></span></div>
<?php endif; ?>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('video', 17) ?><span><?= View::e(Translator::get('teacher.schedule_classroom')) ?></span></h2>
        <form method="POST" action="<?= View::e(View::url('/teacher/classrooms/create')) ?>">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('teacher.class_subject')) ?></label>
                <select class="form-control" name="assignment_pair" required>
                    <?php foreach ($assignments as $a): ?>
                        <option value="<?= View::e($a['class_id'] . ':' . $a['subject_id']) ?>"><?= View::e($a['class_name'] . ' — ' . $a['subject_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.session_title')) ?></label><input class="form-control" name="title" required></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.start_datetime')) ?></label><input class="form-control" type="datetime-local" name="scheduled_start" value="<?= date('Y-m-d\TH:i') ?>" required></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.end_datetime')) ?></label><input class="form-control" type="datetime-local" name="scheduled_end" value="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>" required></div>
            </div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.fallback_url_hint')) ?></label><input class="form-control" type="url" name="fallback_url" placeholder="https://www.skyroom.online"></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.description')) ?></label><textarea class="form-control" name="description" rows="2"></textarea></div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('teacher.schedule_btn')) ?></button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('teacher.scheduled_sessions')) ?></h2>
        <?php if (empty($sessions)): ?>
            <?= Ui::emptyState(Translator::get('common.no_data'), 'video') ?>
        <?php else: foreach ($sessions as $s): ?>
            <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                    <strong><?= View::e($s['title']) ?></strong>
                    <span class="badge badge-primary"><?= View::e(Ui::statusLabel($s['status'])) ?> · <?= View::e(DateHelper::number($s['attendee_count'])) ?> <?= View::e(Translator::get('teacher.joined_count')) ?></span>
                </div>
                <div style="font-size:0.84rem;color:var(--text-muted);margin:4px 0 8px;">
                    <?= View::e($s['class_name'] . ' · ' . $s['subject_name']) ?> · <?= View::e(DateHelper::format($s['scheduled_start'])) ?>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <a href="<?= View::e(View::url('/teacher/classrooms/' . $s['id'] . '/join')) ?>" class="btn btn-primary btn-sm" target="_blank"><?= Icon::svg('external-link', 14) ?><span><?= View::e(Translator::get('teacher.launch_session')) ?></span></a>
                    <form method="POST" action="<?= View::e(View::url('/teacher/classrooms/' . $s['id'] . '/status')) ?>" style="display:flex;gap:6px;align-items:center;">
                        <?= Csrf::field() ?>
                        <select class="form-control" name="status" style="min-height:32px;padding:3px 8px;width:auto;">
                            <?php foreach (['scheduled', 'live', 'completed', 'cancelled'] as $st): ?>
                                <option value="<?= View::e($st) ?>" <?= $s['status'] === $st ? 'selected' : '' ?>><?= View::e(Ui::statusLabel($st)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.save')) ?></button>
                    </form>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
