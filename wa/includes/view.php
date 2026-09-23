<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function wa_render(string $title, string $content, array $options = []): never
{
    $user = wa_user();
    $nav = [];
    if ($user) {
        $navRes = wa_api('GET', '/api/admin/navigation');
        if (wa_api_ok($navRes)) {
            $nav = $navRes['data']['items'] ?? [];
            if (!empty($navRes['data']['user'])) {
                $_SESSION['user'] = array_merge($user, [
                    'permissions' => $navRes['data']['user']['permissions'] ?? ($user['permissions'] ?? []),
                    'csrf_token' => $navRes['data']['user']['csrf_token'] ?? ($_SESSION['csrf_token'] ?? ''),
                ]);
                $_SESSION['csrf_token'] = $navRes['data']['user']['csrf_token'] ?? $_SESSION['csrf_token'];
                $user = $_SESSION['user'];
            }
        }
    }
    $flashes = wa_consume_flash();
    $appName = wa_e((string) wa_env('APP_NAME', 'Website Admin'));
    $pageTitle = wa_e($title);
    include __DIR__ . '/layout.php';
    exit;
}

function wa_badge(bool|int $active, string $on = 'Active', string $off = 'Inactive'): string
{
    $ok = (int) $active === 1;
    $cls = $ok ? 'badge badge-ok' : 'badge badge-off';
    return '<span class="' . $cls . '">' . wa_e($ok ? $on : $off) . '</span>';
}

function wa_pagination(array $pagination, string $basePath, array $query = []): string
{
    $page = (int) ($pagination['page'] ?? 1);
    $total = (int) ($pagination['total_pages'] ?? 1);
    if ($total <= 1) {
        return '';
    }
    $html = '<nav class="pagination">';
    for ($i = 1; $i <= $total; $i++) {
        $q = http_build_query(array_merge($query, ['page' => $i]));
        $cls = $i === $page ? ' class="current"' : '';
        $html .= '<a' . $cls . ' href="' . wa_e(wa_url($basePath) . '?' . $q) . '">' . $i . '</a>';
    }
    $html .= '</nav>';
    return $html;
}
