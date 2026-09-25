<?php
declare(strict_types=1);

function sanitize_social_link_url(mixed $raw): string
{
    $url = trim((string) $raw);
    if ($url === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $url) && !preg_match('#^mailto:#i', $url) && !preg_match('#^tel:#i', $url)) {
        $url = 'https://' . $url;
    }
    if (filter_var($url, FILTER_VALIDATE_URL) === false && !preg_match('#^(mailto:|tel:)#i', $url)) {
        throw new RuntimeException('Invalid social link URL');
    }
    if (strlen($url) > 500) {
        $url = substr($url, 0, 500);
    }
    return $url;
}

/**
 * @param array<int,array<string,mixed>> $assignments
 */
function sync_social_link_pages(PDO $pdo, int $socialLinkId, array $assignments, int $actorId): void
{
    $seen = [];
    foreach ($assignments as $a) {
        $pageId = (int) ($a['page_id'] ?? 0);
        if ($pageId < 1 || isset($seen[$pageId])) {
            continue;
        }
        $seen[$pageId] = true;
        $p = $pdo->prepare("SELECT id FROM pages WHERE id = :id AND page_type = 'website' AND deleted_at IS NULL");
        $p->execute([':id' => $pageId]);
        if (!$p->fetchColumn()) {
            throw new RuntimeException("Website page {$pageId} not found");
        }
        $isFeatured = to_bool_int($a['is_featured'] ?? false);
        $isActive = to_bool_int($a['is_active'] ?? true, 1);
        $sort = (int) ($a['sort_order'] ?? 0);

        $existing = $pdo->prepare(
            'SELECT id FROM social_link_pages WHERE social_link_id = :s AND page_id = :p AND deleted_at IS NULL LIMIT 1'
        );
        $existing->execute([':s' => $socialLinkId, ':p' => $pageId]);
        $exId = $existing->fetchColumn();
        if ($exId) {
            $pdo->prepare(
                'UPDATE social_link_pages SET is_featured = :f, is_active = :a, sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id'
            )->execute([':f' => $isFeatured, ':a' => $isActive, ':s' => $sort, ':u' => now_utc(), ':by' => $actorId, ':id' => $exId]);
            continue;
        }
        $old = $pdo->prepare(
            'SELECT id FROM social_link_pages WHERE social_link_id = :s AND page_id = :p AND deleted_at IS NOT NULL ORDER BY id DESC LIMIT 1'
        );
        $old->execute([':s' => $socialLinkId, ':p' => $pageId]);
        $oldId = $old->fetchColumn();
        if ($oldId) {
            restore_row($pdo, 'social_link_pages', (int) $oldId, $actorId);
            $pdo->prepare(
                'UPDATE social_link_pages SET is_featured = :f, is_active = :a, sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id'
            )->execute([':f' => $isFeatured, ':a' => $isActive, ':s' => $sort, ':u' => now_utc(), ':by' => $actorId, ':id' => $oldId]);
        } else {
            $pdo->prepare(
                'INSERT INTO social_link_pages (social_link_id, page_id, is_featured, is_active, sort_order, created_at, created_by)
                 VALUES (:s, :p, :f, :a, :sort, :c, :by)'
            )->execute([
                ':s' => $socialLinkId, ':p' => $pageId, ':f' => $isFeatured, ':a' => $isActive,
                ':sort' => $sort, ':c' => now_utc(), ':by' => $actorId,
            ]);
        }
    }

    $cur = $pdo->prepare('SELECT id, page_id FROM social_link_pages WHERE social_link_id = :s AND deleted_at IS NULL');
    $cur->execute([':s' => $socialLinkId]);
    foreach ($cur->fetchAll() ?: [] as $row) {
        if (!isset($seen[(int) $row['page_id']])) {
            soft_delete_row($pdo, 'social_link_pages', (int) $row['id'], $actorId);
        }
    }
}

/**
 * @param array<int,array<string,mixed>> $assignments
 */
