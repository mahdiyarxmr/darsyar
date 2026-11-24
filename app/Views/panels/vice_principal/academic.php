<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$years = $years ?? [];
$semesters = $semesters ?? [];
$grades = $grades ?? [];
$classes = $classes ?? [];
$subjects = $subjects ?? [];
$teachers = $teachers ?? [];
$assignments = $assignments ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.vice_principal'), 'url' => '/vice-principal/dashboard'], ['label' => Translator::get('menu.academic')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.academic')) ?></h1></div>
</div>

<div class="tabs" role="tablist">
    <button type="button" class="pill-tab active" data-tab-group="academic" data-tab-target="tabAssignments"><?= Icon::svg('user-check', 15) ?><span><?= View::e(Translator::get('vp.active_assignments')) ?></span></button>
    <button type="button" class="pill-tab" data-tab-group="academic" data-tab-target="tabClasses"><?= Icon::svg('layers', 15) ?><span><?= View::e(Translator::get('common.class')) ?></span></button>
    <button type="button" class="pill-tab" data-tab-group="academic" data-tab-target="tabSubjects"><?= Icon::svg('book-open', 15) ?><span><?= View::e(Translator::get('common.subject')) ?></span></button>
    <button type="button" class="pill-tab" data-tab-group="academic" data-tab-target="tabYears"><?= Icon::svg('calendar', 15) ?><span><?= View::e(Translator::get('common.year')) ?></span></button>
</div>

