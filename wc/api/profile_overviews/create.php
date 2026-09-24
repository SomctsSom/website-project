<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('profile_overviews.create');
auth_verify_csrf($actor);
require __DIR__ . '/_upload.php';

$input = array_merge(request_input(), $_POST);
$title = trim((string) ($input['title'] ?? ''));
$body = sanitize_profile_overview_html((string) ($input['body'] ?? ''));
$sortOrder = (int) ($input['sort_order'] ?? 0);
$isActive = array_key_exists('is_active', $input) ? to_bool_int($input['is_active'], 1) : 1;
$pagesJson = (string) ($input['pages_json'] ?? '[]');
$pageAssignments = json_decode($pagesJson, true);
if (!is_array($pageAssignments)) {
    $pageAssignments = [];
}

if ($title === '') {
    json_error('Title is required', 422);
}

$uploads = [];
$top = profile_overview_take_upload('image_top');
$left = profile_overview_take_upload('image_left');
$right = profile_overview_take_upload('image_right');
foreach ([$top, $left, $right] as $u) {
    if ($u['upload']) {
        $uploads[] = $u['upload']['absolute_path'];
    }
}

$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO profile_overviews (
            title, body, image_top_path, image_left_path, image_right_path,
            sort_order, is_active, created_at, created_by
         ) VALUES (:title, :body, :top, :left, :right, :sort, :active, :created, :by)'
    );
    $stmt->execute([
        ':title' => $title,
        ':body' => $body !== '' ? $body : null,
        ':top' => $top['path'],
        ':left' => $left['path'],
        ':right' => $right['path'],
        ':sort' => $sortOrder,
        ':active' => $isActive,
        ':created' => now_utc(),
        ':by' => $actor['id'],
    ]);
    $id = (int) $pdo->lastInsertId();
    sync_profile_overview_pages($pdo, $id, $pageAssignments, (int) $actor['id']);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    foreach ($uploads as $path) {
        @unlink($path);
    }
    json_error($e->getMessage(), 422);
}

audit_log((int) $actor['id'], 'create', 'profile_overviews', $id, ['title' => $title]);
json_ok(['id' => $id], 'Profile overview created', 201);
