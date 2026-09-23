<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('users.delete');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid user id', 422);
}
if ($id === (int) $actor['id']) {
    json_error('You cannot delete your own account', 422);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
if (!$stmt->fetchColumn()) {
    json_error('User not found', 404);
}

auth_protect_last_super_admin($id, 'delete');

if (!soft_delete_row($pdo, 'users', $id, (int) $actor['id'])) {
    json_error('Soft delete failed', 500);
}

// Revoke sessions
$pdo->prepare('UPDATE api_sessions SET revoked_at = :now WHERE user_id = :id AND revoked_at IS NULL')
    ->execute([':now' => now_utc(), ':id' => $id]);

audit_log((int) $actor['id'], 'soft_delete', 'users', $id);
json_ok(['id' => $id], 'User soft-deleted');
