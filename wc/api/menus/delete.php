<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('menus.delete');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid menu id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM menus WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
if (!$stmt->fetchColumn()) {
    json_error('Menu not found', 404);
}

// Soft-delete children associations and child menus recursively (soft)
$children = $pdo->prepare('SELECT id FROM menus WHERE parent_menu_id = :id AND deleted_at IS NULL');
$children->execute([':id' => $id]);
$childIds = $children->fetchAll(PDO::FETCH_COLUMN) ?: [];
foreach ($childIds as $cid) {
    soft_delete_row($pdo, 'menus', (int) $cid, (int) $actor['id']);
}

$mps = $pdo->prepare('SELECT id FROM menu_pages WHERE menu_id = :id AND deleted_at IS NULL');
$mps->execute([':id' => $id]);
foreach ($mps->fetchAll(PDO::FETCH_COLUMN) ?: [] as $mpId) {
    soft_delete_row($pdo, 'menu_pages', (int) $mpId, (int) $actor['id']);
}

if (!soft_delete_row($pdo, 'menus', $id, (int) $actor['id'])) {
    json_error('Soft delete failed', 500);
}
audit_log((int) $actor['id'], 'soft_delete', 'menus', $id);
json_ok(['id' => $id], 'Menu soft-deleted');
