<?php
declare(strict_types=1);

/**
 * @return array{
 *   enabled:int,
 *   transparent:int,
 *   show_phone:int,
 *   show_email:int,
 *   show_hours:int,
 *   show_social:int,
 *   bg_color:string,
 *   text_color:string,
 *   accent_color:string,
 *   social_color:string
 * }
 */
function header_bar_defaults(): array
{
    return [
        'enabled' => 1,
        'transparent' => 0,
        'show_phone' => 1,
        'show_email' => 1,
        'show_hours' => 1,
        'show_social' => 1,
        'bg_color' => '#1a1a1a',
        'text_color' => '#ffffff',
        'accent_color' => '#e0893a',
        'social_color' => '#ffffff',
    ];
}

function header_bar_bool($value, int $default = 1): int
{
    if ($value === null || $value === '') {
        return $default;
    }
    if (is_bool($value)) {
        return $value ? 1 : 0;
    }
    $v = strtolower(trim((string) $value));
    if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
        return 1;
    }
    if (in_array($v, ['0', 'false', 'no', 'off'], true)) {
        return 0;
    }
    return ((int) $value) === 1 ? 1 : 0;
}

/**
 * @return array{
 *   enabled:int,
 *   transparent:int,
 *   show_phone:int,
 *   show_email:int,
 *   show_hours:int,
 *   show_social:int,
 *   bg_color:string,
 *   text_color:string,
 *   accent_color:string,
 *   social_color:string
 * }
 */
function get_header_bar_settings(?PDO $pdo = null): array
{
    $defaults = header_bar_defaults();
    $pdo = $pdo ?? db();
    try {
        $stmt = $pdo->query(
            "SELECT setting_key, setting_value FROM site_settings
             WHERE setting_key IN (
                'header_bar_enabled','header_bar_transparent',
                'header_bar_show_phone','header_bar_show_email',
                'header_bar_show_hours','header_bar_show_social',
                'header_bar_bg_color','header_bar_text_color',
                'header_bar_accent_color','header_bar_social_color'
             )"
        );
        $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: []) : [];
        if (isset($rows['header_bar_enabled'])) {
            $defaults['enabled'] = header_bar_bool($rows['header_bar_enabled'], 1);
        }
        if (isset($rows['header_bar_transparent'])) {
            $defaults['transparent'] = header_bar_bool($rows['header_bar_transparent'], 0);
        }
        if (isset($rows['header_bar_show_phone'])) {
            $defaults['show_phone'] = header_bar_bool($rows['header_bar_show_phone'], 1);
        }
        if (isset($rows['header_bar_show_email'])) {
            $defaults['show_email'] = header_bar_bool($rows['header_bar_show_email'], 1);
        }
        if (isset($rows['header_bar_show_hours'])) {
            $defaults['show_hours'] = header_bar_bool($rows['header_bar_show_hours'], 1);
        }
        if (isset($rows['header_bar_show_social'])) {
            $defaults['show_social'] = header_bar_bool($rows['header_bar_show_social'], 1);
        }
        if (isset($rows['header_bar_bg_color'])) {
            $defaults['bg_color'] = sanitize_navbar_hex($rows['header_bar_bg_color'], $defaults['bg_color']);
        }
        if (isset($rows['header_bar_text_color'])) {
            $defaults['text_color'] = sanitize_navbar_hex($rows['header_bar_text_color'], $defaults['text_color']);
        }
        if (isset($rows['header_bar_accent_color'])) {
            $defaults['accent_color'] = sanitize_navbar_hex($rows['header_bar_accent_color'], $defaults['accent_color']);
        }
        if (isset($rows['header_bar_social_color'])) {
            $defaults['social_color'] = sanitize_navbar_hex($rows['header_bar_social_color'], $defaults['social_color']);
        }
    } catch (Throwable $e) {
        // Table may not exist yet
    }
    return $defaults;
}

/**
 * @param array<string,mixed> $input
 * @return array{
 *   enabled:int,
 *   transparent:int,
 *   show_phone:int,
 *   show_email:int,
 *   show_hours:int,
 *   show_social:int,
 *   bg_color:string,
 *   text_color:string,
 *   accent_color:string,
 *   social_color:string
 * }
 */
function set_header_bar_settings(PDO $pdo, array $input, ?int $actorId = null): array
{
    $defaults = header_bar_defaults();
    $current = get_header_bar_settings($pdo);

    $enabled = array_key_exists('enabled', $input)
        ? header_bar_bool($input['enabled'], 1)
        : $current['enabled'];
    $transparent = array_key_exists('transparent', $input)
        ? header_bar_bool($input['transparent'], 0)
        : $current['transparent'];
    $showPhone = array_key_exists('show_phone', $input)
        ? header_bar_bool($input['show_phone'], 1)
        : $current['show_phone'];
    $showEmail = array_key_exists('show_email', $input)
        ? header_bar_bool($input['show_email'], 1)
        : $current['show_email'];
    $showHours = array_key_exists('show_hours', $input)
        ? header_bar_bool($input['show_hours'], 1)
        : $current['show_hours'];
    $showSocial = array_key_exists('show_social', $input)
        ? header_bar_bool($input['show_social'], 1)
        : $current['show_social'];

    $bg = sanitize_navbar_hex($input['bg_color'] ?? $current['bg_color'], $defaults['bg_color']);
    $text = sanitize_navbar_hex($input['text_color'] ?? $current['text_color'], $defaults['text_color']);
    $accent = sanitize_navbar_hex($input['accent_color'] ?? $current['accent_color'], $defaults['accent_color']);
    $social = sanitize_navbar_hex($input['social_color'] ?? $current['social_color'], $defaults['social_color']);

    $map = [
        'header_bar_enabled' => (string) $enabled,
        'header_bar_transparent' => (string) $transparent,
        'header_bar_show_phone' => (string) $showPhone,
        'header_bar_show_email' => (string) $showEmail,
        'header_bar_show_hours' => (string) $showHours,
        'header_bar_show_social' => (string) $showSocial,
        'header_bar_bg_color' => $bg,
        'header_bar_text_color' => $text,
        'header_bar_accent_color' => $accent,
        'header_bar_social_color' => $social,
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO site_settings (setting_key, setting_value, updated_at, updated_by)
         VALUES (:k, :v, :u, :by)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value),
           updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)"
    );
    foreach ($map as $key => $value) {
        $stmt->execute([
            ':k' => $key,
            ':v' => $value,
            ':u' => now_utc(),
            ':by' => $actorId,
        ]);
    }

    return [
        'enabled' => $enabled,
        'transparent' => $transparent,
        'show_phone' => $showPhone,
        'show_email' => $showEmail,
        'show_hours' => $showHours,
        'show_social' => $showSocial,
        'bg_color' => $bg,
        'text_color' => $text,
        'accent_color' => $accent,
        'social_color' => $social,
    ];
}
