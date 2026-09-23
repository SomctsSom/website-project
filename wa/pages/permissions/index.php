<?php
declare(strict_types=1);

wa_require_perm('permissions.view');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create' && wa_can('permissions.create')) {
        $res = wa_api('POST', '/api/permissions/create', [
            'module' => $_POST['module'] ?? '',
            'action' => $_POST['perm_action'] ?? '',
            'description' => $_POST['description'] ?? '',
        ]);
        wa_flash(wa_api_ok($res) ? 'success' : 'error', (string) ($res['message'] ?? 'Done'));
    } elseif ($action === 'delete' && wa_can('permissions.delete')) {
        $res = wa_api('POST', '/api/permissions/delete', ['id' => (int) ($_POST['id'] ?? 0)]);
        wa_flash(wa_api_ok($res) ? 'success' : 'error', (string) ($res['message'] ?? 'Done'));
    }
    wa_redirect('/permissions');
}

$q = trim((string) ($_GET['q'] ?? ''));
$res = wa_api('GET', '/api/permissions', ['q' => $q, 'per_page' => 100]);
$items = wa_api_ok($res) ? ($res['data']['items'] ?? []) : [];

ob_start();
?>
<div class="page-head"><div><h1>Permissions</h1><p>System permissions cannot be soft-deleted.</p></div></div>
<div class="panel">
  <?php if (wa_can('permissions.create')): ?>
  <form method="post" class="form-grid" style="margin-bottom:1.2rem">
    <?= wa_csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <label>Module<input name="module" required></label>
    <label>Action<input name="perm_action" required></label>
    <label class="full">Description<input name="description"></label>
    <div class="actions full"><button class="btn" type="submit">Add permission</button></div>
  </form>
  <?php endif; ?>
  <form class="toolbar" method="get"><input type="search" name="q" value="<?= wa_e($q) ?>"><button class="btn btn-secondary" type="submit">Search</button></form>
  <table class="data">
    <thead><tr><th>Code</th><th>Module</th><th>Action</th><th>System</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $row): ?>
      <tr>
        <td><?= wa_e($row['code']) ?></td>
        <td><?= wa_e($row['module']) ?></td>
        <td><?= wa_e($row['action']) ?></td>
        <td><?= (int)$row['is_system']===1?'Yes':'No' ?></td>
        <td>
          <?php if (wa_can('permissions.delete') && (int)$row['is_system']!==1): ?>
            <form class="confirm-form" method="post"><?= wa_csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-danger" style="padding:0.2rem 0.5rem" type="submit" data-confirm="Soft-delete this permission?">Delete</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
wa_render('Permissions', ob_get_clean());
