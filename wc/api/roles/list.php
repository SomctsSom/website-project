<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('roles.view');

$input = request_input();
[$page, $perPage, $offset] = paginate_params($input);
$q = trim((string) ($input['q'] ?? ''));
$trashed = ((string) ($input['trashed'] ?? '')) === '1';

$where = [$trashed ? 'r.deleted_at IS NOT NULL' : 'r.deleted_at IS NULL'];
$params = [];
if ($q !== '') {
    $where[] = '(r.name LIKE :q OR r.code LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$pdo = db();

$total = (int) (function () use ($pdo, $sqlWhere, $params) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM roles r {$sqlWhere}");
    $s->execute($params);
    return $s->fetchColumn();
})();

$stmt = $pdo->prepare(
    "SELECT r.*,
            (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id AND rp.deleted_at IS NULL) AS permission_count,
            (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id AND u.deleted_at IS NULL) AS user_count
     FROM roles r
     {$sqlWhere}
     ORDER BY r.is_system DESC, r.name ASC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];
foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['is_system'] = (int) $row['is_system'];
    $row['created_at_display'] = format_display_time($row['created_at']);
}
unset($row);

json_ok(['items' => $rows, 'pagination' => [
    'page' => $page, 'per_page' => $perPage, 'total' => $total,
    'total_pages' => (int) ceil($total / max(1, $perPage)),
]]);
