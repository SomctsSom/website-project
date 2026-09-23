<?php
declare(strict_types=1);

require_method('GET');

$pdo = db();
$stmt = $pdo->query(
    "SELECT id, title, slug, template_key
     FROM pages
     WHERE page_type = 'website' AND deleted_at IS NULL AND is_active = 1
     ORDER BY title"
);
$rows = $stmt->fetchAll() ?: [];
foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
}
unset($row);
json_ok(['items' => $rows]);
