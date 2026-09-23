<?php
declare(strict_types=1);

wa_require_perm('roles.view');
$id = (int) ($_GET['id'] ?? 0);
$res = wa_api('GET', '/api/roles/view', ['id' => $id]);
if (!wa_api_ok($res)) {
    wa_flash('error', $res['message'] ?? 'Not found');
    wa_redirect('/roles');
}
$r = $res['data'];
ob_start();
?>
<div class="page-head"><div><h1><?= wa_e($r['name']) ?></h1><p><?= wa_e($r['code']) ?></p></div>
  <div class="actions">
    <?php if (wa_can('roles.edit')): ?><a class="btn" href="<?= wa_e(wa_url('/roles/edit?id='.$r['id'])) ?>">Edit</a><?php endif; ?>
    <a class="btn btn-ghost" href="<?= wa_e(wa_url('/roles')) ?>">Back</a>
  </div>
</div>
<div class="panel">
  <p><?= wa_e((string)$r['description']) ?></p>
  <h3>Permissions</h3>
  <ul>
    <?php foreach ($r['permissions'] ?? [] as $p): ?>
      <li><?= wa_e($p['code']) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php
wa_render('View role', ob_get_clean());
