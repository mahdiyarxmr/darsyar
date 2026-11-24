<?php

use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$settings = $settings ?? [];
$announcements = $announcements ?? [];
$news = $news ?? [];
$banners = $banners ?? [];
$activities = $activities ?? [];
$events = $events ?? [];
$gallery = $gallery ?? [];
?>
<section class="hero">
    <div class="container hero-inner">
        <div>
            <span class="badge badge-primary"><?= View::e(Translator::get('public.hero_badge')) ?></span>
            <h1><?= View::e($settings['hero_title'] ?? Translator::get('app.full_name')) ?></h1>
            <p><?= View::e($settings['hero_subtitle'] ?? $settings['school_tagline'] ?? Translator::get('app.tagline')) ?></p>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:22px;">
                <a href="<?= View::e(View::url('/login')) ?>" class="btn btn-primary btn-lg">
                    <?= Icon::svg('log-in', 17) ?>
                    <span><?= View::e(Translator::get('nav.login')) ?></span>
                </a>
                <a href="<?= View::e(View::url('/register')) ?>" class="btn btn-secondary btn-lg">
                    <?= Icon::svg('user-plus', 17) ?>
                    <span><?= View::e(Translator::get('nav.register')) ?></span>
                </a>
            </div>
        </div>
        <div class="hero-panel">
            <div style="font-weight:800;font-size:1.05rem;"><?= View::e($settings['school_name'] ?? Translator::get('app.name')) ?></div>
            <p style="font-size:0.88rem;color:var(--text-muted);margin:0;"><?= View::e($settings['school_intro'] ?? Translator::get('app.tagline')) ?></p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <span class="badge badge-primary"><?= Icon::svg('layers', 14) ?> <?= View::e(Translator::get('public.feature_panels')) ?></span>
                <span class="badge badge-success"><?= Icon::svg('file-text', 14) ?> <?= View::e(Translator::get('public.feature_exams')) ?></span>
                <span class="badge badge-info"><?= Icon::svg('target', 14) ?> <?= View::e(Translator::get('public.feature_counseling')) ?></span>
            </div>
        </div>
    </div>
</section>

<div class="container" style="padding-bottom:48px;">
    <?php if (!empty($banners)): ?>
        <div class="grid grid-3" style="margin-bottom:24px;">
            <?php foreach ($banners as $b): ?>
                <div class="card card-interactive" style="padding:0;overflow:hidden;">
                    <?php if (!empty($b['image_path'])): ?>
                        <img src="<?= View::e(View::url($b['image_path'])) ?>" alt="<?= View::e($b['title']) ?>" style="width:100%;height:165px;object-fit:cover;border-bottom:1.5px solid var(--border);">
                    <?php endif; ?>
                    <div style="padding:16px;">
                        <h3 style="margin:0 0 6px;font-size:1rem;font-weight:800;"><?= View::e($b['title']) ?></h3>
                        <p style="margin:0;color:var(--text-muted);font-size:0.86rem;"><?= View::e($b['summary']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-2" style="margin-bottom:22px;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= Icon::svg('megaphone', 18) ?><span><?= View::e(Translator::get('public.latest_announcements')) ?></span></h2>
            </div>
            <?php if (empty($announcements)): ?>
                <?= Ui::emptyState(Translator::get('common.no_data'), 'megaphone') ?>
            <?php else: ?>
                <div style="display:grid;gap:12px;">
                    <?php foreach ($announcements as $a): ?>
                        <div style="padding-bottom:10px;border-bottom:1px solid var(--border-subtle);">
                            <div style="font-weight:700;font-size:0.93rem;"><?= View::e($a['title']) ?></div>
                            <div style="font-size:0.85rem;color:var(--text-muted);margin-top:3px;"><?= View::e($a['body']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= Icon::svg('calendar', 18) ?><span><?= View::e(Translator::get('public.upcoming_events')) ?></span></h2>
                <a href="<?= View::e(View::url('/events')) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.view')) ?></a>
            </div>
            <?php if (empty($events)): ?>
                <?= Ui::emptyState(Translator::get('public.no_events'), 'calendar') ?>
            <?php else: ?>
                <?php foreach ($events as $ev): ?>
                    <div style="padding:10px 0;border-bottom:1px solid var(--border-subtle);display:flex;justify-content:space-between;gap:12px;align-items:center;">
                        <div>
                            <div style="font-weight:700;font-size:0.93rem;"><?= View::e($ev['title']) ?></div>
                            <div style="font-size:0.84rem;color:var(--text-muted);"><?= View::e($ev['location'] ?? '') ?></div>
                        </div>
                        <span class="badge badge-primary"><?= View::e(DateHelper::format($ev['event_date'], false)) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= Icon::svg('newspaper', 18) ?><span><?= View::e(Translator::get('public.school_news')) ?></span></h2>
                <a href="<?= View::e(View::url('/news')) ?>" class="btn btn-secondary btn-sm"><?= View::e(Translator::get('common.view')) ?></a>
            </div>
            <?php if (empty($news)): ?>
                <?= Ui::emptyState(Translator::get('public.no_news'), 'newspaper') ?>
            <?php else: ?>
                <?php foreach ($news as $n): ?>
                    <div style="padding:10px 0;border-bottom:1px solid var(--border-subtle);">
                        <div style="font-weight:700;font-size:0.93rem;"><?= View::e($n['title']) ?></div>
                        <div style="font-size:0.85rem;color:var(--text-muted);margin-top:2px;"><?= View::e($n['summary'] ?: $n['body']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= Icon::svg('building', 18) ?><span><?= View::e(Translator::get('public.contact_us')) ?></span></h2>
            </div>
            <div style="display:grid;gap:10px;font-size:0.9rem;">
                <div><strong><?= View::e(Translator::get('common.phone')) ?>:</strong> <span dir="ltr"><?= View::e(DateHelper::localizeDigits($settings['school_phone'] ?? '-')) ?></span></div>
                <div><strong><?= View::e(Translator::get('common.email')) ?>:</strong> <span dir="ltr"><?= View::e($settings['school_email'] ?? '-') ?></span></div>
                <div><strong><?= View::e(Translator::get('admin.school_address')) ?>:</strong> <?= View::e($settings['school_address'] ?? '-') ?></div>
            </div>
        </div>
    </div>
</div>
