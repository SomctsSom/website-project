<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('vision_missions.view');

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid vision/mission id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM vision_missions WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Vision & Mission not found', 404);
}
$row['id'] = (int) $row['id'];
$row['is_active'] = (int) $row['is_active'];
$row = vision_mission_attach_media($row);
$ps = $pdo->prepare(
    'SELECT vp.id AS vision_page_id, vp.page_id, vp.is_featured, vp.is_active, vp.sort_order, p.title, p.slug, p.page_type
     FROM vision_mission_pages vp
     JOIN pages p ON p.id = vp.page_id
     WHERE vp.vision_mission_id = :id AND vp.deleted_at IS NULL
     ORDER BY vp.sort_order, p.title'
);
$ps->execute([':id' => $id]);
$row['pages'] = $ps->fetchAll() ?: [];
$row['created_at_display'] = format_display_time($row['created_at']);
json_ok($row);
