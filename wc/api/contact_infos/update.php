<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('contact_social.edit');
auth_verify_csrf($actor);

$input = array_merge(request_input(), $_POST);
$id = (int) ($input['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM contact_infos WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
$existing = $stmt->fetch();
if (!$existing) {
    json_error('Not found', 404);
}

$summary = array_key_exists('company_summary', $input)
    ? trim((string) $input['company_summary'])
    : (string) ($existing['company_summary'] ?? '');
$companyName = array_key_exists('company_name', $input)
    ? trim((string) $input['company_name'])
    : (string) ($existing['company_name'] ?? '');
$tagline = array_key_exists('tagline', $input)
    ? trim((string) $input['tagline'])
    : (string) ($existing['tagline'] ?? '');
$email = array_key_exists('email', $input)
    ? trim((string) $input['email'])
    : (string) ($existing['email'] ?? '');
$businessHours = array_key_exists('business_hours', $input)
    ? trim((string) $input['business_hours'])
    : (string) ($existing['business_hours'] ?? '');
$sortOrder = array_key_exists('sort_order', $input) ? (int) $input['sort_order'] : (int) $existing['sort_order'];
$isActive = array_key_exists('is_active', $input) ? to_bool_int($input['is_active'], 1) : (int) $existing['is_active'];
$logoPath = $existing['logo_path'] ?? null;

$addresses = array_key_exists('addresses_json', $input)
    ? json_decode((string) $input['addresses_json'], true)
    : null;
$phones = array_key_exists('phones_json', $input)
    ? json_decode((string) $input['phones_json'], true)
    : null;
$pageAssignments = array_key_exists('pages_json', $input)
    ? json_decode((string) $input['pages_json'], true)
    : null;

$newUpload = null;
if (isset($_FILES['logo']) && (int) ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $newUpload = secure_upload_image($_FILES['logo'], 'contact_logos');
    if (!$newUpload['ok']) {
        json_error($newUpload['error'], 422);
    }
    $logoPath = $newUpload['relative_path'];
}
if (!empty($input['clear_logo'])) {
    $logoPath = null;
}

$pdo->beginTransaction();
try {
    $pdo->prepare(
        'UPDATE contact_infos SET company_summary = :summary, company_name = :name, tagline = :tagline, email = :email,
         business_hours = :hours, logo_path = :logo, sort_order = :sort, is_active = :active,
         updated_at = :u, updated_by = :by WHERE id = :id'
    )->execute([
        ':summary' => $summary !== '' ? $summary : null,
        ':name' => $companyName !== '' ? mb_substr($companyName, 0, 190) : null,
        ':tagline' => $tagline !== '' ? mb_substr($tagline, 0, 255) : null,
        ':email' => $email !== '' ? mb_substr($email, 0, 190) : null,
        ':hours' => $businessHours !== '' ? mb_substr($businessHours, 0, 255) : null,
        ':logo' => $logoPath,
        ':sort' => $sortOrder,
        ':active' => $isActive,
        ':u' => now_utc(),
        ':by' => $actor['id'],
        ':id' => $id,
    ]);
    if (is_array($addresses)) {
        sync_contact_addresses($pdo, $id, $addresses);
    }
    if (is_array($phones)) {
        sync_contact_phones($pdo, $id, $phones);
    }
    if (is_array($pageAssignments)) {
        sync_contact_info_pages($pdo, $id, $pageAssignments, (int) $actor['id']);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error($e->getMessage(), 422);
}

audit_log((int) $actor['id'], 'update', 'contact_infos', $id, [
    'logo_replaced' => $newUpload !== null,
]);
json_ok(['id' => $id], 'Contact info updated');
