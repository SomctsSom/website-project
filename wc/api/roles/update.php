<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('roles.edit');
auth_verify_csrf($actor);

$input = request_input();
$id = (int) ($input['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid role id', 422);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM roles WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
$existing = $stmt->fetch();
if (!$existing) {
    json_error('Role not found', 404);
}

$name = trim((string) ($input['name'] ?? $existing['name']));
$description = trim((string) ($input['description'] ?? (string) $existing['description']));
$code = $existing['code'];
if ((int) $existing['is_system'] !== 1 && isset($input['code'])) {
    $code = str_replace('-', '_', slugify((string) $input['code']));
}

if ($name === '' || $code === '') {
    json_error('Name and code are required', 422);
}

$dup = $pdo->prepare('SELECT id FROM roles WHERE code_active = :code AND id <> :id LIMIT 1');
$dup->execute([':code' => $code, ':id' => $id]);
if ($dup->fetchColumn()) {
    json_error('Role code already exists', 422);
}

$pdo->prepare(
    'UPDATE roles SET name = :name, code = :code, description = :description, updated_at = :u, updated_by = :by WHERE id = :id'
)->execute([
    ':name' => $name,
    ':code' => $code,
    ':description' => $description !== '' ? $description : null,
    ':u' => now_utc(),
    ':by' => $actor['id'],
    ':id' => $id,
]);
audit_log((int) $actor['id'], 'update', 'roles', $id, ['code' => $code]);
json_ok(['id' => $id], 'Role updated');
