<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('permissions.create');
auth_verify_csrf($actor);

$input = request_input();
$module = slugify((string) ($input['module'] ?? ''));
$module = str_replace('-', '_', $module);
$action = slugify((string) ($input['action'] ?? ''));
$action = str_replace('-', '_', $action);
$description = trim((string) ($input['description'] ?? ''));
$code = $module . '.' . $action;

if ($module === '' || $action === '') {
    json_error('Module and action are required', 422);
}

$pdo = db();
$dup = $pdo->prepare('SELECT id FROM permissions WHERE code_active = :code LIMIT 1');
$dup->execute([':code' => $code]);
if ($dup->fetchColumn()) {
    json_error('Permission code already exists', 422);
}

$stmt = $pdo->prepare(
    'INSERT INTO permissions (module, action, code, description, is_system, created_at, created_by)
     VALUES (:module, :action, :code, :description, 0, :created, :by)'
);
$stmt->execute([
    ':module' => $module,
    ':action' => $action,
    ':code' => $code,
    ':description' => $description !== '' ? $description : null,
    ':created' => now_utc(),
    ':by' => $actor['id'],
]);
$id = (int) $pdo->lastInsertId();
audit_log((int) $actor['id'], 'create', 'permissions', $id, ['code' => $code]);
json_ok(['id' => $id, 'code' => $code], 'Permission created', 201);
