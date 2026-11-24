<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\View;
use App\Modules\Auth\AuthService;
use App\Modules\Notifications\NotificationService;

$locale = $locale ?? Translator::locale();
$direction = $direction ?? Translator::direction();
$theme = $theme ?? 'light';
$authUser = $authUser ?? AuthService::user();
$flash = $flash ?? [];
$role = $authUser['role'] ?? '';
$unreadCount = $authUser ? NotificationService::unreadCount((int) $authUser['id']) : 0;
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$currentUri = urlencode(View::currentRequestUri());
?>
<!DOCTYPE html>
<html lang="<?= View::e($locale) ?>" dir="<?= View::e($direction) ?>" data-theme="<?= View::e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= View::e(Csrf::token()) ?>">
    <title><?= View::e(($title ?? Translator::get('app.name')) . ' — ' . Translator::get('app.name')) ?></title>
    <link rel="stylesheet" href="<?= View::e(View::url('/assets/css/app.css')) ?>">
</head>
<body>
<div class="app-shell">
    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>
    <aside id="appSidebar" class="sidebar no-print" aria-label="<?= View::e(Translator::get('nav.menu')) ?>">
        <div class="sidebar-brand">
            <img src="<?= View::e(View::url('/assets/img/logo.svg')) ?>" alt="<?= View::e(Translator::get('app.name')) ?>">
            <div>
                <div class="sidebar-brand-title"><?= View::e(Translator::get('app.name')) ?></div>
                <div class="sidebar-brand-role"><?= View::e(Translator::get('roles.' . $role)) ?></div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <?= View::partial('sidebar_nav', ['role' => $role, 'currentPath' => $currentPath, 'unreadCount' => $unreadCount]) ?>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <span><?= View::e($authUser['full_name'] ?? '') ?></span>
                <span class="badge badge-muted"><?= View::e($authUser['username'] ?? '') ?></span>
            </div>
            <form method="POST" action="<?= View::e(View::url('/logout')) ?>">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;">
                    <?= Icon::svg('log-out', 16) ?>
                    <span><?= View::e(Translator::get('nav.logout')) ?></span>
                </button>
            </form>
        </div>
    </aside>

    <div class="main-area">
        <header class="topbar no-print">
            <div class="topbar-left">
                <button type="button" id="menuToggleBtn" class="menu-toggle" aria-label="<?= View::e(Translator::get('nav.menu')) ?>">
                    <?= Icon::svg('menu', 18) ?>
                </button>
                <a href="<?= View::e(View::url('/')) ?>" class="btn btn-secondary btn-sm" aria-label="<?= View::e(Translator::get('nav.back_home')) ?>">
                    <?= Icon::svg('globe', 15) ?>
                    <span><?= View::e(Translator::get('nav.back_home')) ?></span>
                </a>
            </div>
            <div class="topbar-right">
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
                <a href="<?= View::e(View::url('/notifications')) ?>" class="btn btn-secondary btn-sm" aria-label="<?= View::e(Translator::get('nav.notifications')) ?>">
                    <?= Icon::svg('bell', 15) ?>
                    <span><?= View::e(DateHelper::number($unreadCount)) ?></span>
                </a>
            </div>
        </header>

        <main class="page-content">
            <?php if (!empty($flash['success'])): ?>
                <div class="alert alert-success" role="status"><?= Icon::svg('check-circle', 18) ?><span><?= View::e($flash['success']) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($flash['error'])): ?>
                <div class="alert alert-error" role="alert"><?= Icon::svg('alert-circle', 18) ?><span><?= View::e($flash['error']) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($flash['warning'])): ?>
                <div class="alert alert-warning" role="status"><?= Icon::svg('alert-triangle', 18) ?><span><?= View::e($flash['warning']) ?></span></div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</div>

<!-- Reusable Confirmation Modal -->
<div id="confirmModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
    <div class="modal">
        <div class="modal-header">
            <span id="confirmModalTitle"><?= View::e(Translator::get('common.confirm_title')) ?></span>
            <button type="button" class="btn btn-ghost btn-sm btn-icon" data-modal-close aria-label="<?= View::e(Translator::get('common.close')) ?>"><?= Icon::svg('x', 16) ?></button>
        </div>
        <div class="modal-body">
            <p id="confirmModalMessage" style="margin:0;"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-modal-close><?= View::e(Translator::get('common.cancel')) ?></button>
            <button type="button" id="confirmModalAccept" class="btn btn-danger btn-sm"><?= View::e(Translator::get('common.yes')) ?></button>
        </div>
    </div>
</div>

<div id="toastContainer" class="toast-container" aria-live="polite"></div>
<script src="<?= View::e(View::url('/assets/js/app.js')) ?>"></script>
</body>
</html>
