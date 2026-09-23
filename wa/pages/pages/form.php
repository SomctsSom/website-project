<?php
declare(strict_types=1);

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
wa_require_perm($isEdit ? 'pages.edit' : 'pages.create');

$page = [
    'page_type' => (string) ($_GET['page_type'] ?? 'website'),
    'title' => '', 'slug' => '', 'template_key' => '', 'is_active' => 1, 'menus' => [],
];
$listMeta = wa_api('GET', '/api/pages', ['page_type' => $page['page_type'], 'per_page' => 1]);
$adminTemplates = wa_api_ok($listMeta) ? ($listMeta['data']['approved_admin_templates'] ?? []) : [];
$websiteTemplates = wa_api_ok($listMeta) ? ($listMeta['data']['approved_website_templates'] ?? []) : [];

if ($isEdit) {
    $view = wa_api('GET', '/api/pages/view', ['id' => $id]);
    if (!wa_api_ok($view)) {
        wa_flash('error', $view['message'] ?? 'Not found');
        wa_redirect('/website-pages');
    }
    $page = $view['data'];
}

$templates = $page['page_type'] === 'admin' ? $adminTemplates : $websiteTemplates;
$menusRes = wa_api('GET', '/api/menus', ['menu_type' => $page['page_type'], 'tree' => 0]);
$menus = wa_api_ok($menusRes) ? ($menusRes['data']['items'] ?? []) : [];
$assignedMenus = array_map(static fn($m) => (int) $m['id'], $page['menus'] ?? []);

$error = '';
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $payload = [
        'page_type' => $page['page_type'],
        'title' => trim((string) ($_POST['title'] ?? '')),
        'slug' => trim((string) ($_POST['slug'] ?? '')),
        'template_key' => trim((string) ($_POST['template_key'] ?? '')),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'menu_ids' => array_map('intval', $_POST['menu_ids'] ?? []),
    ];
    if ($isEdit) {
        $payload['id'] = $id;
        $res = wa_api('POST', '/api/pages/update', $payload);
    } else {
        $res = wa_api('POST', '/api/pages/create', $payload);
    }
    if (wa_api_ok($res)) {
        wa_flash('success', 'Page saved');
        wa_redirect($page['page_type'] === 'admin' ? '/admin-pages' : '/website-pages');
    }
    $error = (string) ($res['message'] ?? 'Save failed');
    $page = array_merge($page, $payload);
}

$list = $page['page_type'] === 'admin' ? '/admin-pages' : '/website-pages';
ob_start();
?>
<div class="page-head"><div><h1><?= $isEdit ? 'Edit page' : 'Create page' ?></h1></div>
  <a class="btn btn-ghost" href="<?= wa_e(wa_url($list)) ?>">Back</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="post" class="form-grid">
    <?= wa_csrf_field() ?>
    <label>Title<input name="title" required value="<?= wa_e((string)$page['title']) ?>"></label>
    <label>Slug<input name="slug" required value="<?= wa_e((string)$page['slug']) ?>"></label>
    <label>Template key
      <select name="template_key" required>
        <?php foreach ($templates as $tk): ?>
          <option value="<?= wa_e($tk) ?>" <?= ($page['template_key'] ?? '') === $tk ? 'selected' : '' ?>><?= wa_e($tk) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label><span>Active</span><input type="checkbox" name="is_active" value="1" <?= (int)$page['is_active']===1?'checked':'' ?>></label>
    <div class="full">
      <strong>Assign to menus (optional)</strong>
      <div class="page-checks">
        <?php foreach ($menus as $m): ?>
          <label class="page-check" style="grid-template-columns:auto 1fr">
            <input type="checkbox" name="menu_ids[]" value="<?= (int)$m['id'] ?>" <?= in_array((int)$m['id'], $assignedMenus, true) ? 'checked' : '' ?>>
            <span><?= wa_e($m['title']) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="actions full"><button class="btn" type="submit">Save</button></div>
  </form>
</div>
<?php
wa_render($isEdit ? 'Edit page' : 'Create page', ob_get_clean());
