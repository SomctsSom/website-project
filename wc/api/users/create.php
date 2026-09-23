<?php
declare(strict_types=1);

require_method('POST');
$user = auth_require_permission('users.create');
auth_verify_csrf($user);

$input = request_input();
$name = trim((string) ($input['name'] ?? ''));
$email = strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');
$roleId = (int) ($input['role_id'] ?? 0);
$isActive = isset($input['is_active']) ? to_bool_int($input['is_active'], 1) : 1;

$errors = [];
if ($name === '') {
    $errors['name'] = 'Name is required';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Valid email is required';
}
if (strlen($password) < 10) {
    $errors['password'] = 'Password must be at least 10 characters';
}
if ($roleId < 1) {
    $errors['role_id'] = 'Role is required';
}
if ($errors) {
    json_error('Validation failed', 422, $errors);
}

$pdo = db();
$role = $pdo->prepare('SELECT id, code FROM roles WHERE id = :id AND deleted_at IS NULL');
$role->execute([':id' => $roleId]);
$roleRow = $role->fetch();
if (!$roleRow) {
    json_error('Role not found', 422);
}

$exists = $pdo->prepare('SELECT id FROM users WHERE email_active = :email LIMIT 1');
$exists->execute([':email' => $email]);
if ($exists->fetchColumn()) {
    json_error('Email already in use', 422);
}

$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, role_id, is_active, created_at, created_by)
     VALUES (:name, :email, :hash, :role_id, :active, :created, :by)'
);
$stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':hash' => password_hash($password, PASSWORD_DEFAULT),
    ':role_id' => $roleId,
    ':active' => $isActive,
    ':created' => now_utc(),
    ':by' => $user['id'],
]);
$id = (int) $pdo->lastInsertId();
audit_log((int) $user['id'], 'create', 'users', $id, ['email' => $email, 'role_id' => $roleId]);
json_ok(['id' => $id], 'User created', 201);
