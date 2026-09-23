<?php
declare(strict_types=1);

wa_require_perm('users.view');
$id = (int) ($_GET['id'] ?? 0);
$res = wa_api('GET', '/api/users/view', ['id' => $id]);
if (!wa_api_ok($res)) {
    wa_flash('error', $res['message'] ?? 'Not found');
    wa_redirect('/users');
}
$u = $res['data'];
ob_start();
?>
<div class="page-head"><div><h1><?= wa_e($u['name']) ?></h1><p><?= wa_e($u['email']) ?></p></div>
  <div class="actions">
    <?php if (wa_can('users.edit')): ?><a class="btn" href="<?= wa_e(wa_url('/users/edit?id='.$u['id'])) ?>">Edit</a><?php endif; ?>
    <a class="btn btn-ghost" href="<?= wa_e(wa_url('/users')) ?>">Back</a>
  </div>
</div>
<div class="panel">
  <p>Role: <?= wa_e($u['role_name']) ?> (<?= wa_e($u['role_code']) ?>)</p>
  <p>Status: <?= wa_badge((int)$u['is_active']) ?></p>
  <p>Last login: <?= wa_e($u['last_login_at_display'] ?? '—') ?></p>
  <p>Created: <?= wa_e($u['created_at_display'] ?? '') ?></p>
</div>
<?php
wa_render('View user', ob_get_clean());
