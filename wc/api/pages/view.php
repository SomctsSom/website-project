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
$row['banner_enabled'] = (int) ($row['banner_enabled'] ?? 0);
$row['banner_size_preset'] = normalize_hero_size_preset($row['banner_size_preset'] ?? 'md');
$row['banner_size_label'] = hero_size_presets()[$row['banner_size_preset']];
$rawBg = trim((string) ($row['bg_color'] ?? ''));
$row['bg_color'] = $rawBg !== '' ? sanitize_navbar_hex($rawBg, '') : null;
if ($row['bg_color'] === '') {
    $row['bg_color'] = null;
}
$rawCardBg = trim((string) ($row['card_bg_color'] ?? ''));
$row['card_bg_color'] = $rawCardBg !== '' ? sanitize_navbar_hex($rawCardBg, '') : null;
if ($row['card_bg_color'] === '') {
    $row['card_bg_color'] = null;
}
$row['banner_image_url'] = !empty($row['banner_image_path'])
    ? secure_public_media_url((string) $row['banner_image_path'])
    : null;
$row['created_at_display'] = format_display_time($row['created_at']);
if (($row['page_type'] ?? '') === 'website') {
    $row['section_order'] = get_page_section_order($pdo, $id);
}
json_ok($row);
