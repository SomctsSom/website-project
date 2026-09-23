<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('pages.edit');
auth_verify_csrf($actor);

$input = request_input();
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

$dup = $pdo->prepare('SELECT id FROM pages WHERE type_slug_active = :k AND id <> :id LIMIT 1');
$dup->execute([':k' => $pageType . ':' . $slug, ':id' => $id]);
if ($dup->fetchColumn()) {
    json_error('Slug already exists for this page type', 422);
}

$pdo->prepare(
    'UPDATE pages SET title = :title, slug = :slug, template_key = :tk, is_active = :active, updated_at = :u, updated_by = :by WHERE id = :id'
)->execute([
    ':title' => $title,
    ':slug' => $slug,
    ':tk' => $templateKey,
    ':active' => $isActive,
    ':u' => now_utc(),
    ':by' => $actor['id'],
    ':id' => $id,
]);

if (isset($input['menu_ids']) && is_array($input['menu_ids'])) {
    $pageIds = array_values(array_unique(array_map('intval', $input['menu_ids'])));
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

audit_log((int) $actor['id'], 'update', 'pages', $id, ['slug' => $slug]);
json_ok(['id' => $id], 'Page updated');
