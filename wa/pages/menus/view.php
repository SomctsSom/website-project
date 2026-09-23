<?php
declare(strict_types=1);

wa_require_perm('menus.view');
$id = (int) ($_GET['id'] ?? 0);
$res = wa_api('GET', '/api/menus/view', ['id' => $id]);
if (!wa_api_ok($res)) {
    wa_flash('error', $res['message'] ?? 'Not found');
    wa_redirect('/website-menus');
}
$m = $res['data'];
$list = $m['menu_type'] === 'admin' ? '/admin-menus' : '/website-menus';
ob_start();
?>
<div class="page-head"><div><h1><?= wa_e($m['title']) ?></h1><p><?= wa_e($m['menu_type']) ?> menu</p></div>
  <div class="actions">
    <?php if (wa_can('menus.edit')): ?><a class="btn" href="<?= wa_e(wa_url('/menus/edit?id='.$m['id'])) ?>">Edit</a><?php endif; ?>
    <a class="btn btn-ghost" href="<?= wa_e(wa_url($list)) ?>">Back</a>
  </div>
</div>
<div class="panel">
  <p>URL: <?= wa_e((string)$m['url']) ?> · Order: <?= (int)$m['sort_order'] ?> · <?= wa_badge((int)$m['is_active']) ?></p>
  <h3>Pages</h3>
  <ul>
    <?php foreach ($m['pages'] ?? [] as $p): ?>
      <li><?= wa_e($p['title']) ?> (<?= wa_e($p['slug']) ?>)</li>
    <?php endforeach; ?>
  </ul>
</div>
<?php
wa_render('View menu', ob_get_clean());
