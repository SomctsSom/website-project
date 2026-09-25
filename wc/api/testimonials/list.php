<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('testimonials.view');

$input = request_input();
[$page, $perPage, $offset] = paginate_params($input);
$q = trim((string) ($input['q'] ?? ''));
$pageId = isset($input['page_id']) && $input['page_id'] !== '' ? (int) $input['page_id'] : null;
$featured = (string) ($input['featured'] ?? '');
$status = (string) ($input['status'] ?? '');
$trashed = ((string) ($input['trashed'] ?? '')) === '1';

$where = [$trashed ? 't.deleted_at IS NOT NULL' : 't.deleted_at IS NULL'];
$params = [];
$join = '';

if ($q !== '') {
    $where[] = '(t.quote LIKE :q OR t.author_name LIKE :q OR t.author_role LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($status === 'active') {
    $where[] = 't.is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 't.is_active = 0';
}
if ($pageId !== null || $featured !== '') {
    $join = 'JOIN testimonial_pages tp ON tp.testimonial_id = t.id AND tp.deleted_at IS NULL';
    if ($pageId !== null) {
        $where[] = 'tp.page_id = :page_id';
        $params[':page_id'] = $pageId;
    }
    if ($featured === '1') {
        $where[] = 'tp.is_featured = 1 AND tp.is_active = 1';
    } elseif ($featured === '0') {
        $where[] = 'tp.is_featured = 0';
    }
}

$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$pdo = db();
$c = $pdo->prepare("SELECT COUNT(DISTINCT t.id) FROM testimonials t {$join} {$sqlWhere}");
$c->execute($params);
$total = (int) $c->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT DISTINCT t.* FROM testimonials t {$join} {$sqlWhere}
     ORDER BY t.sort_order ASC, t.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];

foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['is_active'] = (int) $row['is_active'];
    $row = testimonial_attach_display($row);
    $ps = $pdo->prepare(
        'SELECT tp.page_id, tp.is_featured, tp.is_active, tp.sort_order, pg.title, pg.slug
         FROM testimonial_pages tp
         JOIN pages pg ON pg.id = tp.page_id AND pg.deleted_at IS NULL
         WHERE tp.testimonial_id = :id AND tp.deleted_at IS NULL
         ORDER BY tp.sort_order, pg.title'
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
