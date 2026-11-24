<?php

use App\Core\Icon;
use App\Core\Translator;
use App\Core\View;

$locale = $locale ?? Translator::locale();
$direction = $direction ?? Translator::direction();
?>
<!DOCTYPE html>
<html lang="<?= View::e($locale) ?>" dir="<?= View::e($direction) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? Translator::get('app.name')) ?></title>
    <link rel="stylesheet" href="<?= View::e(View::url('/assets/css/app.css')) ?>">
    <style>
        body { background: #FFFFFF; color: #0F172A; padding: 24px; }
        .print-container { max-width: 920px; margin: 0 auto; }
    </style>
</head>
<body>
<div class="print-container">
    <div class="no-print" style="margin-bottom:18px;display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <?= Icon::svg('printer', 16) ?>
            <span><?= View::e(Translator::get('common.print_pdf')) ?></span>
        </button>
        <a href="javascript:history.back()" class="btn btn-secondary">
            <?= Icon::svg(Translator::isRtl() ? 'chevron-right' : 'chevron-left', 16) ?>
            <span><?= View::e(Translator::get('common.back')) ?></span>
        </a>
    </div>
    <?= $content ?>
</div>
</body>
</html>
