<?php
declare(strict_types=1);

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
wa_require_perm($isEdit ? 'users.edit' : 'users.create');

$rolesRes = wa_api('GET', '/api/roles', ['per_page' => 100]);
$roles = wa_api_ok($rolesRes) ? ($rolesRes['data']['items'] ?? []) : [];
$user = [
    'name' => '', 'email' => '', 'role_id' => 0, 'is_active' => 1,
];
if ($isEdit) {
    $view = wa_api('GET', '/api/users/view', ['id' => $id]);
    if (!wa_api_ok($view)) {
        wa_flash('error', $view['message'] ?? 'Not found');
        wa_redirect('/users');
    }
    $user = $view['data'];
}

$error = '';
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $payload = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'role_id' => (int) ($_POST['role_id'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    if (!empty($_POST['password'])) {
        $payload['password'] = (string) $_POST['password'];
    }
    if ($isEdit) {
        $payload['id'] = $id;
        $res = wa_api('POST', '/api/users/update', $payload);
    } else {
        $payload['password'] = (string) ($_POST['password'] ?? '');
        $res = wa_api('POST', '/api/users/create', $payload);
    }
    if (wa_api_ok($res)) {
        wa_flash('success', $res['message'] ?? 'Saved');
        wa_redirect('/users');
    }
    $error = (string) ($res['message'] ?? 'Save failed');
    $user = array_merge($user, $payload);
}

ob_start();
?>
<div class="page-head"><div><h1><?= $isEdit ? 'Edit user' : 'Create user' ?></h1></div>
  <a class="btn btn-ghost" href="<?= wa_e(wa_url('/users')) ?>">Back</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="post" class="form-grid">
    <?= wa_csrf_field() ?>
    <label>Name<input name="name" required value="<?= wa_e((string) $user['name']) ?>"></label>
    <label>Email<input type="email" name="email" required value="<?= wa_e((string) $user['email']) ?>"></label>
    <label>Role
      <select name="role_id" required>
        <option value="">Select role</option>
        <?php foreach ($roles as $r): ?>
          <option value="<?= (int) $r['id'] ?>" <?= (int)$user['role_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= wa_e($r['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Password<?= $isEdit ? ' (leave blank to keep)' : '' ?>
      <input type="password" name="password" <?= $isEdit ? '' : 'required minlength="10"' ?> autocomplete="new-password">
    </label>
    <label><span>Active</span><input type="checkbox" name="is_active" value="1" <?= (int)$user['is_active']===1?'checked':'' ?>></label>
    <div class="actions full"><button class="btn" type="submit">Save</button></div>
  </form>
</div>
<?php
wa_render($isEdit ? 'Edit user' : 'Create user', ob_get_clean());
