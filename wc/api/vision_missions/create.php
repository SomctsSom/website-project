<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('vision_missions.create');
auth_verify_csrf($actor);
require __DIR__ . '/_upload.php';

$input = array_merge(request_input(), $_POST);
$title = trim((string) ($input['title'] ?? ''));
$body = sanitize_vision_mission_html((string) ($input['body'] ?? ''));
$statementType = normalize_vision_mission_type($input['statement_type'] ?? 'vision');
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

$upload = vision_mission_take_upload('image');
$uploads = [];
if ($upload['upload']) {
    $uploads[] = $upload['upload']['absolute_path'];
}

$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO vision_missions (
            statement_type, title, body, image_path, sort_order, is_active, created_at, created_by
         ) VALUES (:type, :title, :body, :image, :sort, :active, :created, :by)'
    );
    $stmt->execute([
        ':type' => $statementType,
        ':title' => $title,
        ':body' => $body !== '' ? $body : null,
        ':image' => $upload['path'],
        ':sort' => $sortOrder,
        ':active' => $isActive,
        ':created' => now_utc(),
        ':by' => $actor['id'],
    ]);
    $id = (int) $pdo->lastInsertId();
    sync_vision_mission_pages($pdo, $id, $pageAssignments, (int) $actor['id']);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    foreach ($uploads as $path) {
        @unlink($path);
    }
    json_error($e->getMessage(), 422);
}

audit_log((int) $actor['id'], 'create', 'vision_missions', $id, ['title' => $title, 'statement_type' => $statementType]);
json_ok(['id' => $id], 'Vision & Mission created', 201);
