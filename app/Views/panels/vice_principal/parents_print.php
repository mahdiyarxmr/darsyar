<?php

use App\Core\Translator;
use App\Core\View;

$credentials = $credentials ?? [];
?>
<div class="card" style="padding:26px;">
    <h1 style="margin-top:0;font-size:1.4rem;"><?= View::e(Translator::get('report_card.parent_creds_title')) ?></h1>
    <p style="color:#475569;"><?= View::e(Translator::get('report_card.parent_creds_hint')) ?></p>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= View::e(Translator::get('vp.student_code')) ?></th>
                    <th><?= View::e(Translator::get('common.student')) ?></th>
                    <th><?= View::e(Translator::get('common.class')) ?></th>
                    <th><?= View::e(Translator::get('common.username')) ?></th>
                    <th><?= View::e(Translator::get('common.password')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($credentials as $c): ?>
                    <tr>
                        <td><?= View::e($c['student_code']) ?></td>
                        <td><?= View::e($c['student_name']) ?></td>
                        <td><?= View::e($c['class_name']) ?></td>
                        <td><code><?= View::e($c['parent_username']) ?></code></td>
                        <td><code><?= View::e($c['temporary_password']) ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
