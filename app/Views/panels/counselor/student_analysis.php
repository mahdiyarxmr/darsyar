<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$analysis = $analysis ?? [];
$student = $analysis['student'] ?? [];
$rating = $analysis['rating'] ?? [];
$attendance = $analysis['attendance'] ?? [];
$dailyStats = $analysis['dailyStats'] ?? [];
$exams = $analysis['exams'] ?? [];
$notes = $notes ?? [];
?>
<?= Ui::breadcrumbs([
    ['label' => Translator::get('panel.counselor'), 'url' => '/counselor/dashboard'],
    ['label' => $student['full_name'] ?? ''],
]) ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= View::e(Translator::get('counselor.performance_analysis')) ?>: <?= View::e($student['full_name'] ?? '') ?></h1>
        <p class="page-subtitle"><code><?= View::e($student['student_code'] ?? '') ?></code> · <?= View::e(($student['grade_name'] ?? '') . ' — ' . ($student['class_name'] ?? '-')) ?></p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= View::e(View::url('/counselor/study-plans?student_id=' . ($student['id'] ?? 0))) ?>" class="btn btn-primary btn-sm"><?= View::e(Translator::get('counselor.manage_plan')) ?></a>
        <a href="<?= View::e(View::url('/counselor/notes?student_id=' . ($student['id'] ?? 0))) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('counselor.add_note')) ?></a>
    </div>
</div>

<div class="grid grid-4" style="margin-bottom:20px;">
    <div class="stat-card"><span class="stat-label"><?= View::e(Translator::get('student.overall_score')) ?></span><span class="stat-value"><?= View::e(DateHelper::number($rating['total_score'] ?? 0)) ?></span><span class="stat-meta"><?= View::e(Ui::ratingLabel($rating['category'] ?? null)) ?></span></div>
    <div class="stat-card"><span class="stat-label"><?= View::e(Translator::get('student.exam_avg')) ?></span><span class="stat-value"><?= View::e(DateHelper::percent($rating['academic_score'] ?? 0)) ?></span></div>
    <div class="stat-card"><span class="stat-label"><?= View::e(Translator::get('student.study_plan_progress')) ?></span><span class="stat-value"><?= View::e(DateHelper::percent($rating['study_plan_score'] ?? 0)) ?></span></div>
    <div class="stat-card"><span class="stat-label"><?= View::e(Translator::get('student.attendance_rate')) ?></span><span class="stat-value"><?= View::e(DateHelper::percent($attendance['rate'] ?? 100)) ?></span></div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('file-text', 17) ?><span><?= View::e(Translator::get('counselor.exam_history')) ?></span></h2>
        <?php if (empty($exams)): ?>
            <?= Ui::emptyState(Translator::get('common.no_data'), 'file-text') ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th><?= View::e(Translator::get('teacher.exam_title')) ?></th><th><?= View::e(Translator::get('common.subject')) ?></th><th><?= View::e(Translator::get('common.percentage')) ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($exams as $e): ?>
                            <tr><td><?= View::e($e['exam_title']) ?></td><td><?= View::e($e['subject_name']) ?></td><td><span class="badge badge-success"><?= View::e(DateHelper::percent($e['percentage'])) ?></span></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px;"><?= Icon::svg('notebook', 17) ?><span><?= View::e(Translator::get('counselor.counseling_notes')) ?></span></h2>
        <?php if (empty($notes)): ?>
            <?= Ui::emptyState(Translator::get('common.no_data'), 'notebook') ?>
        <?php else: foreach ($notes as $n): ?>
            <div style="padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:10px;">
                <strong><?= View::e($n['title']) ?></strong>
                <p style="margin:4px 0;font-size:0.86rem;color:var(--text-muted);"><?= View::e($n['note']) ?></p>
                <small style="color:var(--text-muted);"><?= View::e(DateHelper::format($n['created_at'])) ?></small>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
