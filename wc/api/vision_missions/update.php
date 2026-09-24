<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('vision_missions.edit');
auth_verify_csrf($actor);
require __DIR__ . '/_upload.php';

$input = array_merge(request_input(), $_POST);
$id = (int) ($input['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid vision/mission id', 422);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM vision_missions WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
$existing = $stmt->fetch();
if (!$existing) {
    json_error('Vision & Mission not found', 404);
}

$title = trim((string) ($input['title'] ?? $existing['title']));
$body = array_key_exists('body', $input)
    ? sanitize_vision_mission_html((string) $input['body'])
    : sanitize_vision_mission_html((string) ($existing['body'] ?? ''));
$statementType = array_key_exists('statement_type', $input)
    ? normalize_vision_mission_type($input['statement_type'])
    : normalize_vision_mission_type($existing['statement_type'] ?? 'vision');
$sortOrder = (int) ($input['sort_order'] ?? $existing['sort_order']);
$isActive = array_key_exists('is_active', $input)
    ? to_bool_int($input['is_active'], (int) $existing['is_active'])
    : (int) $existing['is_active'];

$imagePath = $existing['image_path'];
$uploads = [];
$upload = vision_mission_take_upload('image');
if ($upload['path']) {
    $imagePath = $upload['path'];
    $uploads[] = $upload['upload']['absolute_path'];
}
if (!empty($input['clear_image'])) {
    $imagePath = null;
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
        'UPDATE vision_missions SET statement_type = :type, title = :title, body = :body,
         image_path = :image, sort_order = :sort, is_active = :active, updated_at = :u, updated_by = :by WHERE id = :id'
    )->execute([
        ':type' => $statementType,
        ':title' => $title,
        ':body' => $body !== '' ? $body : null,
        ':image' => $imagePath,
        ':sort' => $sortOrder,
        ':active' => $isActive,
        ':u' => now_utc(),
        ':by' => $actor['id'],
        ':id' => $id,
    ]);
    if ($pageAssignments !== null) {
        sync_vision_mission_pages($pdo, $id, $pageAssignments, (int) $actor['id']);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    foreach ($uploads as $path) {
        @unlink($path);
    }
    json_error($e->getMessage(), 422);
}

audit_log((int) $actor['id'], 'update', 'vision_missions', $id, ['title' => $title, 'statement_type' => $statementType]);
json_ok(['id' => $id], 'Vision & Mission updated');
