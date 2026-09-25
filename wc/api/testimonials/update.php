<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('testimonials.edit');
auth_verify_csrf($actor);

$input = array_merge(request_input(), $_POST);
$id = (int) ($input['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid testimonial id', 422);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM testimonials WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
$existing = $stmt->fetch();
if (!$existing) {
    json_error('Testimonial not found', 404);
}

$quote = array_key_exists('quote', $input) ? trim((string) $input['quote']) : (string) $existing['quote'];
$authorName = array_key_exists('author_name', $input)
    ? trim((string) $input['author_name'])
    : (string) $existing['author_name'];
$authorRole = array_key_exists('author_role', $input)
    ? trim((string) $input['author_role'])
    : (string) ($existing['author_role'] ?? '');
$rating = array_key_exists('rating', $input)
    ? normalize_testimonial_rating($input['rating'], (int) $existing['rating'])
    : normalize_testimonial_rating($existing['rating']);
$sortOrder = (int) ($input['sort_order'] ?? $existing['sort_order']);
$isActive = array_key_exists('is_active', $input)
    ? to_bool_int($input['is_active'], (int) $existing['is_active'])
    : (int) $existing['is_active'];

$pageAssignments = null;
if (array_key_exists('pages_json', $input) && (string) $input['pages_json'] !== '') {
    $pageAssignments = json_decode((string) $input['pages_json'], true);
    if (!is_array($pageAssignments)) {
        json_error('Invalid pages_json', 422);
    }
}

if ($quote === '') {
    json_error('Quote is required', 422);
}
if ($authorName === '') {
    json_error('Author name is required', 422);
}

$pdo->beginTransaction();
try {
    $pdo->prepare(
        'UPDATE testimonials SET rating = :rating, quote = :quote, author_name = :name, author_role = :role,
         sort_order = :sort, is_active = :active, updated_at = :u, updated_by = :by WHERE id = :id'
    )->execute([
        ':rating' => $rating,
        ':quote' => $quote,
        ':name' => $authorName,
        ':role' => $authorRole !== '' ? $authorRole : null,
        ':sort' => $sortOrder,
        ':active' => $isActive,
        ':u' => now_utc(),
        ':by' => $actor['id'],
        ':id' => $id,
    ]);
    if ($pageAssignments !== null) {
        sync_testimonial_pages($pdo, $id, $pageAssignments, (int) $actor['id']);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error($e->getMessage(), 422);
}

audit_log((int) $actor['id'], 'update', 'testimonials', $id, ['author_name' => $authorName]);
json_ok(['id' => $id], 'Testimonial updated');
