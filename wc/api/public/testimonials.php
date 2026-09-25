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
    "SELECT t.id, t.rating, t.quote, t.author_name, t.author_role,
            t.sort_order AS testimonial_sort, tp.is_featured, tp.sort_order AS page_sort
     FROM testimonial_pages tp
     JOIN testimonials t ON t.id = tp.testimonial_id
     WHERE tp.page_id = :page_id
       AND tp.deleted_at IS NULL AND tp.is_active = 1
       AND t.deleted_at IS NULL AND t.is_active = 1
     ORDER BY tp.is_featured DESC, tp.sort_order ASC, t.sort_order ASC, t.id ASC"
);
$stmt->execute([':page_id' => $pageId]);
$rows = $stmt->fetchAll() ?: [];
foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['is_featured'] = (int) $row['is_featured'];
    $row = testimonial_attach_display($row);
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
