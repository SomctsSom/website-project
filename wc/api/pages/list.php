<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('pages.view');

$input = request_input();
[$page, $perPage, $offset] = paginate_params($input);
$pageType = (string) ($input['page_type'] ?? '');
$q = trim((string) ($input['q'] ?? ''));
$trashed = ((string) ($input['trashed'] ?? '')) === '1';

$where = [$trashed ? 'p.deleted_at IS NOT NULL' : 'p.deleted_at IS NULL'];
$params = [];
if (in_array($pageType, ['website', 'admin'], true)) {
    $where[] = 'p.page_type = :pt';
    $params[':pt'] = $pageType;
}
if ($q !== '') {
    $where[] = '(p.title LIKE :q OR p.slug LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$pdo = db();
$c = $pdo->prepare("SELECT COUNT(*) FROM pages p {$sqlWhere}");
$c->execute($params);
$total = (int) $c->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT p.* FROM pages p {$sqlWhere} ORDER BY p.page_type, p.title LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];
foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['is_active'] = (int) $row['is_active'];
    $row['created_at_display'] = format_display_time($row['created_at']);
}
unset($row);

json_ok([
    'items' => $rows,
    'approved_admin_templates' => app_config('approved_admin_templates'),
    'approved_website_templates' => app_config('approved_website_templates'),
    'pagination' => [
        'page' => $page, 'per_page' => $perPage, 'total' => $total,
        'total_pages' => (int) ceil($total / max(1, $perPage)),
    ],
]);
