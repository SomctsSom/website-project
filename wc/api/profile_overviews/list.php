<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('profile_overviews.view');

$input = request_input();
[$page, $perPage, $offset] = paginate_params($input);
$q = trim((string) ($input['q'] ?? ''));
$pageId = isset($input['page_id']) && $input['page_id'] !== '' ? (int) $input['page_id'] : null;
$featured = (string) ($input['featured'] ?? '');
$status = (string) ($input['status'] ?? '');
$trashed = ((string) ($input['trashed'] ?? '')) === '1';

$where = [$trashed ? 'p.deleted_at IS NOT NULL' : 'p.deleted_at IS NULL'];
$params = [];
$join = '';

if ($q !== '') {
    $where[] = '(p.title LIKE :q OR p.body LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($status === 'active') {
    $where[] = 'p.is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'p.is_active = 0';
}
if ($pageId !== null || $featured !== '') {
    $join = 'JOIN profile_overview_pages pp ON pp.profile_overview_id = p.id AND pp.deleted_at IS NULL';
    if ($pageId !== null) {
        $where[] = 'pp.page_id = :page_id';
        $params[':page_id'] = $pageId;
    }
    if ($featured === '1') {
        $where[] = 'pp.is_featured = 1 AND pp.is_active = 1';
    } elseif ($featured === '0') {
        $where[] = 'pp.is_featured = 0';
    }
}

$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$pdo = db();
$c = $pdo->prepare("SELECT COUNT(DISTINCT p.id) FROM profile_overviews p {$join} {$sqlWhere}");
$c->execute($params);
$total = (int) $c->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT DISTINCT p.* FROM profile_overviews p {$join} {$sqlWhere}
     ORDER BY p.sort_order ASC, p.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];

foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['is_active'] = (int) $row['is_active'];
    $row = profile_overview_attach_media($row);
    $ps = $pdo->prepare(
        'SELECT pp.page_id, pp.is_featured, pp.is_active, pp.sort_order, pg.title, pg.slug
         FROM profile_overview_pages pp
         JOIN pages pg ON pg.id = pp.page_id AND pg.deleted_at IS NULL
         WHERE pp.profile_overview_id = :id AND pp.deleted_at IS NULL
         ORDER BY pp.sort_order, pg.title'
    );
    $ps->execute([':id' => $row['id']]);
    $pages = $ps->fetchAll() ?: [];
    $row['pages'] = $pages;
    $row['page_count'] = count($pages);
    $row['created_at_display'] = format_display_time($row['created_at']);
}
unset($row);

json_ok([
    'items' => $rows,
    'pagination' => [
        'page' => $page, 'per_page' => $perPage, 'total' => $total,
        'total_pages' => (int) ceil($total / max(1, $perPage)),
    ],
]);
