<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('profile_overviews.edit');
auth_verify_csrf($actor);
require __DIR__ . '/_upload.php';

$input = array_merge(request_input(), $_POST);
$id = (int) ($input['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid profile overview id', 422);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM profile_overviews WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
$existing = $stmt->fetch();
if (!$existing) {
    json_error('Profile overview not found', 404);
}

$title = trim((string) ($input['title'] ?? $existing['title']));
$body = array_key_exists('body', $input)
    ? sanitize_profile_overview_html((string) $input['body'])
    : sanitize_profile_overview_html((string) ($existing['body'] ?? ''));
$sortOrder = (int) ($input['sort_order'] ?? $existing['sort_order']);
$isActive = array_key_exists('is_active', $input)
    ? to_bool_int($input['is_active'], (int) $existing['is_active'])
    : (int) $existing['is_active'];

$topPath = $existing['image_top_path'];
$leftPath = $existing['image_left_path'];
$rightPath = $existing['image_right_path'];
$uploads = [];

$top = profile_overview_take_upload('image_top');
$left = profile_overview_take_upload('image_left');
$right = profile_overview_take_upload('image_right');
if ($top['path']) {
    $topPath = $top['path'];
    $uploads[] = $top['upload']['absolute_path'];
}
if ($left['path']) {
    $leftPath = $left['path'];
    $uploads[] = $left['upload']['absolute_path'];
}
if ($right['path']) {
    $rightPath = $right['path'];
    $uploads[] = $right['upload']['absolute_path'];
}
if (!empty($input['clear_image_top'])) {
    $topPath = null;
}
if (!empty($input['clear_image_left'])) {
    $leftPath = null;
}
if (!empty($input['clear_image_right'])) {
    $rightPath = null;
}

if ($title === '') {
    json_error('Title is required', 422);
}

$pagesJson = (string) ($input['pages_json'] ?? '');
$pageAssignments = null;
if ($pagesJson !== '') {
    $pageAssignments = json_decode($pagesJson, true);
    if (!is_array($pageAssignments)) {
        json_error('Invalid pages_json', 422);
    }
}

$pdo->beginTransaction();
try {
    $pdo->prepare(
        'UPDATE profile_overviews SET title = :title, body = :body,
         image_top_path = :top, image_left_path = :left, image_right_path = :right,
         sort_order = :sort, is_active = :active, updated_at = :u, updated_by = :by WHERE id = :id'
    )->execute([
        ':title' => $title,
        ':body' => $body !== '' ? $body : null,
        ':top' => $topPath,
        ':left' => $leftPath,
        ':right' => $rightPath,
        ':sort' => $sortOrder,
        ':active' => $isActive,
        ':u' => now_utc(),
        ':by' => $actor['id'],
        ':id' => $id,
    ]);
    if ($pageAssignments !== null) {
        sync_profile_overview_pages($pdo, $id, $pageAssignments, (int) $actor['id']);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    foreach ($uploads as $path) {
        @unlink($path);
    }
    json_error($e->getMessage(), 422);
}

audit_log((int) $actor['id'], 'update', 'profile_overviews', $id, ['title' => $title]);
json_ok(['id' => $id], 'Profile overview updated');
