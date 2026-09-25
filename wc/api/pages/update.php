<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('pages.edit');
auth_verify_csrf($actor);

$input = array_merge(request_input(), $_POST);
$id = (int) ($input['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid page id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM pages WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
$existing = $stmt->fetch();
if (!$existing) {
    json_error('Page not found', 404);
}

$title = trim((string) ($input['title'] ?? $existing['title']));
$slug = slugify((string) ($input['slug'] ?? $existing['slug']));
$templateKey = trim((string) ($input['template_key'] ?? $existing['template_key']));
$isActive = array_key_exists('is_active', $input) ? to_bool_int($input['is_active'], (int) $existing['is_active']) : (int) $existing['is_active'];
$pageType = $existing['page_type'];
$bannerEyebrow = array_key_exists('banner_eyebrow', $input)
    ? trim((string) $input['banner_eyebrow'])
    : (string) ($existing['banner_eyebrow'] ?? '');
$bannerTitle = array_key_exists('banner_title', $input)
    ? trim((string) $input['banner_title'])
    : (string) ($existing['banner_title'] ?? '');
$bannerSubtitle = array_key_exists('banner_subtitle', $input)
    ? trim((string) $input['banner_subtitle'])
    : (string) ($existing['banner_subtitle'] ?? '');
$bannerEnabled = array_key_exists('banner_enabled', $input)
    ? to_bool_int($input['banner_enabled'], (int) ($existing['banner_enabled'] ?? 1))
    : (int) ($existing['banner_enabled'] ?? 1);
$bannerSizePreset = normalize_hero_size_preset(
    (string) ($input['banner_size_preset'] ?? ($existing['banner_size_preset'] ?? 'md'))
);
$bannerImagePath = $existing['banner_image_path'] ?? null;
$pageBgColor = null;
$cardBgColor = null;
if ($pageType === 'website') {
    if (array_key_exists('bg_color', $input) || array_key_exists('clear_bg_color', $input)) {
        if (!empty($input['clear_bg_color'])) {
            $pageBgColor = null;
        } else {
            $rawBg = trim((string) ($input['bg_color'] ?? ''));
            $pageBgColor = $rawBg !== ''
                ? sanitize_navbar_hex($rawBg, (string) ($existing['bg_color'] ?? '#f2ebe0'))
                : null;
        }
    } else {
        $pageBgColor = $existing['bg_color'] ?? null;
        if ($pageBgColor !== null && $pageBgColor !== '') {
            $pageBgColor = sanitize_navbar_hex($pageBgColor, '#f2ebe0');
        } else {
            $pageBgColor = null;
        }
    }
    if (array_key_exists('card_bg_color', $input) || array_key_exists('clear_card_bg_color', $input)) {
        if (!empty($input['clear_card_bg_color'])) {
            $cardBgColor = null;
        } else {
            $rawCard = trim((string) ($input['card_bg_color'] ?? ''));
            $cardBgColor = $rawCard !== ''
                ? sanitize_navbar_hex($rawCard, (string) ($existing['card_bg_color'] ?? '#ffffff'))
                : null;
        }
    } else {
        $cardBgColor = $existing['card_bg_color'] ?? null;
        if ($cardBgColor !== null && $cardBgColor !== '') {
            $cardBgColor = sanitize_navbar_hex($cardBgColor, '#ffffff');
        } else {
            $cardBgColor = null;
        }
    }
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

$dup = $pdo->prepare('SELECT id FROM pages WHERE type_slug_active = :k AND id <> :id LIMIT 1');
$dup->execute([':k' => $pageType . ':' . $slug, ':id' => $id]);
if ($dup->fetchColumn()) {
    json_error('Slug already exists for this page type', 422);
}

$newUpload = null;
if ($pageType === 'website' && isset($_FILES['banner_image'])
    && (int) ($_FILES['banner_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $newUpload = secure_upload_image($_FILES['banner_image'], 'banners');
    if (!$newUpload['ok']) {
        json_error($newUpload['error'], 422);
    }
    $bannerImagePath = $newUpload['relative_path'];
}
if ($pageType === 'website' && !empty($input['clear_banner_image'])) {
    $bannerImagePath = null;
}

$pdo->prepare(
    'UPDATE pages SET title = :title, slug = :slug, template_key = :tk,
     banner_eyebrow = :be, banner_title = :bt, banner_subtitle = :bs, banner_image_path = :bi,
     banner_enabled = :ben, banner_size_preset = :bsp, bg_color = :bg, card_bg_color = :cbg,
     is_active = :active, updated_at = :u, updated_by = :by WHERE id = :id'
)->execute([
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
    ':u' => now_utc(),
    ':by' => $actor['id'],
    ':id' => $id,
]);

$menuIds = $input['menu_ids'] ?? null;
if (is_string($menuIds)) {
    $decoded = json_decode($menuIds, true);
    $menuIds = is_array($decoded) ? $decoded : null;
}
if (is_array($menuIds)) {
    $pageIds = array_values(array_unique(array_map('intval', $menuIds)));
    $current = $pdo->prepare('SELECT id, menu_id FROM menu_pages WHERE page_id = :p AND deleted_at IS NULL');
    $current->execute([':p' => $id]);
    $map = [];
    foreach ($current->fetchAll() ?: [] as $row) {
        $map[(int) $row['menu_id']] = (int) $row['id'];
    }
    foreach ($map as $mid => $mpId) {
        if (!in_array($mid, $pageIds, true)) {
            soft_delete_row($pdo, 'menu_pages', $mpId, (int) $actor['id']);
        }
    }
    foreach ($pageIds as $mid) {
        if ($mid < 1 || isset($map[$mid])) {
            continue;
        }
        $m = $pdo->prepare('SELECT menu_type FROM menus WHERE id = :id AND deleted_at IS NULL');
        $m->execute([':id' => $mid]);
        $mt = $m->fetchColumn();
        if ($mt !== $pageType) {
            continue;
        }
        $pdo->prepare(
            'INSERT INTO menu_pages (menu_id, page_id, sort_order, created_at, created_by) VALUES (:m, :p, 0, :c, :by)'
        )->execute([':m' => $mid, ':p' => $id, ':c' => now_utc(), ':by' => $actor['id']]);
    }
}

if ($pageType === 'website' && array_key_exists('sections_json', $input)) {
    $sections = json_decode((string) ($input['sections_json'] ?? '[]'), true);
    if (!is_array($sections)) {
        json_error('Invalid sections_json', 422);
    }
    try {
        sync_page_section_order($pdo, $id, $sections, (int) $actor['id']);
    } catch (Throwable $e) {
        json_error($e->getMessage(), 422);
    }
}

audit_log((int) $actor['id'], 'update', 'pages', $id, [
    'slug' => $slug,
    'banner_image_replaced' => $newUpload !== null,
]);
json_ok(['id' => $id], 'Page updated');
