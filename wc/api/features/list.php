<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('features.view');

$input = request_input();
[$page, $perPage, $offset] = paginate_params($input);
$q = trim((string) ($input['q'] ?? ''));
$pageId = isset($input['page_id']) && $input['page_id'] !== '' ? (int) $input['page_id'] : null;
$featured = (string) ($input['featured'] ?? '');
$status = (string) ($input['status'] ?? '');
$trashed = ((string) ($input['trashed'] ?? '')) === '1';

$where = [$trashed ? 'f.deleted_at IS NOT NULL' : 'f.deleted_at IS NULL'];
$params = [];
$join = '';

if ($q !== '') {
    $where[] = '(f.title LIKE :q OR f.description LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($status === 'active') {
    $where[] = 'f.is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'f.is_active = 0';
}
if ($pageId !== null || $featured !== '') {
    $join = 'JOIN feature_pages fp ON fp.feature_id = f.id AND fp.deleted_at IS NULL';
    if ($pageId !== null) {
        $where[] = 'fp.page_id = :page_id';
        $params[':page_id'] = $pageId;
    }
    if ($featured === '1') {
        $where[] = 'fp.is_featured = 1 AND fp.is_active = 1';
    } elseif ($featured === '0') {
        $where[] = 'fp.is_featured = 0';
    }
}

$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$pdo = db();
$c = $pdo->prepare("SELECT COUNT(DISTINCT f.id) FROM features f {$join} {$sqlWhere}");
$c->execute($params);
$total = (int) $c->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT DISTINCT f.* FROM features f {$join} {$sqlWhere}
     ORDER BY f.sort_order ASC, f.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];

foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['is_active'] = (int) $row['is_active'];
    $row['cards'] = feature_load_cards($pdo, (int) $row['id'], false);
    $row['card_count'] = count($row['cards']);
    $ps = $pdo->prepare(
        'SELECT fp.page_id, fp.is_featured, fp.is_active, fp.sort_order, pg.title, pg.slug
         FROM feature_pages fp
         JOIN pages pg ON pg.id = fp.page_id AND pg.deleted_at IS NULL
         WHERE fp.feature_id = :id AND fp.deleted_at IS NULL
         ORDER BY fp.sort_order, pg.title'
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
