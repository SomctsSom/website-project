<?php
declare(strict_types=1);

require_method('POST');
$user = auth_require_user();
auth_verify_csrf($user);

$input = request_input();
$current = (string) ($input['current_password'] ?? '');
$new = (string) ($input['new_password'] ?? '');
$confirm = (string) ($input['confirm_password'] ?? $input['new_password_confirmation'] ?? '');

if ($current === '' || $new === '') {
    json_error('Current and new password are required', 422);
}
if (strlen($new) < 10) {
    json_error('New password must be at least 10 characters', 422);
}
if ($new !== $confirm) {
    json_error('Password confirmation does not match', 422);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $user['id']]);
$hash = $stmt->fetchColumn();
if (!$hash || !password_verify($current, (string) $hash)) {
    json_error('Current password is incorrect', 422);
}

$newHash = password_hash($new, PASSWORD_DEFAULT);
$upd = $pdo->prepare('UPDATE users SET password_hash = :h, updated_at = :u, updated_by = :by WHERE id = :id');
$upd->execute([':h' => $newHash, ':u' => now_utc(), ':by' => $user['id'], ':id' => $user['id']]);
audit_log((int) $user['id'], 'change_password', 'users', (int) $user['id']);
json_ok(null, 'Password updated');
