<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('users.edit');
auth_verify_csrf($actor);

$input = request_input();
$id = (int) ($input['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid user id', 422);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
$existing = $stmt->fetch();
if (!$existing) {
    json_error('User not found', 404);
}

$name = trim((string) ($input['name'] ?? $existing['name']));
$email = strtolower(trim((string) ($input['email'] ?? $existing['email'])));
$roleId = (int) ($input['role_id'] ?? $existing['role_id']);
$isActive = array_key_exists('is_active', $input) ? to_bool_int($input['is_active'], (int) $existing['is_active']) : (int) $existing['is_active'];
$password = (string) ($input['password'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('Name and valid email are required', 422);
}

$role = $pdo->prepare('SELECT id, code FROM roles WHERE id = :id AND deleted_at IS NULL');
$role->execute([':id' => $roleId]);
$roleRow = $role->fetch();
if (!$roleRow) {
    json_error('Role not found', 422);
}

// Protect last Super Admin from demotion/deactivation
$wasSuper = auth_is_super_admin_user($id);
if ($wasSuper) {
    if ($roleRow['code'] !== 'super_admin') {
        auth_protect_last_super_admin($id, 'demote');
    }
    if ($isActive !== 1) {
        auth_protect_last_super_admin($id, 'deactivate');
    }
}

$dup = $pdo->prepare('SELECT id FROM users WHERE email_active = :email AND id <> :id LIMIT 1');
$dup->execute([':email' => $email, ':id' => $id]);
if ($dup->fetchColumn()) {
    json_error('Email already in use', 422);
}

$sql = 'UPDATE users SET name = :name, email = :email, role_id = :role_id, is_active = :active, updated_at = :u, updated_by = :by';
$params = [
    ':name' => $name,
    ':email' => $email,
    ':role_id' => $roleId,
    ':active' => $isActive,
    ':u' => now_utc(),
    ':by' => $actor['id'],
    ':id' => $id,
];
if ($password !== '') {
    if (strlen($password) < 10) {
        json_error('Password must be at least 10 characters', 422);
    }
    $sql .= ', password_hash = :hash';
    $params[':hash'] = password_hash($password, PASSWORD_DEFAULT);
}
$sql .= ' WHERE id = :id';
$pdo->prepare($sql)->execute($params);

audit_log((int) $actor['id'], 'update', 'users', $id, [
    'email' => $email,
    'role_id' => $roleId,
    'is_active' => $isActive,
    'password_changed' => $password !== '',
]);
json_ok(['id' => $id], 'User updated');
