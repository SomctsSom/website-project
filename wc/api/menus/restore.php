<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('menus.restore');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid menu id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM menus WHERE id = :id AND deleted_at IS NOT NULL');
$stmt->execute([':id' => $id]);
if (!$stmt->fetch()) {
    json_error('Deleted menu not found', 404);
}
if (!restore_row($pdo, 'menus', $id, (int) $actor['id'])) {
    json_error('Restore failed', 500);
}
audit_log((int) $actor['id'], 'restore', 'menus', $id);
json_ok(['id' => $id], 'Menu restored');
