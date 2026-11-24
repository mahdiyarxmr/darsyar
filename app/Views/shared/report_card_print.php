<?php

use App\Core\DateHelper;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$card = $card ?? [];
$items = $card['items'] ?? [];
?>
<div class="card" style="padding:28px;">
    <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #CBD5E1;padding-bottom:16px;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="margin:0;font-size:1.45rem;"><?= View::e(Translator::get('report_card.official_title')) ?></h1>
            <div style="color:#475569;margin-top:4px;"><?= View::e(Ui::statusLabel((string) ($card['report_type'] ?? 'monthly'))) ?> — <?= View::e($card['period_label'] ?? '') ?></div>
        </div>
        <div style="text-align:end;font-size:0.9rem;">
            <div><strong><?= View::e(Translator::get('report_card.academic_year')) ?>:</strong> <?= View::e($card['year_name'] ?? '') ?></div>
            <div><strong><?= View::e(Translator::get('report_card.issued_date')) ?>:</strong> <?= View::e(DateHelper::format($card['created_at'] ?? '', false)) ?></div>
        </div>
    </div>

    <div class="grid grid-4" style="margin-bottom:20px;">
        <div><strong><?= View::e(Translator::get('common.student')) ?>:</strong><br><?= View::e($card['student_name'] ?? '') ?></div>
        <div><strong><?= View::e(Translator::get('report_card.student_code')) ?>:</strong><br><code><?= View::e($card['student_code'] ?? '') ?></code></div>
        <div><strong><?= View::e(Translator::get('report_card.grade_class')) ?>:</strong><br><?= View::e(($card['grade_name'] ?? '') . ' - ' . ($card['class_name'] ?? '')) ?></div>
        <div><strong><?= View::e(Translator::get('student.attendance_rate')) ?>:</strong><br><?= View::e(DateHelper::percent($card['attendance_rate'] ?? 100)) ?></div>
    </div>

    <div class="table-wrap" style="margin-bottom:20px;">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.code')) ?></th>
                    <th><?= View::e(Translator::get('common.subject')) ?></th>
                    <th><?= View::e(Translator::get('common.teacher')) ?></th>
                    <th><?= View::e(Translator::get('report_card.exam_avg')) ?></th>
                    <th><?= View::e(Translator::get('report_card.hw_score')) ?></th>
                    <th><?= View::e(Translator::get('report_card.final_grade')) ?></th>
                    <th><?= View::e(Translator::get('report_card.remarks')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><code><?= View::e($it['subject_code']) ?></code></td>
                        <td><?= View::e($it['subject_name']) ?></td>
                        <td><?= View::e($it['teacher_name'] ?? '-') ?></td>
                        <td><?= View::e(DateHelper::percent($it['exam_average'])) ?></td>
                        <td><?= View::e(DateHelper::percent($it['homework_score'])) ?></td>
                        <td><strong><?= View::e(DateHelper::percent($it['subject_grade'])) ?></strong></td>
                        <td><?= View::e(Ui::ratingLabel($it['remarks'] ?? null)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="grid grid-3" style="padding:14px;background:#F8FAFC;border-radius:8px;border:1px solid #CBD5E1;">
        <div><strong><?= View::e(Translator::get('report_card.hw_completion')) ?>:</strong> <?= View::e(DateHelper::percent($card['homework_rate'] ?? 0)) ?></div>
        <div><strong><?= View::e(Translator::get('report_card.final_average')) ?>:</strong> <span style="font-size:1.18rem;font-weight:800;"><?= View::e(DateHelper::percent($card['final_average'] ?? 0)) ?></span></div>
        <div><strong><?= View::e(Translator::get('report_card.overall_status')) ?>:</strong> <strong><?= View::e(Ui::ratingLabel($card['academic_status'] ?? null)) ?></strong></div>
    </div>
    <?php if (!empty($card['comments'])): ?>
        <div style="margin-top:14px;padding:12px;border:1px solid #E2E8F0;border-radius:8px;">
            <strong><?= View::e(Translator::get('common.notes')) ?>:</strong> <?= View::e($card['comments']) ?>
        </div>
    <?php endif; ?>
</div>
