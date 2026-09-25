<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('contact_social.view');

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM social_links WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Not found', 404);
}
$row = social_link_attach_display($row);
$pages = $pdo->prepare(
    'SELECT page_id, is_featured, is_active, sort_order FROM social_link_pages
     WHERE social_link_id = :id AND deleted_at IS NULL'
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
