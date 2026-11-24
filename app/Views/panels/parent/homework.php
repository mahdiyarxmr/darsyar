<?php

use App\Core\DateHelper;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$children = $children ?? [];
$activeChild = $activeChild ?? null;
$items = $items ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.parent'), 'url' => '/parent/dashboard'], ['label' => Translator::get('menu.homework')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.homework')) ?></h1></div>
</div>

<?= View::partial('parent_child_switcher', ['children' => $children, 'activeChild' => $activeChild, 'basePath' => '/parent/homework']) ?>

<div class="card">
    <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('parent.hw_status_title')) ?></h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('common.title')) ?></th>
                    <th><?= View::e(Translator::get('common.subject')) ?></th>
                    <th><?= View::e(Translator::get('teacher.deadline')) ?></th>
                    <th><?= View::e(Translator::get('common.status')) ?></th>
                    <th><?= View::e(Translator::get('common.score')) ?></th>
                    <th><?= View::e(Translator::get('parent.teacher_feedback')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6"><?= View::e(Translator::get('common.no_data')) ?></td></tr>
                <?php else: foreach ($items as $hw): ?>
                    <tr>
                        <td><strong><?= View::e($hw['title']) ?></strong></td>
                        <td><?= View::e($hw['subject_name']) ?></td>
                        <td><?= View::e(DateHelper::format($hw['due_at'])) ?></td>
                        <td><span class="badge badge-primary"><?= View::e(Ui::statusLabel($hw['sub_status'] ?? 'pending')) ?></span></td>
                        <td><?= $hw['score'] !== null ? View::e(DateHelper::number($hw['score']) . ' / ' . DateHelper::number($hw['max_score'])) : '-' ?></td>
                        <td><?= View::e($hw['feedback_text'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
