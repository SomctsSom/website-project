<?php
declare(strict_types=1);

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
wa_require_perm($isEdit ? 'pages.edit' : 'pages.create');

$page = [
    'page_type' => (string) ($_GET['page_type'] ?? 'website'),
    'title' => '', 'slug' => '', 'template_key' => '', 'is_active' => 1, 'menus' => [],
    'banner_eyebrow' => '', 'banner_title' => '', 'banner_subtitle' => '',
    'banner_enabled' => 1,
    'banner_size_preset' => 'md',
    'banner_image_path' => null, 'banner_image_url' => null,
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
$isWebsite = ($page['page_type'] ?? '') === 'website';

$error = '';
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $menuIds = array_map('intval', $_POST['menu_ids'] ?? []);
    $fields = [
        'page_type' => (string) $page['page_type'],
        'title' => trim((string) ($_POST['title'] ?? '')),
        'slug' => trim((string) ($_POST['slug'] ?? '')),
        'template_key' => trim((string) ($_POST['template_key'] ?? ($isWebsite ? 'default' : ''))),
        'is_active' => isset($_POST['is_active']) ? '1' : '0',
        'menu_ids' => json_encode($menuIds, JSON_UNESCAPED_UNICODE),
    ];
    if ($isWebsite) {
        $fields['banner_enabled'] = isset($_POST['banner_enabled']) ? '1' : '0';
        $fields['banner_size_preset'] = trim((string) ($_POST['banner_size_preset'] ?? 'md'));
        $fields['banner_eyebrow'] = trim((string) ($_POST['banner_eyebrow'] ?? ''));
        $fields['banner_title'] = trim((string) ($_POST['banner_title'] ?? ''));
        $fields['banner_subtitle'] = trim((string) ($_POST['banner_subtitle'] ?? ''));
        if (!empty($_POST['clear_banner_image'])) {
            $fields['clear_banner_image'] = '1';
        }
    }
    if ($isEdit) {
        $fields['id'] = (string) $id;
        if ($isWebsite) {
            $res = wa_api_multipart('/api/pages/update', $fields, $_FILES['banner_image'] ?? [], 'banner_image');
        } else {
            $res = wa_api('POST', '/api/pages/update', array_merge($fields, ['menu_ids' => $menuIds, 'id' => $id, 'is_active' => (int) $fields['is_active']]));
        }
    } else {
        if ($isWebsite) {
            $res = wa_api_multipart('/api/pages/create', $fields, $_FILES['banner_image'] ?? [], 'banner_image');
        } else {
            $res = wa_api('POST', '/api/pages/create', array_merge($fields, ['menu_ids' => $menuIds, 'is_active' => (int) $fields['is_active']]));
        }
    }
    if (wa_api_ok($res)) {
        wa_flash('success', 'Page saved');
        wa_redirect($page['page_type'] === 'admin' ? '/admin-pages' : '/website-pages');
    }
    $error = (string) ($res['message'] ?? 'Save failed');
    $page = array_merge($page, [
        'title' => $fields['title'],
        'slug' => $fields['slug'],
        'template_key' => $fields['template_key'],
        'is_active' => (int) $fields['is_active'],
        'banner_enabled' => (int) ($fields['banner_enabled'] ?? ($page['banner_enabled'] ?? 1)),
        'banner_size_preset' => $fields['banner_size_preset'] ?? ($page['banner_size_preset'] ?? 'md'),
        'banner_eyebrow' => $fields['banner_eyebrow'] ?? ($page['banner_eyebrow'] ?? ''),
        'banner_title' => $fields['banner_title'] ?? ($page['banner_title'] ?? ''),
        'banner_subtitle' => $fields['banner_subtitle'] ?? ($page['banner_subtitle'] ?? ''),
    ]);
    $assignedMenus = $menuIds;
}

$list = $page['page_type'] === 'admin' ? '/admin-pages' : '/website-pages';
ob_start();
?>
<div class="page-head"><div><h1><?= $isEdit ? 'Edit page' : 'Create page' ?></h1></div>
  <a class="btn btn-ghost" href="<?= wa_e(wa_url($list)) ?>">Back</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="post" class="form-grid"<?= $isWebsite ? ' enctype="multipart/form-data"' : '' ?>>
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

    <?php if ($isWebsite): ?>
    <div class="full" style="border-top:1px solid #e5e7eb;padding-top:1rem;margin-top:0.25rem">
      <strong>Page banner</strong>
      <p class="muted" style="margin:0.35rem 0 0.75rem">When enabled, shown on the public site if this page has no hero slider. Background should be an image.</p>
    </div>
    <label><span>Show banner</span><input type="checkbox" name="banner_enabled" value="1" <?= (int)($page['banner_enabled'] ?? 1) === 1 ? 'checked' : '' ?>></label>
    <label>Banner size
      <?php $bannerSize = (string) ($page['banner_size_preset'] ?? 'md'); ?>
      <select name="banner_size_preset">
        <option value="sm" <?= $bannerSize === 'sm' ? 'selected' : '' ?>>Small</option>
        <option value="md" <?= $bannerSize === 'md' ? 'selected' : '' ?>>Medium</option>
        <option value="lg" <?= $bannerSize === 'lg' ? 'selected' : '' ?>>Large</option>
        <option value="xl" <?= $bannerSize === 'xl' ? 'selected' : '' ?>>Extra large</option>
      </select>
    </label>
    <label>Banner eyebrow<input name="banner_eyebrow" value="<?= wa_e((string)($page['banner_eyebrow'] ?? '')) ?>" placeholder="PROFESSIONAL DISPATCHING"></label>
    <label>Banner title<input name="banner_title" value="<?= wa_e((string)($page['banner_title'] ?? '')) ?>" placeholder="About Navo Dispatch LLC"></label>
    <label class="full">Banner subtitle<input name="banner_subtitle" value="<?= wa_e((string)($page['banner_subtitle'] ?? '')) ?>" placeholder="Professional Truck & NEMT Dispatching Services"></label>
    <label class="full">Banner background image<?= $isEdit ? ' (optional replace)' : '' ?>
      <input type="file" name="banner_image" accept="image/jpeg,image/png,image/webp,image/gif">
    </label>
    <?php if (!empty($page['banner_image_url'])): ?>
      <div class="full">
        <img class="thumb" style="width:220px;height:80px;object-fit:cover;border-radius:8px" src="<?= wa_e((string)$page['banner_image_url']) ?>" alt="">
        <label style="display:inline-flex;gap:0.4rem;align-items:center;margin-left:0.75rem">
          <input type="checkbox" name="clear_banner_image" value="1"> Remove image
        </label>
      </div>
    <?php endif; ?>
    <?php endif; ?>

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
