<?php

use App\Core\Csrf;
use App\Core\DateHelper;
use App\Core\Icon;
use App\Core\Translator;
use App\Core\Ui;
use App\Core\View;

$posts = $posts ?? [];
$media = $media ?? [];
?>
<?= Ui::breadcrumbs([['label' => Translator::get('panel.super_admin'), 'url' => '/admin/dashboard'], ['label' => Translator::get('menu.cms')]]) ?>
<div class="page-header">
    <div><h1 class="page-title"><?= View::e(Translator::get('menu.cms')) ?></h1></div>
</div>

<div class="grid grid-2" style="margin-bottom:20px;align-items:start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('newspaper', 17) ?><span><?= View::e(Translator::get('admin.cms_publish')) ?></span></h2>
        <form method="POST" action="<?= View::e(View::url('/admin/cms/post')) ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('admin.post_type')) ?></label>
                    <select class="form-control" name="type">
                        <option value="news"><?= View::e(Translator::get('status.news')) ?></option>
                        <option value="banner"><?= View::e(Translator::get('status.banner')) ?></option>
                        <option value="activity"><?= View::e(Translator::get('status.activity')) ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= View::e(Translator::get('admin.sort_order')) ?></label>
                    <input class="form-control" type="number" name="sort_order" value="1">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('common.title')) ?></label>
                <input class="form-control" name="title" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.summary')) ?></label>
                <input class="form-control" name="summary">
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.body')) ?></label>
                <textarea class="form-control" name="body" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.image_hint')) ?></label>
                <div class="file-upload"><input class="form-control" type="file" name="image" accept=".png,.jpg,.jpeg,.webp"></div>
            </div>
            <div class="form-group">
                <label class="form-check"><input type="checkbox" name="is_published" value="1" checked> <span><?= View::e(Translator::get('admin.publish_now')) ?></span></label>
            </div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.save')) ?></button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:14px;"><?= Icon::svg('upload', 17) ?><span><?= View::e(Translator::get('admin.upload_gallery')) ?></span></h2>
        <form method="POST" action="<?= View::e(View::url('/admin/cms/media')) ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.image_title')) ?></label>
                <input class="form-control" name="title" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.category')) ?></label>
                <select class="form-control" name="category">
                    <option value="gallery"><?= View::e(Translator::get('status.gallery')) ?></option>
                    <option value="event"><?= View::e(Translator::get('status.event')) ?></option>
                    <option value="banner"><?= View::e(Translator::get('status.banner')) ?></option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= View::e(Translator::get('admin.image_file')) ?></label>
                <div class="file-upload"><input class="form-control" type="file" name="file" accept=".png,.jpg,.jpeg,.webp" required></div>
            </div>
            <button type="submit" class="btn btn-primary"><?= View::e(Translator::get('common.upload')) ?></button>
        </form>

        <div class="grid grid-4" style="gap:10px;margin-top:18px;">
            <?php foreach ($media as $m): ?>
                <img src="<?= View::e(View::url('/media/' . $m['id'])) ?>" alt="<?= View::e($m['title']) ?>" style="width:100%;height:70px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border);">
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card">
    <h2 class="card-title" style="margin-bottom:12px;"><?= View::e(Translator::get('admin.published_posts')) ?> (<?= View::e(DateHelper::number(count($posts))) ?>)</h2>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th><?= View::e(Translator::get('admin.post_type')) ?></th><th><?= View::e(Translator::get('common.title')) ?></th><th><?= View::e(Translator::get('admin.summary')) ?></th><th><?= View::e(Translator::get('common.actions')) ?></th></tr></thead>
            <tbody>
                <?php foreach ($posts as $p): ?>
                    <tr>
                        <td><span class="badge badge-primary"><?= View::e(Ui::statusLabel((string) $p['type'])) ?></span></td>
                        <td><?= View::e($p['title']) ?></td>
                        <td><?= View::e($p['summary']) ?></td>
                        <td>
                            <form method="POST" action="<?= View::e(View::url('/admin/cms/post/' . $p['id'] . '/delete')) ?>" data-confirm="<?= View::e(Translator::get('common.confirm_delete')) ?>">
                                <?= Csrf::field() ?>
                                <button type="submit" class="btn btn-danger btn-sm"><?= Icon::svg('trash', 14) ?><span><?= View::e(Translator::get('common.delete')) ?></span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