function sync_contact_info_pages(PDO $pdo, int $contactInfoId, array $assignments, int $actorId): void
{
    $seen = [];
    foreach ($assignments as $a) {
        $pageId = (int) ($a['page_id'] ?? 0);
        if ($pageId < 1 || isset($seen[$pageId])) {
            continue;
        }
        $seen[$pageId] = true;
        $p = $pdo->prepare("SELECT id FROM pages WHERE id = :id AND page_type = 'website' AND deleted_at IS NULL");
        $p->execute([':id' => $pageId]);
        if (!$p->fetchColumn()) {
            throw new RuntimeException("Website page {$pageId} not found");
        }
        $isFeatured = to_bool_int($a['is_featured'] ?? false);
        $isActive = to_bool_int($a['is_active'] ?? true, 1);
        $sort = (int) ($a['sort_order'] ?? 0);

        $existing = $pdo->prepare(
            'SELECT id FROM contact_info_pages WHERE contact_info_id = :c AND page_id = :p AND deleted_at IS NULL LIMIT 1'
        );
        $existing->execute([':c' => $contactInfoId, ':p' => $pageId]);
        $exId = $existing->fetchColumn();
        if ($exId) {
            $pdo->prepare(
                'UPDATE contact_info_pages SET is_featured = :f, is_active = :a, sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id'
            )->execute([':f' => $isFeatured, ':a' => $isActive, ':s' => $sort, ':u' => now_utc(), ':by' => $actorId, ':id' => $exId]);
            continue;
        }
        $old = $pdo->prepare(
            'SELECT id FROM contact_info_pages WHERE contact_info_id = :c AND page_id = :p AND deleted_at IS NOT NULL ORDER BY id DESC LIMIT 1'
        );
        $old->execute([':c' => $contactInfoId, ':p' => $pageId]);
        $oldId = $old->fetchColumn();
        if ($oldId) {
            restore_row($pdo, 'contact_info_pages', (int) $oldId, $actorId);
            $pdo->prepare(
                'UPDATE contact_info_pages SET is_featured = :f, is_active = :a, sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id'
            )->execute([':f' => $isFeatured, ':a' => $isActive, ':s' => $sort, ':u' => now_utc(), ':by' => $actorId, ':id' => $oldId]);
        } else {
            $pdo->prepare(
                'INSERT INTO contact_info_pages (contact_info_id, page_id, is_featured, is_active, sort_order, created_at, created_by)
                 VALUES (:c, :p, :f, :a, :sort, :created, :by)'
            )->execute([
                ':c' => $contactInfoId, ':p' => $pageId, ':f' => $isFeatured, ':a' => $isActive,
                ':sort' => $sort, ':created' => now_utc(), ':by' => $actorId,
            ]);
        }
    }

    $cur = $pdo->prepare('SELECT id, page_id FROM contact_info_pages WHERE contact_info_id = :c AND deleted_at IS NULL');
    $cur->execute([':c' => $contactInfoId]);
    foreach ($cur->fetchAll() ?: [] as $row) {
        if (!isset($seen[(int) $row['page_id']])) {
            soft_delete_row($pdo, 'contact_info_pages', (int) $row['id'], $actorId);
        }
    }
}

/**
 * @param array<int,array<string,mixed>> $addresses
 */
function sync_contact_addresses(PDO $pdo, int $contactInfoId, array $addresses): void
{
    $pdo->prepare('DELETE FROM contact_addresses WHERE contact_info_id = :c')->execute([':c' => $contactInfoId]);
    $ins = $pdo->prepare(
        'INSERT INTO contact_addresses (contact_info_id, label, address_text, sort_order, created_at)
         VALUES (:c, :label, :addr, :sort, :created)'
    );
    $n = 10;
    foreach ($addresses as $row) {
        $text = trim((string) ($row['address_text'] ?? $row['address'] ?? ''));
        if ($text === '') {
            continue;
        }
        $label = trim((string) ($row['label'] ?? ''));
        $ins->execute([
            ':c' => $contactInfoId,
            ':label' => $label !== '' ? $label : null,
            ':addr' => mb_substr($text, 0, 500),
            ':sort' => isset($row['sort_order']) ? (int) $row['sort_order'] : $n,
            ':created' => now_utc(),
        ]);
        $n += 10;
    }
}

/**
 * @param array<int,array<string,mixed>> $phones
 */
function sync_contact_phones(PDO $pdo, int $contactInfoId, array $phones): void
{
    $pdo->prepare('DELETE FROM contact_phones WHERE contact_info_id = :c')->execute([':c' => $contactInfoId]);
    $ins = $pdo->prepare(
        'INSERT INTO contact_phones (contact_info_id, label, phone, sort_order, created_at)
         VALUES (:c, :label, :phone, :sort, :created)'
    );
    $n = 10;
    foreach ($phones as $row) {
        $phone = trim((string) ($row['phone'] ?? ''));
        if ($phone === '') {
            continue;
        }
        $label = trim((string) ($row['label'] ?? ''));
        $ins->execute([
            ':c' => $contactInfoId,
            ':label' => $label !== '' ? $label : null,
            ':phone' => mb_substr($phone, 0, 80),
            ':sort' => isset($row['sort_order']) ? (int) $row['sort_order'] : $n,
            ':created' => now_utc(),
        ]);
        $n += 10;
    }
}

function social_link_attach_display(array $row): array
{
    $row['id'] = (int) ($row['id'] ?? 0);
    $row['icon_class'] = sanitize_feature_icon_class($row['icon_class'] ?? 'fab fa-link');
    $row['icon_html'] = feature_icon_html($row['icon_class']);
    $row['is_visible'] = (int) ($row['is_visible'] ?? 1) === 1 ? 1 : 0;
    $row['is_active'] = (int) ($row['is_active'] ?? 1) === 1 ? 1 : 0;
    $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
    return $row;
}

function contact_info_load_children(PDO $pdo, int $contactInfoId): array
{
    $addr = $pdo->prepare(
        'SELECT id, label, address_text, sort_order FROM contact_addresses
         WHERE contact_info_id = :c ORDER BY sort_order ASC, id ASC'
    );
    $addr->execute([':c' => $contactInfoId]);
    $addresses = $addr->fetchAll() ?: [];
    foreach ($addresses as &$a) {
        $a['id'] = (int) $a['id'];
        $a['sort_order'] = (int) $a['sort_order'];
    }
    unset($a);

    $ph = $pdo->prepare(
        'SELECT id, label, phone, sort_order FROM contact_phones
         WHERE contact_info_id = :c ORDER BY sort_order ASC, id ASC'
    );
    $ph->execute([':c' => $contactInfoId]);
    $phones = $ph->fetchAll() ?: [];
    foreach ($phones as &$p) {
        $p['id'] = (int) $p['id'];
        $p['sort_order'] = (int) $p['sort_order'];
    }
    unset($p);

    return ['addresses' => $addresses, 'phones' => $phones];
}
