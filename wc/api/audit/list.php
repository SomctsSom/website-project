<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('audit.view');

$input = request_input();
[$page, $perPage, $offset] = paginate_params($input);
$q = trim((string) ($input['q'] ?? ''));
$entity = trim((string) ($input['entity_type'] ?? ''));
$action = trim((string) ($input['action'] ?? ''));
$userId = isset($input['user_id']) && $input['user_id'] !== '' ? (int) $input['user_id'] : null;

$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(a.action LIKE :q OR a.entity_type LIKE :q OR CAST(a.details_json AS CHAR) LIKE :q OR u.email LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($entity !== '') {
    $where[] = 'a.entity_type = :entity';
    $params[':entity'] = $entity;
}
if ($action !== '') {
    $where[] = 'a.action = :action';
    $params[':action'] = $action;
}
if ($userId !== null) {
    $where[] = 'a.user_id = :uid';
    $params[':uid'] = $userId;
}
$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$pdo = db();
$c = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id {$sqlWhere}");
$c->execute($params);
$total = (int) $c->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT a.*, u.name AS user_name, u.email AS user_email
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     {$sqlWhere}
     ORDER BY a.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];
foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['created_at_display'] = format_display_time($row['created_at']);
    if (is_string($row['details_json'] ?? null)) {
        $row['details'] = json_decode($row['details_json'], true);
    }
}
unset($row);

json_ok([
    'items' => $rows,
    'pagination' => [
        'page' => $page, 'per_page' => $perPage, 'total' => $total,
        'total_pages' => (int) ceil($total / max(1, $perPage)),
    ],
]);
