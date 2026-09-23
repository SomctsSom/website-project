<?php
declare(strict_types=1);

wa_require_perm('users.view');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && wa_can('users.delete')) {
        $res = wa_api('POST', '/api/users/delete', ['id' => $id]);
        wa_flash(wa_api_ok($res) ? 'success' : 'error', (string) ($res['message'] ?? 'Done'));
    } elseif ($action === 'toggle' && wa_can('users.edit')) {
        $view = wa_api('GET', '/api/users/view', ['id' => $id]);
        if (wa_api_ok($view)) {
            $u = $view['data'];
            $res = wa_api('POST', '/api/users/update', [
                'id' => $id,
                'name' => $u['name'],
                'email' => $u['email'],
                'role_id' => $u['role_id'],
                'is_active' => ((int) $u['is_active'] === 1) ? 0 : 1,
            ]);
            wa_flash(wa_api_ok($res) ? 'success' : 'error', (string) ($res['message'] ?? 'Done'));
        }
    }
    wa_redirect('/users?' . http_build_query(array_filter([
        'q' => $_GET['q'] ?? null,
        'status' => $_GET['status'] ?? null,
    ])));
}

$q = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$res = wa_api('GET', '/api/users', ['q' => $q, 'status' => $status, 'page' => $page]);
$items = wa_api_ok($res) ? ($res['data']['items'] ?? []) : [];
$pagination = wa_api_ok($res) ? ($res['data']['pagination'] ?? []) : [];

ob_start();
?>
<div class="page-head">
  <div><h1>Users</h1><p>Search, create, activate, and soft-delete users.</p></div>
  <?php if (wa_can('users.create')): ?><a class="btn" href="<?= wa_e(wa_url('/users/create')) ?>">Create user</a><?php endif; ?>
</div>
<div class="panel">
  <form class="toolbar" method="get">
    <input type="search" name="q" placeholder="Search name or email" value="<?= wa_e($q) ?>">
    <select name="status">
      <option value="">All statuses</option>
      <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
    <button class="btn btn-secondary" type="submit">Filter</button>
  </form>
  <?php if (!wa_api_ok($res)): ?><div class="alert alert-error"><?= wa_e($res['message'] ?? 'Failed') ?></div><?php endif; ?>
  <table class="data">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $row): ?>
      <tr>
        <td><?= wa_e($row['name']) ?></td>
        <td><?= wa_e($row['email']) ?></td>
        <td><?= wa_e($row['role_name']) ?></td>
        <td><?= wa_badge((int) $row['is_active']) ?></td>
        <td><?= wa_e($row['created_at_display'] ?? $row['created_at']) ?></td>
        <td>
          <a href="<?= wa_e(wa_url('/users/view?id=' . $row['id'])) ?>">View</a>
          <?php if (wa_can('users.edit')): ?> · <a href="<?= wa_e(wa_url('/users/edit?id=' . $row['id'])) ?>">Edit</a><?php endif; ?>
          <?php if (wa_can('users.edit')): ?>
            · <form class="confirm-form" method="post"><?= wa_csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><button class="btn-ghost btn" type="submit" style="padding:0.2rem 0.5rem"><?= (int)$row['is_active']===1?'Deactivate':'Activate' ?></button></form>
          <?php endif; ?>
          <?php if (wa_can('users.delete')): ?>
            · <form class="confirm-form" method="post"><?= wa_csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><button class="btn btn-danger" style="padding:0.2rem 0.5rem" type="submit" data-confirm="Soft-delete this user?">Delete</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?= wa_pagination($pagination, '/users', ['q' => $q, 'status' => $status]) ?>
</div>
<?php
wa_render('Users', ob_get_clean());
