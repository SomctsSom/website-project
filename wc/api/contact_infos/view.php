<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('contact_social.view');

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM contact_infos WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Not found', 404);
}
$row['id'] = (int) $row['id'];
$row['is_active'] = (int) $row['is_active'];
$row['sort_order'] = (int) $row['sort_order'];
$row['logo_url'] = !empty($row['logo_path'])
    ? secure_public_media_url((string) $row['logo_path'])
    : null;
$children = contact_info_load_children($pdo, $id);
$row['addresses'] = $children['addresses'];
$row['phones'] = $children['phones'];
$pages = $pdo->prepare(
    'SELECT page_id, is_featured, is_active, sort_order FROM contact_info_pages
     WHERE contact_info_id = :id AND deleted_at IS NULL'
);
$pages->execute([':id' => $id]);
$row['pages'] = $pages->fetchAll() ?: [];
foreach ($row['pages'] as &$p) {
    $p['page_id'] = (int) $p['page_id'];
    $p['is_featured'] = (int) $p['is_featured'];
    $p['is_active'] = (int) $p['is_active'];
    $p['sort_order'] = (int) $p['sort_order'];
}
unset($p);
json_ok($row);
