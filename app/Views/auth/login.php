<?php

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\View;

$mode = ($mode ?? 'login') === 'register' ? 'register' : 'login';
$classes = $classes ?? [];
?>
<section class="auth-stage">
    <div class="auth-float-wrap" aria-hidden="true">
        <div class="auth-shape auth-shape-1"></div>
        <div class="auth-shape auth-shape-2"></div>
        <div class="auth-shape auth-shape-3"></div>
    </div>

    <div class="auth-shell">
        <div class="auth-showcase">
            <div class="auth-brand-row">
                <img src="<?= View::e(View::url('/assets/img/logo.svg')) ?>" alt="<?= View::e(Translator::get('app.name')) ?>">
                <div>
                    <div style="font-weight:800;font-size:1.25rem;line-height:1.25;"><?= View::e(Translator::get('app.name')) ?></div>
                    <div style="font-size:0.84rem;color:var(--text-muted);"><?= View::e(Translator::get('app.tagline')) ?></div>
                </div>
            </div>

            <div class="auth-highlight-list">
                <div class="auth-highlight-item">
                    <span class="stat-icon"><?= Icon::svg('file-text', 17) ?></span>
                    <span><?= View::e(Translator::get('auth.hero_highlight_1')) ?></span>
                </div>
                <div class="auth-highlight-item">
                    <span class="stat-icon"><?= Icon::svg('target', 17) ?></span>
                    <span><?= View::e(Translator::get('auth.hero_highlight_2')) ?></span>
                </div>
                <div class="auth-highlight-item">
                    <span class="stat-icon"><?= Icon::svg('award', 17) ?></span>
                    <span><?= View::e(Translator::get('auth.hero_highlight_3')) ?></span>
                </div>
            </div>
        </div>

        <div class="auth-card">
            <div id="authSwitcher"
                 class="auth-switcher"
                 data-active="<?= View::e($mode) ?>"
                 data-url-login="<?= View::e(View::url('/login')) ?>"
                 data-url-register="<?= View::e(View::url('/register')) ?>"
                 role="tablist">
                <span class="auth-switcher-thumb" aria-hidden="true"></span>
                <button type="button"
                        class="auth-tab-btn <?= $mode === 'login' ? 'active' : '' ?>"
                        data-auth-tab="login"
                        role="tab"
                        aria-selected="<?= $mode === 'login' ? 'true' : 'false' ?>">
                    <?= Icon::svg('log-in', 16) ?>
                    <span><?= View::e(Translator::get('auth.tab_login')) ?></span>
                </button>
                <button type="button"
                        class="auth-tab-btn <?= $mode === 'register' ? 'active' : '' ?>"
                        data-auth-tab="register"
                        role="tab"
                        aria-selected="<?= $mode === 'register' ? 'true' : 'false' ?>">
                    <?= Icon::svg('user-plus', 16) ?>
                    <span><?= View::e(Translator::get('auth.tab_register')) ?></span>
                </button>
            </div>

            <!-- Login Form Panel -->
            <div class="auth-panel <?= $mode === 'login' ? 'active' : '' ?>" data-auth-panel="login">
                <h1 style="margin:0 0 18px;font-size:1.35rem;font-weight:800;"><?= View::e(Translator::get('auth.login_title')) ?></h1>
                <form method="POST" action="<?= View::e(View::url('/login')) ?>">
                    <?= Csrf::field() ?>
                    <div class="form-group">
                        <label class="form-label" for="identifier"><?= View::e(Translator::get('auth.username_or_email')) ?></label>
                        <input class="form-control" id="identifier" name="identifier" type="text" required autocomplete="username" autofocus>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password"><?= View::e(Translator::get('auth.password')) ?></label>
                        <div class="pw-wrap">
                            <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password">
                            <button type="button" class="pw-toggle" data-toggle-pw="password" aria-label="<?= View::e(Translator::get('auth.toggle_password')) ?>">
                                <span data-eye-show style="display:inline-flex;"><?= Icon::svg('eye', 17) ?></span>
                                <span data-eye-hide style="display:none;"><?= Icon::svg('eye-off', 17) ?></span>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:6px;">
                        <?= Icon::svg('log-in', 18) ?>
                        <span><?= View::e(Translator::get('auth.sign_in')) ?></span>
                    </button>
                </form>
            </div>

            <!-- Register Form Panel -->
            <div class="auth-panel <?= $mode === 'register' ? 'active' : '' ?>" data-auth-panel="register">
                <h1 style="margin:0 0 18px;font-size:1.35rem;font-weight:800;"><?= View::e(Translator::get('auth.register_title')) ?></h1>
                <form method="POST" action="<?= View::e(View::url('/register')) ?>">
                    <?= Csrf::field() ?>
                    <div class="form-group">
                        <label class="form-label"><?= View::e(Translator::get('auth.select_role')) ?></label>
                        <div class="role-picker">
                            <label class="role-option">
                                <input type="radio" name="role" value="student" checked>
                                <?= Icon::svg('graduation-cap', 17) ?>
                                <span><?= View::e(Translator::get('roles.student')) ?></span>
                            </label>
                            <label class="role-option">
                                <input type="radio" name="role" value="parent">
                                <?= Icon::svg('users', 17) ?>
                                <span><?= View::e(Translator::get('roles.parent')) ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="reg_full_name"><?= View::e(Translator::get('common.full_name')) ?></label>
                            <input class="form-control" id="reg_full_name" name="full_name" type="text" required minlength="2">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="reg_username"><?= View::e(Translator::get('common.username')) ?></label>
                            <input class="form-control" id="reg_username" name="username" type="text" required minlength="3" pattern="[a-zA-Z0-9_.\-]{3,60}" dir="ltr">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="reg_phone"><?= View::e(Translator::get('auth.phone_optional')) ?></label>
                            <input class="form-control" id="reg_phone" name="phone" type="text" dir="ltr">
                        </div>
                        <div class="form-group" id="registerClassWrap">
                            <label class="form-label" for="reg_class_id"><?= View::e(Translator::get('auth.select_class')) ?></label>
                            <select class="form-control" id="reg_class_id" name="class_id">
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= View::e((string) $c['id']) ?>"><?= View::e($c['name']) ?><?= !empty($c['grade_name']) ? ' (' . View::e($c['grade_name']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="reg_email"><?= View::e(Translator::get('auth.email_optional')) ?></label>
                        <input class="form-control" id="reg_email" name="email" type="email" dir="ltr">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="reg_password"><?= View::e(Translator::get('auth.new_password')) ?></label>
                            <div class="pw-wrap">
                                <input class="form-control" id="reg_password" name="password" type="password" required minlength="8" autocomplete="new-password">
                                <button type="button" class="pw-toggle" data-toggle-pw="reg_password" aria-label="<?= View::e(Translator::get('auth.toggle_password')) ?>">
                                    <span data-eye-show style="display:inline-flex;"><?= Icon::svg('eye', 17) ?></span>
                                    <span data-eye-hide style="display:none;"><?= Icon::svg('eye-off', 17) ?></span>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="reg_confirm"><?= View::e(Translator::get('auth.confirm_password')) ?></label>
                            <input class="form-control" id="reg_confirm" name="confirm_password" type="password" required minlength="8" autocomplete="new-password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:4px;">
                        <?= Icon::svg('user-plus', 18) ?>
                        <span><?= View::e(Translator::get('auth.create_account')) ?></span>
                    </button>
                </form>
            </div>

            <?php if (Config::get('app.env') === 'local'): ?>
                <details class="demo-drawer-box">
                    <summary style="cursor:pointer;font-size:0.84rem;font-weight:700;color:var(--text-muted);display:flex;align-items:center;gap:6px;list-style:none;">
                        <?= Icon::svg('sparkles', 15) ?>
                        <span><?= View::e(Translator::get('auth.show_demo_accounts')) ?></span>
                    </summary>
                    <div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px;">
                        <?php foreach (['superadmin', 'director', 'viceprincipal', 'counselor', 'teacher1', 'student1', 'parent1'] as $demoUser): ?>
                            <button type="button" class="demo-chip-btn" data-demo-user="<?= View::e($demoUser) ?>" data-demo-pass="School@2026"><?= View::e($demoUser) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:8px;font-size:0.8rem;color:var(--text-muted);">
                        <?= View::e(Translator::get('auth.dev_password_label')) ?>: <code>School@2026</code>
                    </div>
                </details>
            <?php endif; ?>
        </div>
    </div>
</section>
