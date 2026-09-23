<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('permissions.delete');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid permission id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM permissions WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Permission not found', 404);
}
if ((int) $row['is_system'] === 1) {
    json_error('System permissions cannot be deleted', 422);
}
if (!soft_delete_row($pdo, 'permissions', $id, (int) $actor['id'])) {
    json_error('Soft delete failed', 500);
}
audit_log((int) $actor['id'], 'soft_delete', 'permissions', $id);
json_ok(['id' => $id], 'Permission soft-deleted');
