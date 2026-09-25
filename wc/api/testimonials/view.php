<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('testimonials.view');

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid testimonial id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM testimonials WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Testimonial not found', 404);
}
$row['id'] = (int) $row['id'];
$row['is_active'] = (int) $row['is_active'];
$row = testimonial_attach_display($row);
$ps = $pdo->prepare(
    'SELECT tp.id AS testimonial_page_id, tp.page_id, tp.is_featured, tp.is_active, tp.sort_order, p.title, p.slug, p.page_type
     FROM testimonial_pages tp
     JOIN pages p ON p.id = tp.page_id
     WHERE tp.testimonial_id = :id AND tp.deleted_at IS NULL
     ORDER BY tp.sort_order, p.title'
);
$ps->execute([':id' => $id]);
$row['pages'] = $ps->fetchAll() ?: [];
$row['created_at_display'] = format_display_time($row['created_at']);
json_ok($row);
