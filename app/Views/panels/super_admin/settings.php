<?php

use App\Core\Csrf;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$settings = $settings ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.super_admin'), 'url' => '/admin/dashboard'], ['label' => Translator::get('menu.settings')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.settings')) ?></h1></div>
    <form method="POST" action="<?= View::e(View::url('/admin/settings/test-skyroom')) ?>">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-secondary btn-sm">
            <?= Icon::svg('video', 15) ?>
            <span><?= View::e(Translator::get('admin.skyroom_test_btn')) ?></span>
        </button>
    </form>
</div>

<div class="card" style="max-width:840px;">
    <form method="POST" action="<?= View::e(View::url('/admin/settings')) ?>">
        <?= Csrf::field() ?>
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('building', 17) ?><span><?= View::e(Translator::get('admin.school_info_hero')) ?></span></h2>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.school_name')) ?></label>
                <input class="form-control" name="school_name" value="<?= View::e($settings['school_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.school_tagline')) ?></label>
                <input class="form-control" name="school_tagline" value="<?= View::e($settings['school_tagline'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label"><?= View::e(Translator::get('admin.school_intro')) ?></label>
            <textarea class="form-control" name="school_intro" rows="3"><?= View::e($settings['school_intro'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.hero_title')) ?></label>
                <input class="form-control" name="hero_title" value="<?= View::e($settings['hero_title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.hero_subtitle')) ?></label>
                <input class="form-control" name="hero_subtitle" value="<?= View::e($settings['hero_subtitle'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.school_phone')) ?></label>
                <input class="form-control" name="school_phone" value="<?= View::e($settings['school_phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.school_email')) ?></label>
                <input class="form-control" name="school_email" value="<?= View::e($settings['school_email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.school_address')) ?></label>
                <input class="form-control" name="school_address" value="<?= View::e($settings['school_address'] ?? '') ?>">
            </div>
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('globe', 17) ?><span><?= View::e(Translator::get('admin.lang_theme_settings')) ?></span></h2>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.default_locale')) ?></label>
                <select class="form-control" name="default_locale">
                    <option value="fa" <?= ($settings['default_locale'] ?? 'fa') === 'fa' ? 'selected' : '' ?>><?= View::e(Translator::get('nav.persian')) ?></option>
                    <option value="en" <?= ($settings['default_locale'] ?? 'fa') === 'en' ? 'selected' : '' ?>><?= View::e(Translator::get('nav.english')) ?></option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.default_theme')) ?></label>
                <select class="form-control" name="default_theme">
                    <option value="light" <?= ($settings['default_theme'] ?? 'light') === 'light' ? 'selected' : '' ?>><?= View::e(Translator::get('nav.theme.light')) ?></option>
                    <option value="dark" <?= ($settings['default_theme'] ?? 'light') === 'dark' ? 'selected' : '' ?>><?= View::e(Translator::get('nav.theme.dark')) ?></option>
                </select>
            </div>
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('video', 17) ?><span><?= View::e(Translator::get('admin.skyroom_settings')) ?></span></h2>
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="skyroom_enabled" value="1" <?= ($settings['skyroom_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                <span><?= View::e(Translator::get('admin.skyroom_enable')) ?></span>
            </label>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.skyroom_api_url')) ?></label>
                <input class="form-control" name="skyroom_api_url" value="<?= View::e($settings['skyroom_api_url'] ?? 'https://www.skyroom.online/skyroom/api/') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.skyroom_api_key')) ?></label>
                <input class="form-control" type="password" name="skyroom_api_key" placeholder="<?= !empty($settings['skyroom_api_key']) ? '••••••••••••' : '' ?>" autocomplete="off">
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><?= Icon::svg('check', 16) ?><span><?= View::e(Translator::get('common.save')) ?></span></button>
    </form>
</div>
