<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('menus.create');
auth_verify_csrf($actor);

$input = request_input();
$menuType = (string) ($input['menu_type'] ?? '');
$title = trim((string) ($input['title'] ?? ''));
$icon = trim((string) ($input['icon'] ?? ''));
$url = trim((string) ($input['url'] ?? ''));
$sortOrder = (int) ($input['sort_order'] ?? 0);
$isActive = isset($input['is_active']) ? to_bool_int($input['is_active'], 1) : 1;
$parentId = isset($input['parent_menu_id']) && $input['parent_menu_id'] !== '' && $input['parent_menu_id'] !== null
    ? (int) $input['parent_menu_id'] : null;

if (!in_array($menuType, ['website', 'admin'], true)) {
    json_error('menu_type must be website or admin', 422);
}
if ($title === '') {
    json_error('Title is required', 422);
}
if ($url !== '' && !is_safe_url($url)) {
    json_error('Invalid URL', 422);
}

$parentErr = menu_validate_parent($menuType, $parentId);
if ($parentErr !== null) {
    json_error($parentErr, 422);
}

$pdo = db();
$stmt = $pdo->prepare(
    'INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at, created_by)
     VALUES (:type, :parent, :title, :icon, :url, :sort, :active, :created, :by)'
);
$stmt->execute([
    ':type' => $menuType,
    ':parent' => $parentId,
    ':title' => $title,
    ':icon' => $icon !== '' ? $icon : null,
    ':url' => $url !== '' ? $url : null,
    ':sort' => $sortOrder,
    ':active' => $isActive,
    ':created' => now_utc(),
    ':by' => $actor['id'],
]);
$id = (int) $pdo->lastInsertId();
audit_log((int) $actor['id'], 'create', 'menus', $id, ['menu_type' => $menuType, 'title' => $title]);
json_ok(['id' => $id], 'Menu created', 201);
