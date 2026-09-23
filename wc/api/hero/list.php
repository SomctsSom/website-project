<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('hero.view');

$input = request_input();
[$page, $perPage, $offset] = paginate_params($input);
$q = trim((string) ($input['q'] ?? ''));
$pageId = isset($input['page_id']) && $input['page_id'] !== '' ? (int) $input['page_id'] : null;
$featured = (string) ($input['featured'] ?? '');
$status = (string) ($input['status'] ?? '');
$trashed = ((string) ($input['trashed'] ?? '')) === '1';

$where = [$trashed ? 'h.deleted_at IS NOT NULL' : 'h.deleted_at IS NULL'];
$params = [];
$join = '';

if ($q !== '') {
    $where[] = '(h.title LIKE :q OR h.description LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($status === 'active') {
    $where[] = 'h.is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'h.is_active = 0';
}
if ($pageId !== null || $featured !== '') {
    $join = 'JOIN hero_pages hp ON hp.hero_id = h.id AND hp.deleted_at IS NULL';
    if ($pageId !== null) {
        $where[] = 'hp.page_id = :page_id';
        $params[':page_id'] = $pageId;
    }
    if ($featured === '1') {
        $where[] = 'hp.is_featured = 1 AND hp.is_active = 1';
    } elseif ($featured === '0') {
        $where[] = 'hp.is_featured = 0';
    }
}

$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$pdo = db();
$countSql = "SELECT COUNT(DISTINCT h.id) FROM hero h {$join} {$sqlWhere}";
$c = $pdo->prepare($countSql);
$c->execute($params);
$total = (int) $c->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT DISTINCT h.* FROM hero h {$join} {$sqlWhere}
     ORDER BY h.sort_order ASC, h.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];

$heroSize = get_hero_size_preset($pdo);
foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['is_active'] = (int) $row['is_active'];
    $row['image_url'] = secure_public_media_url($row['image_path']);
    $ps = $pdo->prepare(
        'SELECT hp.page_id, hp.is_featured, hp.is_active, hp.sort_order, p.title, p.slug
         FROM hero_pages hp
         JOIN pages p ON p.id = hp.page_id AND p.deleted_at IS NULL
         WHERE hp.hero_id = :id AND hp.deleted_at IS NULL
         ORDER BY hp.sort_order, p.title'
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
    'hero_size' => [
        'size_preset' => $heroSize,
        'size_label' => hero_size_presets()[$heroSize],
        'options' => hero_size_presets(),
    ],
    'pagination' => [
        'page' => $page, 'per_page' => $perPage, 'total' => $total,
        'total_pages' => (int) ceil($total / max(1, $perPage)),
    ],
]);
