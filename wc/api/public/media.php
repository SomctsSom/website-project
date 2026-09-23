<?php
declare(strict_types=1);

/**
 * Serve uploaded media safely. Path comes from /media/{subdir}/{file}.
 */
$relative = $m[1] ?? '';
$full = secure_resolve_upload_path($relative);
if ($full === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($full) ?: 'application/octet-stream';
if (!str_starts_with($mime, 'image/')) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($full));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');
readfile($full);
exit;
