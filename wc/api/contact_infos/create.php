<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('contact_social.create');
auth_verify_csrf($actor);

$input = array_merge(request_input(), $_POST);
$summary = trim((string) ($input['company_summary'] ?? ''));
$companyName = trim((string) ($input['company_name'] ?? ''));
$tagline = trim((string) ($input['tagline'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$businessHours = trim((string) ($input['business_hours'] ?? ''));
$sortOrder = (int) ($input['sort_order'] ?? 0);
$isActive = array_key_exists('is_active', $input) ? to_bool_int($input['is_active'], 1) : 1;

$addresses = json_decode((string) ($input['addresses_json'] ?? '[]'), true);
$phones = json_decode((string) ($input['phones_json'] ?? '[]'), true);
$pageAssignments = json_decode((string) ($input['pages_json'] ?? '[]'), true);
if (!is_array($addresses)) {
    $addresses = [];
}
if (!is_array($phones)) {
    $phones = [];
}
if (!is_array($pageAssignments)) {
    $pageAssignments = [];
}

$hasAddress = false;
foreach ($addresses as $a) {
    if (trim((string) ($a['address_text'] ?? $a['address'] ?? '')) !== '') {
        $hasAddress = true;
        break;
    }
}
$hasPhone = false;
foreach ($phones as $p) {
    if (trim((string) ($p['phone'] ?? '')) !== '') {
        $hasPhone = true;
        break;
    }
}
if ($summary === '' && $companyName === '' && !$hasAddress && !$hasPhone) {
    json_error('Add a company name, summary, address, or phone', 422);
}

$logoPath = null;
if (isset($_FILES['logo']) && (int) ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $upload = secure_upload_image($_FILES['logo'], 'contact_logos');
    if (!$upload['ok']) {
        json_error($upload['error'], 422);
    }
    $logoPath = $upload['relative_path'];
}

$pdo = db();
$pdo->beginTransaction();
try {
    $pdo->prepare(
        'INSERT INTO contact_infos (
            company_summary, company_name, tagline, email, business_hours, logo_path, sort_order, is_active, created_at, created_by
         ) VALUES (
            :summary, :name, :tagline, :email, :hours, :logo, :sort, :active, :created, :by
         )'
    )->execute([
        ':summary' => $summary !== '' ? $summary : null,
        ':name' => $companyName !== '' ? mb_substr($companyName, 0, 190) : null,
        ':tagline' => $tagline !== '' ? mb_substr($tagline, 0, 255) : null,
        ':email' => $email !== '' ? mb_substr($email, 0, 190) : null,
        ':hours' => $businessHours !== '' ? mb_substr($businessHours, 0, 255) : null,
        ':logo' => $logoPath,
        ':sort' => $sortOrder,
        ':active' => $isActive,
        ':created' => now_utc(),
        ':by' => $actor['id'],
    ]);
    $id = (int) $pdo->lastInsertId();
    sync_contact_addresses($pdo, $id, $addresses);
    sync_contact_phones($pdo, $id, $phones);
    sync_contact_info_pages($pdo, $id, $pageAssignments, (int) $actor['id']);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error($e->getMessage(), 422);
}

audit_log((int) $actor['id'], 'create', 'contact_infos', $id);
json_ok(['id' => $id], 'Contact info created', 201);
