<?php
declare(strict_types=1);

wa_require_perm('pages.view');
$id = (int) ($_GET['id'] ?? 0);
$res = wa_api('GET', '/api/pages/view', ['id' => $id]);
if (!wa_api_ok($res)) {
    wa_flash('error', $res['message'] ?? 'Not found');
    wa_redirect('/website-pages');
}
$p = $res['data'];
$list = $p['page_type'] === 'admin' ? '/admin-pages' : '/website-pages';
ob_start();
?>
<div class="page-head"><div><h1><?= wa_e($p['title']) ?></h1><p>/<?= wa_e($p['slug']) ?></p></div>
  <div class="actions">
    <?php if (wa_can('pages.edit')): ?><a class="btn" href="<?= wa_e(wa_url('/pages/edit?id='.$p['id'])) ?>">Edit</a><?php endif; ?>
    <a class="btn btn-ghost" href="<?= wa_e(wa_url($list)) ?>">Back</a>
  </div>
</div>
<div class="panel">
  <p>Type: <?= wa_e($p['page_type']) ?> · Template: <?= wa_e($p['template_key']) ?> · <?= wa_badge((int)$p['is_active']) ?></p>
  <h3>Menus</h3>
  <ul><?php foreach ($p['menus'] ?? [] as $m): ?><li><?= wa_e($m['title']) ?></li><?php endforeach; ?></ul>
</div>
<?php
wa_render('View page', ob_get_clean());
