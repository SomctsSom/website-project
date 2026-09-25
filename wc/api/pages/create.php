<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('pages.create');
auth_verify_csrf($actor);

$input = array_merge(request_input(), $_POST);
$pageType = (string) ($input['page_type'] ?? '');
$title = trim((string) ($input['title'] ?? ''));
$slug = slugify((string) ($input['slug'] ?? $title));
$templateKey = trim((string) ($input['template_key'] ?? ''));
$isActive = isset($input['is_active']) ? to_bool_int($input['is_active'], 1) : 1;
$bannerEyebrow = trim((string) ($input['banner_eyebrow'] ?? ''));
$bannerTitle = trim((string) ($input['banner_title'] ?? ''));
$bannerSubtitle = trim((string) ($input['banner_subtitle'] ?? ''));
$bannerEnabled = array_key_exists('banner_enabled', $input) ? to_bool_int($input['banner_enabled'], 1) : 1;
$bannerSizePreset = normalize_hero_size_preset((string) ($input['banner_size_preset'] ?? 'md'));
$pageBgColor = null;
$cardBgColor = null;
if ($pageType === 'website') {
    $rawBg = trim((string) ($input['bg_color'] ?? ''));
    if ($rawBg !== '' && empty($input['clear_bg_color'])) {
        $pageBgColor = sanitize_navbar_hex($rawBg, '#f2ebe0');
    }
    $rawCard = trim((string) ($input['card_bg_color'] ?? ''));
    if ($rawCard !== '' && empty($input['clear_card_bg_color'])) {
        $cardBgColor = sanitize_navbar_hex($rawCard, '#ffffff');
    }
}

if (!in_array($pageType, ['website', 'admin'], true)) {
    json_error('page_type must be website or admin', 422);
}
if ($title === '' || $slug === '') {
    json_error('Title and slug are required', 422);
}

$reserved = app_config('reserved_slugs', []);
if (in_array($slug, $reserved, true)) {
    json_error('Slug is reserved', 422);
}

$approved = $pageType === 'admin'
    ? app_config('approved_admin_templates', [])
    : app_config('approved_website_templates', []);
if ($pageType === 'website' && $templateKey === '') {
    $templateKey = 'default';
}
if (!in_array($templateKey, $approved, true)) {
    json_error('template_key is not in the approved whitelist', 422);
}

$bannerImagePath = null;
if ($pageType === 'website' && isset($_FILES['banner_image'])
    && (int) ($_FILES['banner_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $upload = secure_upload_image($_FILES['banner_image'], 'banners');
    if (!$upload['ok']) {
        json_error($upload['error'], 422);
    }
    $bannerImagePath = $upload['relative_path'];
}

$pdo = db();
$dup = $pdo->prepare('SELECT id FROM pages WHERE type_slug_active = :k LIMIT 1');
$dup->execute([':k' => $pageType . ':' . $slug]);
if ($dup->fetchColumn()) {
    json_error('Slug already exists for this page type', 422);
}

$stmt = $pdo->prepare(
    'INSERT INTO pages (
        page_type, title, slug, template_key,
        banner_eyebrow, banner_title, banner_subtitle, banner_image_path, banner_enabled, banner_size_preset,
        bg_color, card_bg_color, is_active, created_at, created_by
     ) VALUES (
        :pt, :title, :slug, :tk,
        :be, :bt, :bs, :bi, :ben, :bsp,
        :bg, :cbg, :active, :created, :by
     )'
);
$stmt->execute([
    ':pt' => $pageType,
    ':title' => $title,
    ':slug' => $slug,
    ':tk' => $templateKey,
    ':be' => $bannerEyebrow !== '' ? $bannerEyebrow : null,
    ':bt' => $bannerTitle !== '' ? $bannerTitle : null,
    ':bs' => $bannerSubtitle !== '' ? $bannerSubtitle : null,
    ':bi' => $bannerImagePath,
    ':ben' => $pageType === 'website' ? $bannerEnabled : 0,
    ':bsp' => $pageType === 'website' ? $bannerSizePreset : 'md',
    ':bg' => $pageType === 'website' ? $pageBgColor : null,
    ':cbg' => $pageType === 'website' ? $cardBgColor : null,
    ':active' => $isActive,
    ':created' => now_utc(),
    ':by' => $actor['id'],
]);
$id = (int) $pdo->lastInsertId();

$menuIds = $input['menu_ids'] ?? [];
if (is_string($menuIds)) {
    $decoded = json_decode($menuIds, true);
    $menuIds = is_array($decoded) ? $decoded : [];
}
if (is_array($menuIds)) {
    foreach ($menuIds as $menuId) {
        $menuId = (int) $menuId;
        if ($menuId < 1) {
            continue;
        }
        $m = $pdo->prepare('SELECT menu_type FROM menus WHERE id = :id AND deleted_at IS NULL');
        $m->execute([':id' => $menuId]);
        $mt = $m->fetchColumn();
        if ($mt !== $pageType) {
            continue;
        }
        $pdo->prepare(
            'INSERT INTO menu_pages (menu_id, page_id, sort_order, created_at, created_by) VALUES (:m, :p, 0, :c, :by)'
        )->execute([':m' => $menuId, ':p' => $id, ':c' => now_utc(), ':by' => $actor['id']]);
    }
}

if ($pageType === 'website') {
    sync_page_section_order($pdo, $id, page_content_section_defaults(), (int) $actor['id']);
}

audit_log((int) $actor['id'], 'create', 'pages', $id, ['slug' => $slug, 'page_type' => $pageType]);
json_ok(['id' => $id], 'Page created', 201);
