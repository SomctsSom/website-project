<?php
declare(strict_types=1);

require_method('GET');

$input = request_input();
$pageId = isset($input['page_id']) ? (int) $input['page_id'] : 0;
$slug = trim((string) ($input['slug'] ?? ''));

$pdo = db();
if ($pageId < 1 && $slug !== '') {
    $s = $pdo->prepare(
        "SELECT id FROM pages WHERE page_type = 'website' AND slug = :slug AND deleted_at IS NULL AND is_active = 1 LIMIT 1"
    );
    $s->execute([':slug' => $slug]);
    $pageId = (int) ($s->fetchColumn() ?: 0);
}
if ($pageId < 1) {
    json_error('page_id or slug is required', 422);
}

$p = $pdo->prepare(
    "SELECT id, title, slug FROM pages
     WHERE id = :id AND page_type = 'website' AND deleted_at IS NULL AND is_active = 1"
);
$p->execute([':id' => $pageId]);
$page = $p->fetch();
if (!$page) {
    json_error('Page not found', 404);
}

$socialStmt = $pdo->prepare(
    "SELECT sl.id, sl.icon_class, sl.link_url, sl.label, sl.sort_order AS link_sort,
            slp.is_featured, slp.sort_order AS page_sort
     FROM social_link_pages slp
     JOIN social_links sl ON sl.id = slp.social_link_id
     WHERE slp.page_id = :page_id
       AND slp.deleted_at IS NULL AND slp.is_active = 1
       AND sl.deleted_at IS NULL AND sl.is_active = 1 AND sl.is_visible = 1
     ORDER BY slp.is_featured DESC, slp.sort_order ASC, sl.sort_order ASC, sl.id ASC"
);
$socialStmt->execute([':page_id' => $pageId]);
$social = $socialStmt->fetchAll() ?: [];
foreach ($social as &$row) {
    $row = social_link_attach_display($row);
}
unset($row);

$contactStmt = $pdo->prepare(
    "SELECT c.id, c.company_summary, c.company_name, c.tagline, c.email, c.business_hours, c.logo_path, c.sort_order AS contact_sort,
            cip.is_featured, cip.sort_order AS page_sort
     FROM contact_info_pages cip
     JOIN contact_infos c ON c.id = cip.contact_info_id
     WHERE cip.page_id = :page_id
       AND cip.deleted_at IS NULL AND cip.is_active = 1
       AND c.deleted_at IS NULL AND c.is_active = 1
     ORDER BY cip.is_featured DESC, cip.sort_order ASC, c.sort_order ASC, c.id ASC
     LIMIT 1"
);
$contactStmt->execute([':page_id' => $pageId]);
$contact = $contactStmt->fetch() ?: null;
if ($contact) {
    $contact['id'] = (int) $contact['id'];
    $contact['is_featured'] = (int) $contact['is_featured'];
    $contact['logo_url'] = !empty($contact['logo_path'])
        ? secure_public_media_url((string) $contact['logo_path'])
        : null;
    unset($contact['logo_path']);
    $children = contact_info_load_children($pdo, (int) $contact['id']);
    $contact['addresses'] = $children['addresses'];
    $contact['phones'] = $children['phones'];
}

json_ok([
    'page' => [
        'id' => (int) $page['id'],
        'title' => $page['title'],
        'slug' => $page['slug'],
    ],
    'social' => $social,
    'contact' => $contact,
]);
