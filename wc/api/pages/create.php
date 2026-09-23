<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('pages.create');
auth_verify_csrf($actor);

$input = request_input();
$pageType = (string) ($input['page_type'] ?? '');
$title = trim((string) ($input['title'] ?? ''));
$slug = slugify((string) ($input['slug'] ?? $title));
$templateKey = trim((string) ($input['template_key'] ?? ''));
$isActive = isset($input['is_active']) ? to_bool_int($input['is_active'], 1) : 1;

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
if (!in_array($templateKey, $approved, true)) {
    json_error('template_key is not in the approved whitelist', 422);
}

$pdo = db();
$dup = $pdo->prepare('SELECT id FROM pages WHERE type_slug_active = :k LIMIT 1');
$dup->execute([':k' => $pageType . ':' . $slug]);
if ($dup->fetchColumn()) {
    json_error('Slug already exists for this page type', 422);
}

$stmt = $pdo->prepare(
    'INSERT INTO pages (page_type, title, slug, template_key, is_active, created_at, created_by)
     VALUES (:pt, :title, :slug, :tk, :active, :created, :by)'
);
$stmt->execute([
    ':pt' => $pageType,
    ':title' => $title,
    ':slug' => $slug,
    ':tk' => $templateKey,
    ':active' => $isActive,
    ':created' => now_utc(),
    ':by' => $actor['id'],
]);
$id = (int) $pdo->lastInsertId();

// Optional menu assignment
$menuIds = $input['menu_ids'] ?? [];
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

audit_log((int) $actor['id'], 'create', 'pages', $id, ['slug' => $slug, 'page_type' => $pageType]);
json_ok(['id' => $id], 'Page created', 201);
