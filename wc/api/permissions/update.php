<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('permissions.edit');
auth_verify_csrf($actor);

$input = request_input();
$id = (int) ($input['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid permission id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM permissions WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
$existing = $stmt->fetch();
if (!$existing) {
    json_error('Permission not found', 404);
}

$description = trim((string) ($input['description'] ?? (string) $existing['description']));
$pdo->prepare('UPDATE permissions SET description = :d, updated_at = :u, updated_by = :by WHERE id = :id')
    ->execute([':d' => $description !== '' ? $description : null, ':u' => now_utc(), ':by' => $actor['id'], ':id' => $id]);
audit_log((int) $actor['id'], 'update', 'permissions', $id);
json_ok(['id' => $id], 'Permission updated');
