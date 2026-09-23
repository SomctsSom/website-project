<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('permissions.restore');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid permission id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM permissions WHERE id = :id AND deleted_at IS NOT NULL');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Deleted permission not found', 404);
}
$dup = $pdo->prepare('SELECT id FROM permissions WHERE code_active = :code LIMIT 1');
$dup->execute([':code' => $row['code']]);
if ($dup->fetchColumn()) {
    json_error('Cannot restore: permission code already in use', 422);
}
if (!restore_row($pdo, 'permissions', $id, (int) $actor['id'])) {
    json_error('Restore failed', 500);
}
audit_log((int) $actor['id'], 'restore', 'permissions', $id);
json_ok(['id' => $id], 'Permission restored');
