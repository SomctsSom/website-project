<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('menus.edit');
auth_verify_csrf($actor);

$input = request_input();
$id = (int) ($input['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid menu id', 422);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM menus WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
$existing = $stmt->fetch();
if (!$existing) {
    json_error('Menu not found', 404);
}

$title = trim((string) ($input['title'] ?? $existing['title']));
$icon = array_key_exists('icon', $input) ? trim((string) $input['icon']) : (string) $existing['icon'];
$url = array_key_exists('url', $input) ? trim((string) $input['url']) : (string) $existing['url'];
$sortOrder = (int) ($input['sort_order'] ?? $existing['sort_order']);
$isActive = array_key_exists('is_active', $input) ? to_bool_int($input['is_active'], (int) $existing['is_active']) : (int) $existing['is_active'];
$parentId = array_key_exists('parent_menu_id', $input)
    ? (($input['parent_menu_id'] === '' || $input['parent_menu_id'] === null) ? null : (int) $input['parent_menu_id'])
    : ($existing['parent_menu_id'] === null ? null : (int) $existing['parent_menu_id']);

if ($title === '') {
    json_error('Title is required', 422);
}
if ($url !== '' && !is_safe_url($url)) {
    json_error('Invalid URL', 422);
}
if (menu_would_create_cycle($id, $parentId)) {
    json_error('Parent assignment would create a cycle', 422);
}
$parentErr = menu_validate_parent($existing['menu_type'], $parentId);
if ($parentErr !== null) {
    json_error($parentErr, 422);
}

$pdo->prepare(
    'UPDATE menus SET parent_menu_id = :parent, title = :title, icon = :icon, url = :url,
     sort_order = :sort, is_active = :active, updated_at = :u, updated_by = :by WHERE id = :id'
)->execute([
    ':parent' => $parentId,
    ':title' => $title,
    ':icon' => $icon !== '' ? $icon : null,
    ':url' => $url !== '' ? $url : null,
    ':sort' => $sortOrder,
    ':active' => $isActive,
    ':u' => now_utc(),
    ':by' => $actor['id'],
    ':id' => $id,
]);
audit_log((int) $actor['id'], 'update', 'menus', $id, ['title' => $title, 'is_active' => $isActive]);
json_ok(['id' => $id], 'Menu updated');
