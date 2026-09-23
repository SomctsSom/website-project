<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('roles.delete');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid role id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM roles WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Role not found', 404);
}
if ((int) $row['is_system'] === 1) {
    json_error('System roles cannot be deleted', 422);
}
$users = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role_id = :id AND deleted_at IS NULL');
$users->execute([':id' => $id]);
if ((int) $users->fetchColumn() > 0) {
    json_error('Role has assigned users; reassign them first', 422);
}
if (!soft_delete_row($pdo, 'roles', $id, (int) $actor['id'])) {
    json_error('Soft delete failed', 500);
}
audit_log((int) $actor['id'], 'soft_delete', 'roles', $id);
json_ok(['id' => $id], 'Role soft-deleted');