<!-- Tab 1: Teacher Assignments -->
<div id="tabAssignments" class="tab-panel active" data-tab-group="academic">
    <div class="card" style="margin-bottom:18px;">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('user-check', 17) ?><span><?= View::e(Translator::get('vp.assign_teacher')) ?></span></h2>
        <form method="POST" action="<?= View::e(View::url('/vice-principal/academic/assign-teacher')) ?>" class="form-row" style="align-items:end;">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.teacher')) ?></label>
                <select class="form-control" name="teacher_id">
                    <?php foreach ($teachers as $t): ?><option value="<?= View::e((string) $t['id']) ?>"><?= View::e($t['full_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.class')) ?></label>
                <select class="form-control" name="class_id">
                    <?php foreach ($classes as $c): ?><option value="<?= View::e((string) $c['id']) ?>"><?= View::e($c['name']) ?> (<?= View::e($c['grade_name']) ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.subject')) ?></label>
                <select class="form-control" name="subject_id">
                    <?php foreach ($subjects as $s): ?><option value="<?= View::e((string) $s['id']) ?>"><?= View::e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.year')) ?></label>
                <select class="form-control" name="academic_year_id">
                    <?php foreach ($years as $y): ?><option value="<?= View::e((string) $y['id']) ?>"><?= View::e($y['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><button type="submit" class="btn btn-primary"><?= View::e(Translator::get('vp.assign_btn')) ?></button></div>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= View::e(Translator::get('common.teacher')) ?></th>
                        <th><?= View::e(Translator::get('common.class')) ?></th>
                        <th><?= View::e(Translator::get('common.subject')) ?></th>
                        <th><?= View::e(Translator::get('common.year')) ?></th>
                        <th><?= View::e(Translator::get('common.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td><strong><?= View::e($a['teacher_name']) ?></strong></td>
                            <td><span class="badge badge-primary"><?= View::e($a['class_name']) ?></span></td>
                            <td><?= View::e($a['subject_name']) ?></td>
                            <td><?= View::e(DateHelper::localizeDigits($a['year_name'])) ?></td>
                            <td>
                                <form method="POST" action="<?= View::e(View::url('/vice-principal/academic/assign-teacher/' . $a['id'] . '/delete')) ?>" data-confirm="<?= View::e(Translator::get('common.confirm_delete')) ?>">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-ghost btn-sm btn-icon" aria-label="<?= View::e(Translator::get('common.delete')) ?>"><?= Icon::svg('trash', 15) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab 2: Classes -->
<div id="tabClasses" class="tab-panel" data-tab-group="academic">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('layers', 17) ?><span><?= View::e(Translator::get('vp.create_class')) ?></span></h2>
        <form method="POST" action="<?= View::e(View::url('/vice-principal/academic/classes')) ?>">
            <?= Csrf::field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('common.year')) ?></label>
                    <select class="form-control" name="academic_year_id">
                        <?php foreach ($years as $y): ?><option value="<?= View::e((string) $y['id']) ?>"><?= View::e($y['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('common.grade')) ?></label>
                    <select class="form-control" name="grade_id">
                        <?php foreach ($grades as $g): ?><option value="<?= View::e((string) $g['id']) ?>"><?= View::e($g['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('vp.class_name')) ?></label><input class="form-control" name="name" placeholder="10-B" required></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('vp.room_number')) ?></label><input class="form-control" name="room_number"></div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('vp.capacity')) ?></label><input class="form-control" type="number" name="capacity" value="30"></div>
            </div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.create')) ?></button>
        </form>
    </div>
</div>

<!-- Tab 3: Subjects -->
<div id="tabSubjects" class="tab-panel" data-tab-group="academic">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('book-open', 17) ?><span><?= View::e(Translator::get('vp.create_subject')) ?></span></h2>
        <form method="POST" action="<?= View::e(View::url('/vice-principal/academic/subjects')) ?>" class="form-row" style="align-items:end;">
            <?= Csrf::field() ?>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('vp.subject_code')) ?></label><input class="form-control" name="code" placeholder="LIT101" required></div>
            <div class="form-group"><label class="form-label"><?= View::e(Translator::get('vp.subject_name')) ?></label><input class="form-control" name="name" required></div>
            <div class="form-group"><button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.create')) ?></button></div>
        </form>
    </div>
</div>

<!-- Tab 4: Years & Semesters -->
<div id="tabYears" class="tab-panel" data-tab-group="academic">
    <div class="grid grid-2">
        <div class="card">
            <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('calendar', 17) ?><span><?= View::e(Translator::get('vp.create_year')) ?></span></h2>
            <form method="POST" action="<?= View::e(View::url('/vice-principal/academic/years')) ?>">
                <?= Csrf::field() ?>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('vp.year_name')) ?></label><input class="form-control" name="name" placeholder="1405-1406" required></div>
                <div class="form-row">
                    <div class="form-group date-picker-wrap"><label class="form-label"><?= View::e(Translator::get('vp.start_date')) ?></label><input class="form-control" type="date" name="start_date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="form-group date-picker-wrap"><label class="form-label"><?= View::e(Translator::get('vp.end_date')) ?></label><input class="form-control" type="date" name="end_date" value="<?= date('Y-m-d', strtotime('+9 months')) ?>" required></div>
                </div>
                <div class="form-group"><label class="form-check"><input type="checkbox" name="is_active" value="1"> <span><?= View::e(Translator::get('vp.set_active_year')) ?></span></label></div>
                <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.create')) ?></button>
            </form>
        </div>

        <div class="card">
            <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('vp.create_semester')) ?></h2>
            <form method="POST" action="<?= View::e(View::url('/vice-principal/academic/semesters')) ?>">
                <?= Csrf::field() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= View::e(Translator::get('common.year')) ?></label>
                        <select class="form-control" name="academic_year_id">
                            <?php foreach ($years as $y): ?><option value="<?= View::e((string) $y['id']) ?>"><?= View::e($y['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.semester')) ?></label><input class="form-control" name="name" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group date-picker-wrap"><label class="form-label"><?= View::e(Translator::get('vp.start_date')) ?></label><input class="form-control" type="date" name="start_date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="form-group date-picker-wrap"><label class="form-label"><?= View::e(Translator::get('vp.end_date')) ?></label><input class="form-control" type="date" name="end_date" value="<?= date('Y-m-d', strtotime('+4 months')) ?>" required></div>
                </div>
                <button type="submit" class="btn btn-secondary"><?= View::e(Translator::get('common.create')) ?></button>
            </form>
        </div>
    </div>
</div>
