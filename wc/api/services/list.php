<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('services.view');

$input = request_input();
[$page, $perPage, $offset] = paginate_params($input);
$q = trim((string) ($input['q'] ?? ''));
$pageId = isset($input['page_id']) && $input['page_id'] !== '' ? (int) $input['page_id'] : null;
$featured = (string) ($input['featured'] ?? '');
$status = (string) ($input['status'] ?? '');
$trashed = ((string) ($input['trashed'] ?? '')) === '1';

$where = [$trashed ? 's.deleted_at IS NOT NULL' : 's.deleted_at IS NULL'];
$params = [];
$join = '';

if ($q !== '') {
    $where[] = '(s.title LIKE :q OR s.description LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($status === 'active') {
    $where[] = 's.is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 's.is_active = 0';
}
if ($pageId !== null || $featured !== '') {
    $join = 'JOIN service_pages sp ON sp.service_id = s.id AND sp.deleted_at IS NULL';
    if ($pageId !== null) {
        $where[] = 'sp.page_id = :page_id';
        $params[':page_id'] = $pageId;
    }
    if ($featured === '1') {
        $where[] = 'sp.is_featured = 1 AND sp.is_active = 1';
    } elseif ($featured === '0') {
        $where[] = 'sp.is_featured = 0';
    }
}

$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$pdo = db();
$c = $pdo->prepare("SELECT COUNT(DISTINCT s.id) FROM services s {$join} {$sqlWhere}");
$c->execute($params);
$total = (int) $c->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT DISTINCT s.* FROM services s {$join} {$sqlWhere}
     ORDER BY s.sort_order ASC, s.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];

foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['is_active'] = (int) $row['is_active'];
    $row['image_url'] = $row['image_path'] ? secure_public_media_url($row['image_path']) : null;
    $ps = $pdo->prepare(
        'SELECT sp.page_id, sp.is_featured, sp.is_active, sp.sort_order, p.title, p.slug
         FROM service_pages sp
         JOIN pages p ON p.id = sp.page_id AND p.deleted_at IS NULL
         WHERE sp.service_id = :id AND sp.deleted_at IS NULL
         ORDER BY sp.sort_order, p.title'
    );
    $ps->execute([':id' => $row['id']]);
    $pages = $ps->fetchAll() ?: [];
    $row['pages'] = $pages;
    $row['page_count'] = count($pages);
    $items = service_load_items($pdo, (int) $row['id'], false);
    $row['items'] = $items;
    $row['item_count'] = count($items);
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
