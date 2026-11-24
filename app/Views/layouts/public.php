<?php

use App\Core\Csrf;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\View;
use App\Modules\Auth\AuthService;

$locale = $locale ?? Translator::locale();
$direction = $direction ?? Translator::direction();
$theme = $theme ?? 'light';
$authUser = $authUser ?? AuthService::user();
$flash = $flash ?? [];
$currentUri = urlencode(View::currentRequestUri());
?>
<!DOCTYPE html>
<html lang="<?= View::e($locale) ?>" dir="<?= View::e($direction) ?>" data-theme="<?= View::e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= View::e(Csrf::token()) ?>">
    <title><?= View::e($title ?? Translator::get('app.full_name')) ?></title>
    <link rel="stylesheet" href="<?= View::e(View::url('/assets/css/app.css')) ?>">
</head>
<body>
<header class="public-header no-print">
    <div class="container public-nav">
        <a href="<?= View::e(View::url('/')) ?>" style="display:flex;align-items:center;gap:10px;color:var(--text);font-weight:800;font-size:1.05rem;">
            <img src="<?= View::e(View::url('/assets/img/logo.svg')) ?>" alt="<?= View::e(Translator::get('app.name')) ?>" style="width:34px;height:34px;">
            <span><?= View::e($schoolName ?? Translator::get('app.name')) ?></span>
        </a>
        <nav class="public-links" aria-label="<?= View::e(Translator::get('nav.menu')) ?>">
            <a class="public-nav-link" href="<?= View::e(View::url('/')) ?>"><?= View::e(Translator::get('nav.home')) ?></a>
            <a class="public-nav-link" href="<?= View::e(View::url('/news')) ?>"><?= View::e(Translator::get('nav.news')) ?></a>
            <a class="public-nav-link" href="<?= View::e(View::url('/events')) ?>"><?= View::e(Translator::get('nav.events')) ?></a>
            <div class="lang-switch" role="group" aria-label="<?= View::e(Translator::get('nav.language')) ?>">
                <a class="lang-pill <?= $locale === 'fa' ? 'active' : '' ?>" href="<?= View::e(View::url('/locale/fa?redirect=' . $currentUri)) ?>"><?= View::e(Translator::get('nav.persian')) ?></a>
                <a class="lang-pill <?= $locale === 'en' ? 'active' : '' ?>" href="<?= View::e(View::url('/locale/en?redirect=' . $currentUri)) ?>"><?= View::e(Translator::get('nav.english')) ?></a>
            </div>
            <button type="button" id="themeToggleBtn" class="btn btn-secondary btn-sm btn-icon"
                    data-endpoint="<?= View::e(View::url('/api/theme')) ?>"
                    data-label-light="<?= View::e(Translator::get('nav.theme.light')) ?>"
                    data-label-dark="<?= View::e(Translator::get('nav.theme.dark')) ?>"
                    aria-label="<?= View::e($theme === 'dark' ? Translator::get('nav.theme.light') : Translator::get('nav.theme.dark')) ?>">
                <?= Icon::svg($theme === 'dark' ? 'sun' : 'moon', 16) ?>
            </button>
            <?php if ($authUser): ?>
                <a href="<?= View::e(View::url('/dashboard')) ?>" class="btn btn-primary btn-sm">
                    <?= Icon::svg('layout-dashboard', 15) ?>
                    <span><?= View::e(Translator::get('nav.dashboard')) ?></span>
                </a>
            <?php else: ?>
                <a href="<?= View::e(View::url('/login')) ?>" class="btn btn-secondary btn-sm">
                    <?= Icon::svg('log-in', 15) ?>
                    <span><?= View::e(Translator::get('nav.login')) ?></span>
                </a>
                <a href="<?= View::e(View::url('/register')) ?>" class="btn btn-primary btn-sm">
                    <?= Icon::svg('user-plus', 15) ?>
                    <span><?= View::e(Translator::get('nav.register')) ?></span>
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main>
    <?php if (!empty($flash['success'])): ?>
        <div class="container" style="margin-top:16px;"><div class="alert alert-success"><?= Icon::svg('check-circle', 18) ?><span><?= View::e($flash['success']) ?></span></div></div>
    <?php endif; ?>
    <?php if (!empty($flash['error'])): ?>
        <div class="container" style="margin-top:16px;"><div class="alert alert-error"><?= Icon::svg('alert-circle', 18) ?><span><?= View::e($flash['error']) ?></span></div></div>
    <?php endif; ?>
    <?php if (!empty($flash['warning'])): ?>
        <div class="container" style="margin-top:16px;"><div class="alert alert-warning"><?= Icon::svg('alert-triangle', 18) ?><span><?= View::e($flash['warning']) ?></span></div></div>
    <?php endif; ?>
    <?= $content ?>
</main>

<script src="<?= View::e(View::url('/assets/js/app.js')) ?>"></script>
</body>
</html>
