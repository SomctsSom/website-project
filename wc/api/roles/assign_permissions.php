<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('roles.edit');
auth_verify_csrf($actor);

$input = request_input();
$roleId = (int) ($input['id'] ?? $input['role_id'] ?? 0);
$permissionIds = $input['permission_ids'] ?? [];
if (!is_array($permissionIds)) {
    json_error('permission_ids must be an array', 422);
}
$permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));

if ($roleId < 1) {
    json_error('Invalid role id', 422);
}

$pdo = db();
$role = $pdo->prepare('SELECT * FROM roles WHERE id = :id AND deleted_at IS NULL');
$role->execute([':id' => $roleId]);
$roleRow = $role->fetch();
if (!$roleRow) {
    json_error('Role not found', 404);
}

// Protect last Super Admin essential access: cannot strip all permissions from super_admin role if it's the only path
if ($roleRow['code'] === 'super_admin') {
    $essential = ['users.view', 'roles.view', 'permissions.view'];
    if ($permissionIds) {
        $in = implode(',', array_fill(0, count($permissionIds), '?'));
        $chk = $pdo->prepare("SELECT code FROM permissions WHERE id IN ({$in}) AND deleted_at IS NULL");
        $chk->execute($permissionIds);
        $codes = $chk->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($essential as $need) {
            if (!in_array($need, $codes, true)) {
                json_error("Super Admin role must retain essential permission: {$need}", 422);
            }
        }
    } else {
        json_error('Super Admin role must retain essential permissions', 422);
    }
}

$pdo->beginTransaction();
try {
    // Soft-delete assignments no longer selected
    $current = $pdo->prepare('SELECT id, permission_id FROM role_permissions WHERE role_id = :rid AND deleted_at IS NULL');
    $current->execute([':rid' => $roleId]);
    $existing = $current->fetchAll() ?: [];
    $existingMap = [];
    foreach ($existing as $e) {
        $existingMap[(int) $e['permission_id']] = (int) $e['id'];
    }

    foreach ($existingMap as $pid => $rpId) {
        if (!in_array($pid, $permissionIds, true)) {
            soft_delete_row($pdo, 'role_permissions', $rpId, (int) $actor['id']);
        }
    }

    foreach ($permissionIds as $pid) {
        if ($pid < 1) {
            continue;
        }
        $p = $pdo->prepare('SELECT id FROM permissions WHERE id = :id AND deleted_at IS NULL');
        $p->execute([':id' => $pid]);
        if (!$p->fetchColumn()) {
            throw new RuntimeException("Permission {$pid} not found");
        }
        if (isset($existingMap[$pid])) {
            continue;
        }
        // Restore soft-deleted assignment if present
        $old = $pdo->prepare('SELECT id FROM role_permissions WHERE role_id = :r AND permission_id = :p AND deleted_at IS NOT NULL ORDER BY id DESC LIMIT 1');
        $old->execute([':r' => $roleId, ':p' => $pid]);
        $oldId = $old->fetchColumn();
        if ($oldId) {
            restore_row($pdo, 'role_permissions', (int) $oldId, (int) $actor['id']);
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO role_permissions (role_id, permission_id, created_at, created_by) VALUES (:r, :p, :c, :by)'
            );
            $ins->execute([':r' => $roleId, ':p' => $pid, ':c' => now_utc(), ':by' => $actor['id']]);
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error($e->getMessage(), 422);
}

audit_log((int) $actor['id'], 'assign_permissions', 'roles', $roleId, ['permission_ids' => $permissionIds]);
json_ok(['id' => $roleId], 'Permissions assigned');
