<?php
declare(strict_types=1);

wa_require_perm('menus.view');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$menuType = str_contains($path, 'admin-menus') ? 'admin' : 'website';
$listPath = $menuType === 'admin' ? '/admin-menus' : '/website-menus';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && wa_can('menus.delete')) {
        $res = wa_api('POST', '/api/menus/delete', ['id' => $id]);
        wa_flash(wa_api_ok($res) ? 'success' : 'error', (string) ($res['message'] ?? 'Done'));
    } elseif ($action === 'toggle' && wa_can('menus.edit')) {
        $view = wa_api('GET', '/api/menus/view', ['id' => $id]);
        if (wa_api_ok($view)) {
            $m = $view['data'];
            $res = wa_api('POST', '/api/menus/update', [
                'id' => $id,
                'title' => $m['title'],
                'is_active' => ((int)$m['is_active'] === 1) ? 0 : 1,
                'parent_menu_id' => $m['parent_menu_id'],
                'url' => $m['url'],
                'icon' => $m['icon'],
                'sort_order' => $m['sort_order'],
            ]);
            wa_flash(wa_api_ok($res) ? 'success' : 'error', (string) ($res['message'] ?? 'Done'));
        }
    }
    wa_redirect($listPath);
}

$res = wa_api('GET', '/api/menus', ['menu_type' => $menuType, 'tree' => 1]);
$items = wa_api_ok($res) ? ($res['data']['items'] ?? []) : [];

function wa_render_menu_rows(array $items, string $listPath, int $depth = 0): void
{
    foreach ($items as $row) {
        $pad = str_repeat('— ', $depth);
        echo '<tr>';
        echo '<td>' . wa_e($pad . $row['title']) . '</td>';
        echo '<td>' . wa_e((string) ($row['url'] ?? '')) . '</td>';
        echo '<td>' . (int) $row['sort_order'] . '</td>';
        echo '<td>' . wa_badge((int) $row['is_active']) . '</td>';
        echo '<td>';
        echo '<a href="' . wa_e(wa_url('/menus/view?id=' . $row['id'])) . '">View</a>';
        if (wa_can('menus.edit')) {
            echo ' · <a href="' . wa_e(wa_url('/menus/edit?id=' . $row['id'])) . '">Edit</a>';
            echo ' · <form class="confirm-form" method="post">' . wa_csrf_field() . '<input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="' . (int)$row['id'] . '"><button class="btn btn-ghost" style="padding:0.2rem 0.5rem" type="submit">' . ((int)$row['is_active']===1?'Deactivate':'Activate') . '</button></form>';
        }
        if (wa_can('menus.delete')) {
            echo ' · <form class="confirm-form" method="post">' . wa_csrf_field() . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . (int)$row['id'] . '"><button class="btn btn-danger" style="padding:0.2rem 0.5rem" type="submit" data-confirm="Soft-delete this menu and its children?">Delete</button></form>';
        }
        echo '</td></tr>';
        if (!empty($row['children'])) {
            wa_render_menu_rows($row['children'], $listPath, $depth + 1);
        }
    }
}

ob_start();
?>
<div class="page-head">
  <div><h1><?= $menuType === 'admin' ? 'Admin menus' : 'Website menus' ?></h1><p>Nested navigation with soft delete and reorder support.</p></div>
  <?php if (wa_can('menus.create')): ?>
    <a class="btn" href="<?= wa_e(wa_url('/menus/create?menu_type=' . $menuType)) ?>">Create menu</a>
  <?php endif; ?>
</div>
<div class="panel">
  <table class="data">
    <thead><tr><th>Title</th><th>URL</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <?php wa_render_menu_rows($items, $listPath); ?>
    </tbody>
  </table>
</div>
<?php
wa_render($menuType === 'admin' ? 'Admin menus' : 'Website menus', ob_get_clean());
