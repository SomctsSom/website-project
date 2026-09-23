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
    "SELECT id, title, slug, template_key
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
json_ok($row);
