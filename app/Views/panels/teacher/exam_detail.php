<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$exam = $exam ?? [];
$questions = $questions ?? [];
$bankQuestions = $bankQuestions ?? [];
$attempts = $attempts ?? [];
$classStudents = $classStudents ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.teacher'), 'url' => '/teacher/dashboard'], ['label' => Translator::get('menu.exams'), 'url' => '/teacher/exams'], ['label' => $exam['title'] ?? '']]) ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= View::e($exam['title']) ?></h1>
        <p class="page-subtitle"><?= View::e($exam['class_name'] . ' · ' . $exam['subject_name']) ?> · <?= View::e(Ui::statusLabel($exam['exam_mode'])) ?> · <?= View::e(Translator::get('teacher.max_score')) ?>: <?= View::e(DateHelper::number($exam['max_score'])) ?></p>
    </div>
    <a href="<?= View::e(View::url('/teacher/exams')) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.back')) ?></a>
</div>

<?php if ($exam['exam_mode'] === 'online'): ?>
    <div class="grid grid-2" style="margin-bottom:20px;align-items:start;">
        <div class="card">
            <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('plus', 17) ?><span><?= View::e(Translator::get('teacher.add_exam_question')) ?></span></h2>
            <?php if (!empty($bankQuestions)): ?>
                <form method="POST" action="<?= View::e(View::url('/teacher/exams/' . $exam['id'] . '/questions')) ?>" style="margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--border);">
                    <?= Csrf::field() ?>
                    <label class="form-label"><?= View::e(Translator::get('teacher.import_from_bank')) ?></label>
                    <div style="display:flex;gap:8px;">
                        <select class="form-control" name="bank_question_id">
                            <?php foreach ($bankQuestions as $bq): ?>
                                <option value="<?= View::e((string) $bq['id']) ?>">[<?= View::e(Ui::statusLabel($bq['type'])) ?>] <?= View::e(mb_substr($bq['prompt'], 0, 70)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('teacher.import_btn')) ?></button>
                    </div>
                </form>
            <?php endif; ?>

            <form method="POST" action="<?= View::e(View::url('/teacher/exams/' . $exam['id'] . '/questions')) ?>">
                <?= Csrf::field() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= View::e(Translator::get('admin.post_type')) ?></label>
                        <select class="form-control" name="type">
                            <option value="mcq"><?= View::e(Translator::get('status.mcq')) ?></option>
                            <option value="descriptive"><?= View::e(Translator::get('status.descriptive')) ?></option>
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label"><?= View::e(Translator::get('common.score')) ?></label><input class="form-control" type="number" step="0.5" name="points" value="5"></div>
                </div>
                <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.prompt')) ?></label><textarea class="form-control" name="prompt" rows="2" required></textarea></div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.opt_a')) ?></label><input class="form-control" name="opt_0"></div>
                    <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.opt_b')) ?></label><input class="form-control" name="opt_1"></div>
                    <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.opt_c')) ?></label><input class="form-control" name="opt_2"></div>
                    <div class="form-group"><label class="form-label"><?= View::e(Translator::get('teacher.opt_d')) ?></label><input class="form-control" name="opt_3"></div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('teacher.correct_option')) ?></label>
                    <select class="form-control" name="correct_index">
                        <option value="0"><?= View::e(Translator::get('teacher.opt_a')) ?></option>
                        <option value="1"><?= View::e(Translator::get('teacher.opt_b')) ?></option>
                        <option value="2"><?= View::e(Translator::get('teacher.opt_c')) ?></option>
                        <option value="3"><?= View::e(Translator::get('teacher.opt_d')) ?></option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('teacher.add_exam_question')) ?></button>
            </form>
        </div>

        <div class="card">
            <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('teacher.exam_questions')) ?> (<?= View::e(DateHelper::number(count($questions))) ?>)</h2>
            <?php foreach ($questions as $idx => $q): ?>
                <div style="padding:10px;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:10px;">
                    <strong><?= View::e(DateHelper::number($idx + 1)) ?>. (<?= View::e(Ui::statusLabel($q['type'])) ?>, <?= View::e(DateHelper::number($q['points'])) ?>)</strong>
                    <div style="margin-top:4px;"><?= View::e($q['prompt']) ?></div>
                    <?php if (!empty($q['options'])): ?>
                        <ul style="margin:6px 0 0;padding-inline-start:18px;font-size:0.85rem;">
                            <?php foreach ($q['options'] as $opt): ?>
                                <li style="<?= (int) $opt['is_correct'] === 1 ? 'color:var(--success);font-weight:700;' : '' ?>">
                                    <?= View::e($opt['option_text']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('teacher.student_attempts')) ?></h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= View::e(Translator::get('common.student')) ?></th>
                        <th><?= View::e(Translator::get('vp.student_code')) ?></th>
                        <th><?= View::e(Translator::get('common.date')) ?></th>
                        <th><?= View::e(Translator::get('common.status')) ?></th>
                        <th><?= View::e(Translator::get('common.score')) ?></th>
                        <th><?= View::e(Translator::get('common.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attempts as $a): ?>
                        <tr>
                            <td><?= View::e($a['student_name']) ?></td>
                            <td><code><?= View::e($a['student_code']) ?></code></td>
                            <td><?= View::e(DateHelper::format($a['submitted_at'])) ?></td>
                            <td><span class="badge <?= $a['status'] === 'graded' ? 'badge-success' : 'badge-warning' ?>"><?= View::e(Ui::statusLabel($a['status'])) ?></span></td>
                            <td><strong><?= View::e(DateHelper::number($a['total_score'])) ?></strong></td>
                            <td><a href="<?= View::e(View::url('/teacher/exams/attempt/' . $a['id'] . '/grade')) ?>" class="btn btn-primary btn-sm"><?= View::e(Translator::get('teacher.review_grade')) ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="card" style="margin-bottom:20px;">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('teacher.offline_csv_title')) ?></h2>
        <form method="POST" action="<?= View::e(View::url('/teacher/exams/' . $exam['id'] . '/offline-import')) ?>" enctype="multipart/form-data" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <?= Csrf::field() ?>
            <input class="form-control" type="file" name="scores_csv" accept=".csv,text/csv" style="max-width:320px;" required>
            <button type="submit" class="btn btn-success btn-sm"><?= View::e(Translator::get('common.upload')) ?></button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('teacher.offline_manual_title')) ?> (<?= View::e(DateHelper::number($exam['max_score'])) ?>)</h2>
        <form method="POST" action="<?= View::e(View::url('/teacher/exams/' . $exam['id'] . '/offline-scores')) ?>">
            <?= Csrf::field() ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th><?= View::e(Translator::get('vp.student_code')) ?></th><th><?= View::e(Translator::get('common.student')) ?></th><th><?= View::e(Translator::get('common.score')) ?></th><th><?= View::e(Translator::get('common.notes')) ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($classStudents as $stu): ?>
                            <tr>
                                <td><code><?= View::e($stu['student_code']) ?></code></td>
                                <td><?= View::e($stu['full_name']) ?></td>
                                <td><input class="form-control" type="number" step="0.25" max="<?= View::e((string) $exam['max_score']) ?>" name="scores[<?= View::e((string) $stu['id']) ?>]" value="<?= View::e((string) ($stu['recorded_score'] ?? '')) ?>"></td>
                                <td><input class="form-control" name="remarks[<?= View::e((string) $stu['id']) ?>]" value="<?= View::e((string) ($stu['recorded_remarks'] ?? '')) ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:14px;display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
                <label class="form-check"><input type="checkbox" name="publish" value="1" checked> <span><?= View::e(Translator::get('teacher.publish_notify_parents')) ?></span></label>
                <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.save')) ?></button>
            </div>
        </form>
    </div>
<?php endif; ?>
