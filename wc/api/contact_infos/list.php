<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('contact_social.view');

$input = request_input();
[$page, $perPage, $offset] = paginate_params($input);
$q = trim((string) ($input['q'] ?? ''));
$status = (string) ($input['status'] ?? '');
$pageId = isset($input['page_id']) ? (int) $input['page_id'] : 0;

$where = ['c.deleted_at IS NULL'];
$params = [];
if ($q !== '') {
    $where[] = '(c.company_summary LIKE :q OR c.company_name LIKE :q OR c.tagline LIKE :q OR c.email LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($status === 'active') {
    $where[] = 'c.is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'c.is_active = 0';
}
$join = '';
if ($pageId > 0) {
    $join = 'JOIN contact_info_pages cip ON cip.contact_info_id = c.id AND cip.deleted_at IS NULL AND cip.page_id = :page_id';
    $params[':page_id'] = $pageId;
}
$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$pdo = db();
$cnt = $pdo->prepare("SELECT COUNT(DISTINCT c.id) FROM contact_infos c {$join} {$sqlWhere}");
$cnt->execute($params);
$total = (int) $cnt->fetchColumn();
$stmt = $pdo->prepare(
    "SELECT DISTINCT c.* FROM contact_infos c {$join} {$sqlWhere}
     ORDER BY c.sort_order ASC, c.id DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$items = $stmt->fetchAll() ?: [];
foreach ($items as &$row) {
    $row['id'] = (int) $row['id'];
    $row['is_active'] = (int) $row['is_active'];
    $row['sort_order'] = (int) $row['sort_order'];
    $children = contact_info_load_children($pdo, (int) $row['id']);
    $row['addresses'] = $children['addresses'];
    $row['phones'] = $children['phones'];
    $row['address_count'] = count($children['addresses']);
    $row['phone_count'] = count($children['phones']);
}
unset($row);

json_ok([
    'items' => $items,
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => (int) ceil($total / max(1, $perPage)),
    ],
]);
