<?php
declare(strict_types=1);

/**
 * @return array{transparent:int,bg_color:string,menu_color:string,active_color:string,page_bg_color:string}
 */
function navbar_color_defaults(): array
{
    return [
        'transparent' => 0,
        'bg_color' => '#12352e',
        'menu_color' => '#ffffff',
        'active_color' => '#e0893a',
        'page_bg_color' => '#f2ebe0',
    ];
}

function sanitize_navbar_hex(mixed $value, string $fallback): string
{
    $value = trim((string) $value);
    if (preg_match('/^#([0-9a-fA-F]{6})$/', $value)) {
        return strtolower($value);
    }
    if (preg_match('/^#([0-9a-fA-F]{3})$/', $value, $m)) {
        $h = $m[1];
        return '#' . strtolower($h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2]);
    }
    if (preg_match('/^([0-9a-fA-F]{6})$/', $value)) {
        return '#' . strtolower($value);
    }
    return $fallback;
}

/**
 * @return array{transparent:int,bg_color:string,menu_color:string,active_color:string,page_bg_color:string}
 */
function get_navbar_colors(?PDO $pdo = null): array
{
    $defaults = navbar_color_defaults();
    $pdo = $pdo ?? db();
    try {
        $stmt = $pdo->query(
            "SELECT setting_key, setting_value FROM site_settings
             WHERE setting_key IN (
                'navbar_transparent','navbar_bg_color','navbar_menu_color','navbar_active_color','page_bg_color'
             )"
        );
        $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: []) : [];
        if (isset($rows['navbar_transparent'])) {
            $defaults['transparent'] = ((string) $rows['navbar_transparent'] === '1') ? 1 : 0;
        }
        if (isset($rows['navbar_bg_color'])) {
            $defaults['bg_color'] = sanitize_navbar_hex($rows['navbar_bg_color'], $defaults['bg_color']);
        }
        if (isset($rows['navbar_menu_color'])) {
            $defaults['menu_color'] = sanitize_navbar_hex($rows['navbar_menu_color'], $defaults['menu_color']);
        }
        if (isset($rows['navbar_active_color'])) {
            $defaults['active_color'] = sanitize_navbar_hex($rows['navbar_active_color'], $defaults['active_color']);
        }
        if (isset($rows['page_bg_color'])) {
            $defaults['page_bg_color'] = sanitize_navbar_hex($rows['page_bg_color'], $defaults['page_bg_color']);
        }
    } catch (Throwable $e) {
        // Table may not exist yet
    }
    return $defaults;
}

/**
 * @param array<string,mixed> $input
 * @return array{transparent:int,bg_color:string,menu_color:string,active_color:string,page_bg_color:string}
 */
function set_navbar_colors(PDO $pdo, array $input, ?int $actorId = null): array
{
    $defaults = navbar_color_defaults();
    $current = get_navbar_colors($pdo);
    $transparent = array_key_exists('transparent', $input)
        ? to_bool_int($input['transparent'], $current['transparent'])
        : $current['transparent'];
    $bg = sanitize_navbar_hex($input['bg_color'] ?? $current['bg_color'], $defaults['bg_color']);
    $menu = sanitize_navbar_hex($input['menu_color'] ?? $current['menu_color'], $defaults['menu_color']);
    $active = sanitize_navbar_hex($input['active_color'] ?? $current['active_color'], $defaults['active_color']);
    $pageBg = sanitize_navbar_hex($input['page_bg_color'] ?? $current['page_bg_color'], $defaults['page_bg_color']);

    $map = [
        'navbar_transparent' => (string) $transparent,
        'navbar_bg_color' => $bg,
        'navbar_menu_color' => $menu,
        'navbar_active_color' => $active,
        'page_bg_color' => $pageBg,
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
        'transparent' => $transparent,
        'bg_color' => $bg,
        'menu_color' => $menu,
        'active_color' => $active,
        'page_bg_color' => $pageBg,
    ];
}
