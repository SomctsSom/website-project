<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('features.view');

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid feature id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM features WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Feature section not found', 404);
}
$row['id'] = (int) $row['id'];
$row['is_active'] = (int) $row['is_active'];
$ps = $pdo->prepare(
    'SELECT fp.id AS feature_page_id, fp.page_id, fp.is_featured, fp.is_active, fp.sort_order, p.title, p.slug, p.page_type
     FROM feature_pages fp
     JOIN pages p ON p.id = fp.page_id
     WHERE fp.feature_id = :id AND fp.deleted_at IS NULL
     ORDER BY fp.sort_order, p.title'
);
$ps->execute([':id' => $id]);
$row['pages'] = $ps->fetchAll() ?: [];
$row['cards'] = feature_load_cards($pdo, $id, false);
$row['created_at_display'] = format_display_time($row['created_at']);
json_ok($row);
