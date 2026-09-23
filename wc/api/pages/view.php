<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('pages.view');

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid page id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM pages WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Page not found', 404);
}

$menus = $pdo->prepare(
    'SELECT m.id, m.title, m.menu_type, mp.sort_order
     FROM menu_pages mp
     JOIN menus m ON m.id = mp.menu_id AND m.deleted_at IS NULL
     WHERE mp.page_id = :id AND mp.deleted_at IS NULL'
);
$menus->execute([':id' => $id]);
$row['menus'] = $menus->fetchAll() ?: [];
$row['id'] = (int) $row['id'];
$row['is_active'] = (int) $row['is_active'];
$row['created_at_display'] = format_display_time($row['created_at']);
json_ok($row);
