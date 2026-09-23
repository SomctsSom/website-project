<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('permissions.view');

$input = request_input();
[$page, $perPage, $offset] = paginate_params($input, 50, 200);
$q = trim((string) ($input['q'] ?? ''));
$module = trim((string) ($input['module'] ?? ''));
$trashed = ((string) ($input['trashed'] ?? '')) === '1';

$where = [$trashed ? 'deleted_at IS NOT NULL' : 'deleted_at IS NULL'];
$params = [];
if ($q !== '') {
    $where[] = '(code LIKE :q OR description LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($module !== '') {
    $where[] = 'module = :module';
    $params[':module'] = $module;
}
$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$pdo = db();
$c = $pdo->prepare("SELECT COUNT(*) FROM permissions {$sqlWhere}");
$c->execute($params);
$total = (int) $c->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM permissions {$sqlWhere} ORDER BY module, action LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];
foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['is_system'] = (int) $row['is_system'];
}
unset($row);

json_ok(['items' => $rows, 'pagination' => [
    'page' => $page, 'per_page' => $perPage, 'total' => $total,
    'total_pages' => (int) ceil($total / max(1, $perPage)),
]]);
