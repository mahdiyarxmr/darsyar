<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$sessions = $sessions ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.student'), 'url' => '/student/dashboard'], ['label' => Translator::get('menu.classrooms')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.classrooms')) ?></h1></div>
</div>

<div class="card">
    <?php if (empty($sessions)): ?>
        <?= Ui::emptyState(Translator::get('common.no_data'), 'video') ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= View::e(Translator::get('teacher.session_title')) ?></th>
                        <th><?= View::e(Translator::get('common.subject')) ?></th>
                        <th><?= View::e(Translator::get('common.teacher')) ?></th>
                        <th><?= View::e(Translator::get('common.date')) ?></th>
                        <th><?= View::e(Translator::get('common.status')) ?></th>
                        <th><?= View::e(Translator::get('common.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td><strong><?= View::e($s['title']) ?></strong></td>
                            <td><?= View::e($s['subject_name']) ?></td>
                            <td><?= View::e($s['teacher_name']) ?></td>
                            <td><?= View::e(DateHelper::format($s['scheduled_start'])) ?></td>
                            <td><span class="badge badge-primary"><?= View::e(Ui::statusLabel($s['status'])) ?></span></td>
                            <td><a href="<?= View::e(View::url('/student/classrooms/' . $s['id'] . '/join')) ?>" class="btn btn-primary btn-sm" target="_blank"><?= Icon::svg('external-link', 14) ?><span><?= View::e(Translator::get('student.join_classroom')) ?></span></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
