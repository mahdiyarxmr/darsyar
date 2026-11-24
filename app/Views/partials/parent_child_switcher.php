<?php

use App\Core\Icon;
use App\Core\View;

$children = $children ?? [];
$activeChild = $activeChild ?? null;
$basePath = $basePath ?? '/parent/dashboard';
?>
<?php if (count($children) > 1): ?>
    <div class="pill-tabs" role="tablist">
        <?php foreach ($children as $child): ?>
            <?php $isActive = $activeChild && (int) $child['id'] === (int) $activeChild['id']; ?>
            <a href="<?= View::e(View::url($basePath . '?student_id=' . $child['id'])) ?>" class="pill-tab <?= $isActive ? 'active' : '' ?>">
                <?= Icon::svg('graduation-cap', 15) ?>
                <span><?= View::e($child['full_name']) ?> (<?= View::e($child['class_name'] ?? '-') ?>)</span>
            </a>
        <?php endforeach; ?>
    </div>
<?php elseif ($activeChild): ?>
    <div style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <span class="badge badge-primary"><?= Icon::svg('graduation-cap', 13) ?> <?= View::e($activeChild['full_name']) ?></span>
        <span class="badge badge-muted"><?= View::e(($activeChild['grade_name'] ?? '') . ' · ' . ($activeChild['class_name'] ?? '-')) ?></span>
    </div>
<?php endif; ?>
