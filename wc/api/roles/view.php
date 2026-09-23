<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('roles.view');

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid role id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM roles WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Role not found', 404);
}

$p = $pdo->prepare(
    'SELECT p.id, p.module, p.action, p.code, p.description
     FROM role_permissions rp
     JOIN permissions p ON p.id = rp.permission_id AND p.deleted_at IS NULL
     WHERE rp.role_id = :id AND rp.deleted_at IS NULL
     ORDER BY p.module, p.action'
);
$p->execute([':id' => $id]);
$row['permissions'] = $p->fetchAll() ?: [];
$row['id'] = (int) $row['id'];
$row['is_system'] = (int) $row['is_system'];
$row['created_at_display'] = format_display_time($row['created_at']);
json_ok($row);
