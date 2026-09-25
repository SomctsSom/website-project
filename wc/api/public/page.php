<?php
declare(strict_types=1);

require_method('GET');

$input = request_input();
$slug = trim((string) ($input['slug'] ?? 'home'));
$reserved = app_config('reserved_slugs', []);
if (in_array($slug, $reserved, true)) {
    json_error('Not found', 404);
}

$pdo = db();
$stmt = $pdo->prepare(
    "SELECT id, title, slug, template_key,
            banner_enabled, banner_size_preset, banner_eyebrow, banner_title, banner_subtitle, banner_image_path,
            bg_color, card_bg_color
     FROM pages
     WHERE page_type = 'website' AND slug = :slug AND deleted_at IS NULL AND is_active = 1
     LIMIT 1"
);
$stmt->execute([':slug' => $slug]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Page not found', 404);
}
$row['id'] = (int) $row['id'];
$row['banner_enabled'] = (int) ($row['banner_enabled'] ?? 0);
$row['banner_size_preset'] = normalize_hero_size_preset($row['banner_size_preset'] ?? 'md');
$row['banner_image_url'] = !empty($row['banner_image_path'])
    ? secure_public_media_url((string) $row['banner_image_path'])
    : null;
unset($row['banner_image_path']);
$rawPageBg = trim((string) ($row['bg_color'] ?? ''));
$row['bg_color'] = $rawPageBg !== '' ? sanitize_navbar_hex($rawPageBg, '') : null;
if ($row['bg_color'] === '') {
    $row['bg_color'] = null;
}
$rawCardBg = trim((string) ($row['card_bg_color'] ?? ''));
$row['card_bg_color'] = $rawCardBg !== '' ? sanitize_navbar_hex($rawCardBg, '') : null;
if ($row['card_bg_color'] === '') {
    $row['card_bg_color'] = null;
}
$row['section_order'] = get_page_section_order($pdo, (int) $row['id']);
json_ok($row);
