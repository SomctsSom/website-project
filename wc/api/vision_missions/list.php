<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('vision_missions.view');

$input = request_input();
[$page, $perPage, $offset] = paginate_params($input);
$q = trim((string) ($input['q'] ?? ''));
$pageId = isset($input['page_id']) && $input['page_id'] !== '' ? (int) $input['page_id'] : null;
$featured = (string) ($input['featured'] ?? '');
$status = (string) ($input['status'] ?? '');
$type = trim((string) ($input['statement_type'] ?? ''));
$trashed = ((string) ($input['trashed'] ?? '')) === '1';

$where = [$trashed ? 'v.deleted_at IS NOT NULL' : 'v.deleted_at IS NULL'];
$params = [];
$join = '';

if ($q !== '') {
    $where[] = '(v.title LIKE :q OR v.body LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($status === 'active') {
    $where[] = 'v.is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'v.is_active = 0';
}
if ($type !== '' && in_array($type, vision_mission_types(), true)) {
    $where[] = 'v.statement_type = :stype';
    $params[':stype'] = $type;
}
if ($pageId !== null || $featured !== '') {
    $join = 'JOIN vision_mission_pages vp ON vp.vision_mission_id = v.id AND vp.deleted_at IS NULL';
    if ($pageId !== null) {
        $where[] = 'vp.page_id = :page_id';
        $params[':page_id'] = $pageId;
    }
    if ($featured === '1') {
        $where[] = 'vp.is_featured = 1 AND vp.is_active = 1';
    } elseif ($featured === '0') {
        $where[] = 'vp.is_featured = 0';
    }
}

$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$pdo = db();
$c = $pdo->prepare("SELECT COUNT(DISTINCT v.id) FROM vision_missions v {$join} {$sqlWhere}");
$c->execute($params);
$total = (int) $c->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT DISTINCT v.* FROM vision_missions v {$join} {$sqlWhere}
     ORDER BY v.sort_order ASC, v.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];

foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['is_active'] = (int) $row['is_active'];
    $row = vision_mission_attach_media($row);
    $ps = $pdo->prepare(
        'SELECT vp.page_id, vp.is_featured, vp.is_active, vp.sort_order, pg.title, pg.slug
         FROM vision_mission_pages vp
         JOIN pages pg ON pg.id = vp.page_id AND pg.deleted_at IS NULL
         WHERE vp.vision_mission_id = :id AND vp.deleted_at IS NULL
         ORDER BY vp.sort_order, pg.title'
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
