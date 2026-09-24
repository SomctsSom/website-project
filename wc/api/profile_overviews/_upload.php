<?php
declare(strict_types=1);

/**
 * @return array{path:?string,upload:?array}
 */
function profile_overview_take_upload(string $field): array
{
    if (!isset($_FILES[$field]) || (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'upload' => null];
    }
    $upload = secure_upload_image($_FILES[$field], 'profiles');
    if (!$upload['ok']) {
        json_error($upload['error'] . " ({$field})", 422);
    }
    return ['path' => $upload['relative_path'], 'upload' => $upload];
}
