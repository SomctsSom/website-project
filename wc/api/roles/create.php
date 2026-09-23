<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('roles.create');
auth_verify_csrf($actor);

$input = request_input();
$name = trim((string) ($input['name'] ?? ''));
$code = slugify((string) ($input['code'] ?? $name));
$code = str_replace('-', '_', $code);
$description = trim((string) ($input['description'] ?? ''));

if ($name === '' || $code === '') {
    json_error('Name and code are required', 422);
}

$pdo = db();
$dup = $pdo->prepare('SELECT id FROM roles WHERE code_active = :code LIMIT 1');
$dup->execute([':code' => $code]);
if ($dup->fetchColumn()) {
    json_error('Role code already exists', 422);
}

$stmt = $pdo->prepare(
    'INSERT INTO roles (name, code, description, is_system, created_at, created_by)
     VALUES (:name, :code, :description, 0, :created, :by)'
);
$stmt->execute([
    ':name' => $name,
    ':code' => $code,
    ':description' => $description !== '' ? $description : null,
    ':created' => now_utc(),
    ':by' => $actor['id'],
]);
$id = (int) $pdo->lastInsertId();
audit_log((int) $actor['id'], 'create', 'roles', $id, ['code' => $code]);
json_ok(['id' => $id], 'Role created', 201);
