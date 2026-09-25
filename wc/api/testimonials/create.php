<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('testimonials.create');
auth_verify_csrf($actor);

$input = array_merge(request_input(), $_POST);
$quote = trim((string) ($input['quote'] ?? ''));
$authorName = trim((string) ($input['author_name'] ?? ''));
$authorRole = trim((string) ($input['author_role'] ?? ''));
$rating = normalize_testimonial_rating($input['rating'] ?? 5);
$sortOrder = (int) ($input['sort_order'] ?? 0);
$isActive = array_key_exists('is_active', $input) ? to_bool_int($input['is_active'], 1) : 1;
$pagesJson = (string) ($input['pages_json'] ?? '[]');
$pageAssignments = json_decode($pagesJson, true);
if (!is_array($pageAssignments)) {
    $pageAssignments = [];
}

if ($quote === '') {
    json_error('Quote is required', 422);
}
if ($authorName === '') {
    json_error('Author name is required', 422);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO testimonials (rating, quote, author_name, author_role, sort_order, is_active, created_at, created_by)
         VALUES (:rating, :quote, :name, :role, :sort, :active, :created, :by)'
    );
    $stmt->execute([
        ':rating' => $rating,
        ':quote' => $quote,
        ':name' => $authorName,
        ':role' => $authorRole !== '' ? $authorRole : null,
        ':sort' => $sortOrder,
        ':active' => $isActive,
        ':created' => now_utc(),
        ':by' => $actor['id'],
    ]);
    $id = (int) $pdo->lastInsertId();
    sync_testimonial_pages($pdo, $id, $pageAssignments, (int) $actor['id']);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error($e->getMessage(), 422);
}

audit_log((int) $actor['id'], 'create', 'testimonials', $id, ['author_name' => $authorName]);
json_ok(['id' => $id], 'Testimonial created', 201);
