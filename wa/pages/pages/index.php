<?php
declare(strict_types=1);

wa_require_perm('pages.view');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$pageType = str_contains($path, 'admin-pages') ? 'admin' : 'website';
$listPath = $pageType === 'admin' ? '/admin-pages' : '/website-pages';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && wa_can('pages.delete')) {
        $res = wa_api('POST', '/api/pages/delete', ['id' => $id]);
        wa_flash(wa_api_ok($res) ? 'success' : 'error', (string) ($res['message'] ?? 'Done'));
    } elseif ($action === 'toggle' && wa_can('pages.edit')) {
        $view = wa_api('GET', '/api/pages/view', ['id' => $id]);
        if (wa_api_ok($view)) {
            $p = $view['data'];
            $res = wa_api('POST', '/api/pages/update', [
                'id' => $id,
                'title' => $p['title'],
                'slug' => $p['slug'],
                'template_key' => $p['template_key'],
                'is_active' => ((int)$p['is_active'] === 1) ? 0 : 1,
            ]);
            wa_flash(wa_api_ok($res) ? 'success' : 'error', (string) ($res['message'] ?? 'Done'));
        }
    }
    wa_redirect($listPath);
}

$q = trim((string) ($_GET['q'] ?? ''));
$res = wa_api('GET', '/api/pages', ['page_type' => $pageType, 'q' => $q, 'page' => (int)($_GET['page'] ?? 1)]);
$items = wa_api_ok($res) ? ($res['data']['items'] ?? []) : [];
$pagination = wa_api_ok($res) ? ($res['data']['pagination'] ?? []) : [];

ob_start();
?>
<div class="page-head">
  <div><h1><?= $pageType === 'admin' ? 'Admin pages' : 'Website pages' ?></h1><p>Template keys are whitelisted — not executed as paths.</p></div>
  <?php if (wa_can('pages.create')): ?>
    <a class="btn" href="<?= wa_e(wa_url('/pages/create?page_type=' . $pageType)) ?>">Create page</a>
  <?php endif; ?>
</div>
<div class="panel">
  <form class="toolbar" method="get"><input type="search" name="q" value="<?= wa_e($q) ?>"><button class="btn btn-secondary" type="submit">Search</button></form>
  <table class="data">
    <thead><tr><th>Title</th><th>Slug</th><th>Template</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $row): ?>
      <tr>
        <td><?= wa_e($row['title']) ?></td>
        <td><?= wa_e($row['slug']) ?></td>
        <td><?= wa_e($row['template_key']) ?></td>
        <td><?= wa_badge((int)$row['is_active']) ?></td>
        <td>
          <a href="<?= wa_e(wa_url('/pages/view?id='.$row['id'])) ?>">View</a>
          <?php if (wa_can('pages.edit')): ?> · <a href="<?= wa_e(wa_url('/pages/edit?id='.$row['id'])) ?>">Edit</a>
            · <form class="confirm-form" method="post"><?= wa_csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-ghost" style="padding:0.2rem 0.5rem" type="submit"><?= (int)$row['is_active']===1?'Deactivate':'Activate' ?></button></form>
          <?php endif; ?>
          <?php if (wa_can('pages.delete')): ?>
            · <form class="confirm-form" method="post"><?= wa_csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-danger" style="padding:0.2rem 0.5rem" type="submit" data-confirm="Soft-delete this page?">Delete</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?= wa_pagination($pagination, $listPath, ['q' => $q]) ?>
</div>
<?php
wa_render($pageType === 'admin' ? 'Admin pages' : 'Website pages', ob_get_clean());
