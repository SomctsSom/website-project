<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('hero.create');
auth_verify_csrf($actor);

$input = array_merge(request_input(), $_POST);
$title = trim((string) ($input['title'] ?? ''));
$description = trim((string) ($input['description'] ?? ''));
$buttonText = trim((string) ($input['button_text'] ?? ''));
$buttonUrl = trim((string) ($input['button_url'] ?? ''));
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
if ($buttonUrl !== '' && !is_safe_url($buttonUrl)) {
    json_error('Invalid button URL', 422);
}
if (!isset($_FILES['image'])) {
    json_error('Image is required', 422);
}

$upload = secure_upload_image($_FILES['image'], 'hero');
if (!$upload['ok']) {
    json_error($upload['error'], 422);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO hero (title, description, image_path, button_text, button_url, sort_order, is_active, created_at, created_by)
         VALUES (:title, :description, :image, :bt, :bu, :sort, :active, :created, :by)'
    );
    $stmt->execute([
        ':title' => $title,
        ':description' => $description !== '' ? $description : null,
        ':image' => $upload['relative_path'],
        ':bt' => $buttonText !== '' ? $buttonText : null,
        ':bu' => $buttonUrl !== '' ? $buttonUrl : null,
        ':sort' => $sortOrder,
        ':active' => $isActive,
        ':created' => now_utc(),
        ':by' => $actor['id'],
    ]);
    $id = (int) $pdo->lastInsertId();
    sync_hero_pages($pdo, $id, $pageAssignments, (int) $actor['id']);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    @unlink($upload['absolute_path']);
    json_error($e->getMessage(), 422);
}

audit_log((int) $actor['id'], 'create', 'hero', $id, ['title' => $title]);
json_ok(['id' => $id, 'image_path' => $upload['relative_path']], 'Hero created', 201);
