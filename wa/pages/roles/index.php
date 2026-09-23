<?php
declare(strict_types=1);

wa_require_perm('roles.view');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && wa_can('roles.delete')) {
    $res = wa_api('POST', '/api/roles/delete', ['id' => (int) ($_POST['id'] ?? 0)]);
    wa_flash(wa_api_ok($res) ? 'success' : 'error', (string) ($res['message'] ?? 'Done'));
    wa_redirect('/roles');
}

$q = trim((string) ($_GET['q'] ?? ''));
$res = wa_api('GET', '/api/roles', ['q' => $q, 'page' => (int) ($_GET['page'] ?? 1)]);
$items = wa_api_ok($res) ? ($res['data']['items'] ?? []) : [];
$pagination = wa_api_ok($res) ? ($res['data']['pagination'] ?? []) : [];

ob_start();
?>
<div class="page-head">
  <div><h1>Roles</h1><p>Assign permissions to roles. System roles are protected.</p></div>
  <?php if (wa_can('roles.create')): ?><a class="btn" href="<?= wa_e(wa_url('/roles/create')) ?>">Create role</a><?php endif; ?>
</div>
<div class="panel">
  <form class="toolbar" method="get"><input type="search" name="q" value="<?= wa_e($q) ?>" placeholder="Search roles"><button class="btn btn-secondary" type="submit">Search</button></form>
  <table class="data">
    <thead><tr><th>Name</th><th>Code</th><th>Permissions</th><th>Users</th><th>System</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $row): ?>
      <tr>
        <td><?= wa_e($row['name']) ?></td>
        <td><?= wa_e($row['code']) ?></td>
        <td><?= (int) $row['permission_count'] ?></td>
        <td><?= (int) $row['user_count'] ?></td>
        <td><?= (int)$row['is_system']===1 ? 'Yes' : 'No' ?></td>
        <td>
          <a href="<?= wa_e(wa_url('/roles/view?id='.$row['id'])) ?>">View</a>
          <?php if (wa_can('roles.edit')): ?> · <a href="<?= wa_e(wa_url('/roles/edit?id='.$row['id'])) ?>">Edit</a><?php endif; ?>
          <?php if (wa_can('roles.delete') && (int)$row['is_system']!==1): ?>
            · <form class="confirm-form" method="post"><?= wa_csrf_field() ?><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-danger" style="padding:0.2rem 0.5rem" type="submit" data-confirm="Soft-delete this role?">Delete</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?= wa_pagination($pagination, '/roles', ['q' => $q]) ?>
</div>
<?php
wa_render('Roles', ob_get_clean());
