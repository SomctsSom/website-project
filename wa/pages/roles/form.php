<?php
declare(strict_types=1);

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
wa_require_perm($isEdit ? 'roles.edit' : 'roles.create');

$role = ['name' => '', 'code' => '', 'description' => ''];
$assigned = [];
if ($isEdit) {
    $view = wa_api('GET', '/api/roles/view', ['id' => $id]);
    if (!wa_api_ok($view)) {
        wa_flash('error', $view['message'] ?? 'Not found');
        wa_redirect('/roles');
    }
    $role = $view['data'];
    $assigned = array_map(static fn($p) => (int) $p['id'], $role['permissions'] ?? []);
}
$permsRes = wa_api('GET', '/api/permissions', ['per_page' => 200]);
$perms = wa_api_ok($permsRes) ? ($permsRes['data']['items'] ?? []) : [];

$error = '';
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if ($isEdit) {
        $res = wa_api('POST', '/api/roles/update', [
            'id' => $id,
            'name' => trim((string) ($_POST['name'] ?? '')),
            'code' => trim((string) ($_POST['code'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
        ]);
        if (wa_api_ok($res)) {
            $permIds = array_map('intval', $_POST['permission_ids'] ?? []);
            $assign = wa_api('POST', '/api/roles/assign-permissions', ['id' => $id, 'permission_ids' => $permIds]);
            if (wa_api_ok($assign)) {
                wa_flash('success', 'Role updated');
                wa_redirect('/roles/view?id=' . $id);
            }
            $error = (string) ($assign['message'] ?? 'Permission assign failed');
        } else {
            $error = (string) ($res['message'] ?? 'Update failed');
        }
    } else {
        $res = wa_api('POST', '/api/roles/create', [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'code' => trim((string) ($_POST['code'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
        ]);
        if (wa_api_ok($res)) {
            $newId = (int) ($res['data']['id'] ?? 0);
            $permIds = array_map('intval', $_POST['permission_ids'] ?? []);
            if ($permIds) {
                wa_api('POST', '/api/roles/assign-permissions', ['id' => $newId, 'permission_ids' => $permIds]);
            }
            wa_flash('success', 'Role created');
            wa_redirect('/roles');
        }
        $error = (string) ($res['message'] ?? 'Create failed');
    }
    $role['name'] = $_POST['name'] ?? '';
    $role['code'] = $_POST['code'] ?? '';
    $role['description'] = $_POST['description'] ?? '';
    $assigned = array_map('intval', $_POST['permission_ids'] ?? []);
}

ob_start();
?>
<div class="page-head"><div><h1><?= $isEdit ? 'Edit role' : 'Create role' ?></h1></div>
  <a class="btn btn-ghost" href="<?= wa_e(wa_url('/roles')) ?>">Back</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="post" class="form-grid">
    <?= wa_csrf_field() ?>
    <label>Name<input name="name" required value="<?= wa_e((string)$role['name']) ?>"></label>
    <label>Code<input name="code" value="<?= wa_e((string)$role['code']) ?>" <?= !empty($role['is_system']) ? 'readonly' : '' ?>></label>
    <label class="full">Description<textarea name="description" rows="2"><?= wa_e((string)($role['description'] ?? '')) ?></textarea></label>
    <div class="full">
      <strong>Permissions</strong>
      <div class="page-checks">
        <?php foreach ($perms as $p): ?>
          <label class="page-check" style="grid-template-columns:auto 1fr">
            <input type="checkbox" name="permission_ids[]" value="<?= (int)$p['id'] ?>" <?= in_array((int)$p['id'], $assigned, true) ? 'checked' : '' ?>>
            <span><?= wa_e($p['code']) ?> <span class="muted"><?= wa_e((string)$p['description']) ?></span></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="actions full"><button class="btn" type="submit">Save</button></div>
  </form>
</div>
<?php
wa_render($isEdit ? 'Edit role' : 'Create role', ob_get_clean());
