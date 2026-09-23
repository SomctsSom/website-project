<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('users.view');

$input = request_input();
[$page, $perPage, $offset] = paginate_params($input);
$q = trim((string) ($input['q'] ?? ''));
$roleId = isset($input['role_id']) && $input['role_id'] !== '' ? (int) $input['role_id'] : null;
$status = (string) ($input['status'] ?? ''); // active|inactive|all
$trashed = ((string) ($input['trashed'] ?? '')) === '1';

$where = [];
$params = [];
if ($trashed) {
    $where[] = 'u.deleted_at IS NOT NULL';
} else {
    $where[] = 'u.deleted_at IS NULL';
}
if ($q !== '') {
    $where[] = '(u.name LIKE :q OR u.email LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($roleId !== null) {
    $where[] = 'u.role_id = :role_id';
    $params[':role_id'] = $roleId;
}
if ($status === 'active') {
    $where[] = 'u.is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'u.is_active = 0';
}

$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$pdo = db();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u {$sqlWhere}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT u.id, u.name, u.email, u.role_id, u.is_active, u.last_login_at, u.created_at, u.updated_at, u.deleted_at,
            r.name AS role_name, r.code AS role_code
     FROM users u
     JOIN roles r ON r.id = u.role_id
     {$sqlWhere}
     ORDER BY u.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];

foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['role_id'] = (int) $row['role_id'];
    $row['is_active'] = (int) $row['is_active'];
    $row['last_login_at_display'] = format_display_time($row['last_login_at']);
    $row['created_at_display'] = format_display_time($row['created_at']);
}
unset($row);

json_ok([
    'items' => $rows,
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => (int) ceil($total / max(1, $perPage)),
    ],
]);
