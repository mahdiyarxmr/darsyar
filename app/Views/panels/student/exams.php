<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;
use App\Modules\Exams\ExamService;

$exams = $exams ?? [];
$studentId = $studentId ?? 0;
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.student'), 'url' => '/student/dashboard'], ['label' => Translator::get('menu.exams')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.exams')) ?></h1></div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('teacher.exam_title')) ?></th>
                    <th><?= View::e(Translator::get('common.subject')) ?></th>
                    <th><?= View::e(Translator::get('teacher.exam_mode')) ?></th>
                    <th><?= View::e(Translator::get('student.availability_window')) ?></th>
                    <th><?= View::e(Translator::get('common.score')) ?></th>
                    <th><?= View::e(Translator::get('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($exams)): ?>
                    <tr><td colspan="6"><?= View::e(Translator::get('common.no_data')) ?></td></tr>
                <?php else: foreach ($exams as $e): ?>
                    <?php $avail = $e['exam_mode'] === 'online' ? ExamService::checkAvailability($e, (int) $studentId) : ['allowed' => false, 'reason' => 'offline']; ?>
                    <tr>
                        <td><strong><?= View::e($e['title']) ?></strong></td>
                        <td><?= View::e($e['subject_name']) ?></td>
                        <td><span class="badge <?= $e['exam_mode'] === 'online' ? 'badge-primary' : 'badge-warning' ?>"><?= View::e(Ui::statusLabel($e['exam_mode'])) ?></span></td>
                        <td><small><?= View::e(DateHelper::format($e['starts_at'])) ?> — <?= View::e(DateHelper::format($e['ends_at'])) ?></small></td>
                        <td>
                            <?php if ($e['my_score'] !== null): ?>
                                <strong><?= View::e(DateHelper::number($e['my_score'])) ?> / <?= View::e(DateHelper::number($e['max_score'])) ?></strong>
                                (<?= View::e(DateHelper::percent($e['my_pct'])) ?>)
                            <?php else: ?>
                                <span style="color:var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                                <?php if ($e['exam_mode'] === 'offline'): ?>
                                    <span class="badge badge-muted"><?= View::e(Translator::get('student.offline_exam_badge')) ?></span>
                                    <?php if (!empty($e['practice_sheet_path'])): ?>
                                        <a href="<?= View::e(View::url('/files/exam/' . $e['id'])) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('student.practice_sheet_btn')) ?></a>
                                    <?php endif; ?>
                                <?php elseif ($avail['allowed']): ?>
                                    <a href="<?= View::e(View::url('/student/exams/' . $e['id'] . '/take')) ?>" class="btn btn-primary btn-sm"><?= Icon::svg('edit', 14) ?><span><?= View::e(Translator::get('student.start_continue_exam')) ?></span></a>
                                <?php elseif ($avail['reason'] === 'closed'): ?>
                                    <span class="badge badge-danger"><?= View::e(Translator::get('exam.closed')) ?></span>
                                <?php elseif ($avail['reason'] === 'not_started'): ?>
                                    <span class="badge badge-warning"><?= View::e(Translator::get('student.scheduled_badge')) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?= View::e(Translator::get('student.completed_badge')) ?></span>
                                <?php endif; ?>

                                <?php if ((int) $e['attempts_used'] > 0): ?>
                                    <a href="<?= View::e(View::url('/student/exams/' . $e['id'] . '/result')) ?>" class="btn btn-secondary btn-sm"><?= Icon::svg('eye', 14) ?><span><?= View::e(Translator::get('student.review_result')) ?></span></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
