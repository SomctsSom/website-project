<?php
declare(strict_types=1);

require_method('GET');

$input = request_input();
$pageId = isset($input['page_id']) ? (int) $input['page_id'] : 0;
$slug = trim((string) ($input['slug'] ?? ''));

$pdo = db();
if ($pageId < 1 && $slug !== '') {
    $s = $pdo->prepare(
        "SELECT id FROM pages WHERE page_type = 'website' AND slug = :slug AND deleted_at IS NULL AND is_active = 1 LIMIT 1"
    );
    $s->execute([':slug' => $slug]);
    $pageId = (int) ($s->fetchColumn() ?: 0);
}
if ($pageId < 1) {
    json_error('page_id or slug is required', 422);
}

$p = $pdo->prepare(
    "SELECT id, title, slug FROM pages
     WHERE id = :id AND page_type = 'website' AND deleted_at IS NULL AND is_active = 1"
);
$p->execute([':id' => $pageId]);
$page = $p->fetch();
if (!$page) {
    json_error('Page not found', 404);
}

$stmt = $pdo->prepare(
    "SELECT po.id, po.title, po.body, po.image_top_path, po.image_left_path, po.image_right_path,
            po.sort_order AS profile_sort, pp.is_featured, pp.sort_order AS page_sort
     FROM profile_overview_pages pp
     JOIN profile_overviews po ON po.id = pp.profile_overview_id
     WHERE pp.page_id = :page_id
       AND pp.deleted_at IS NULL AND pp.is_active = 1
       AND po.deleted_at IS NULL AND po.is_active = 1
     ORDER BY pp.is_featured DESC, pp.sort_order ASC, po.sort_order ASC, po.id ASC"
);
$stmt->execute([':page_id' => $pageId]);
$rows = $stmt->fetchAll() ?: [];
foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['is_featured'] = (int) $row['is_featured'];
    $row = profile_overview_attach_media($row);
    unset($row['image_top_path'], $row['image_left_path'], $row['image_right_path'], $row['body']);
}
unset($row);

json_ok([
    'page' => [
        'id' => (int) $page['id'],
        'title' => $page['title'],
        'slug' => $page['slug'],
    ],
    'items' => $rows,
]);
