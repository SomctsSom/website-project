<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('contact_social.view');

$input = request_input();
[$page, $perPage, $offset] = paginate_params($input);
$q = trim((string) ($input['q'] ?? ''));
$status = (string) ($input['status'] ?? '');
$pageId = isset($input['page_id']) ? (int) $input['page_id'] : 0;

$where = ['sl.deleted_at IS NULL'];
$params = [];
if ($q !== '') {
    $where[] = '(sl.label LIKE :q OR sl.link_url LIKE :q OR sl.icon_class LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($status === 'active') {
    $where[] = 'sl.is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'sl.is_active = 0';
}
$join = '';
if ($pageId > 0) {
    $join = 'JOIN social_link_pages slp ON slp.social_link_id = sl.id AND slp.deleted_at IS NULL AND slp.page_id = :page_id';
    $params[':page_id'] = $pageId;
}
$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$pdo = db();
$c = $pdo->prepare("SELECT COUNT(DISTINCT sl.id) FROM social_links sl {$join} {$sqlWhere}");
$c->execute($params);
$total = (int) $c->fetchColumn();
$stmt = $pdo->prepare(
    "SELECT DISTINCT sl.* FROM social_links sl {$join} {$sqlWhere}
     ORDER BY sl.sort_order ASC, sl.id DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$items = $stmt->fetchAll() ?: [];
foreach ($items as &$row) {
    $row = social_link_attach_display($row);
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
