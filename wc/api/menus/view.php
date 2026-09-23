<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('menus.view');

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid menu id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM menus WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Menu not found', 404);
}

$pages = $pdo->prepare(
    'SELECT mp.id AS menu_page_id, mp.sort_order, p.id, p.title, p.slug, p.page_type, p.is_active, p.template_key
     FROM menu_pages mp
     JOIN pages p ON p.id = mp.page_id
     WHERE mp.menu_id = :id AND mp.deleted_at IS NULL AND p.deleted_at IS NULL
     ORDER BY mp.sort_order, p.title'
);
$pages->execute([':id' => $id]);
$row['pages'] = $pages->fetchAll() ?: [];
$row['id'] = (int) $row['id'];
$row['is_active'] = (int) $row['is_active'];
$row['created_at_display'] = format_display_time($row['created_at']);
json_ok($row);
