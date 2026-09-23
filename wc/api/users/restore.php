<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('users.restore');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid user id', 422);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id AND deleted_at IS NOT NULL');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Deleted user not found', 404);
}

$dup = $pdo->prepare('SELECT id FROM users WHERE email_active = :email LIMIT 1');
$dup->execute([':email' => $row['email']]);
if ($dup->fetchColumn()) {
    json_error('Cannot restore: email is already used by an active user', 422);
}

if (!restore_row($pdo, 'users', $id, (int) $actor['id'])) {
    json_error('Restore failed', 500);
}
audit_log((int) $actor['id'], 'restore', 'users', $id);
json_ok(['id' => $id], 'User restored');
