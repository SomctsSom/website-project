<?php
declare(strict_types=1);

wa_require_perm('profile_overviews.view');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && wa_can('profile_overviews.delete')) {
        $res = wa_api('POST', '/api/profile-overviews/delete', ['id' => $id]);
        wa_flash(wa_api_ok($res) ? 'success' : 'error', (string) ($res['message'] ?? 'Done'));
    } elseif ($action === 'toggle' && wa_can('profile_overviews.edit')) {
        $view = wa_api('GET', '/api/profile-overviews/view', ['id' => $id]);
        if (wa_api_ok($view)) {
            $p = $view['data'];
            $pagesJson = json_encode(array_map(static function ($row) {
                return [
                    'page_id' => (int) $row['page_id'],
                    'is_featured' => (int) $row['is_featured'],
                    'is_active' => (int) $row['is_active'],
                    'sort_order' => (int) $row['sort_order'],
                ];
            }, $p['pages'] ?? []), JSON_UNESCAPED_UNICODE);
            $res = wa_api_multipart('/api/profile-overviews/update', [
                'id' => (string) $id,
                'title' => (string) $p['title'],
                'body' => (string) ($p['body'] ?? ''),
                'sort_order' => (string) $p['sort_order'],
                'is_active' => ((int) $p['is_active'] === 1) ? '0' : '1',
                'pages_json' => (string) $pagesJson,
            ], []);
            wa_flash(wa_api_ok($res) ? 'success' : 'error', (string) ($res['message'] ?? 'Done'));
        }
    }
    wa_redirect('/profile-overviews?' . http_build_query(array_filter([
        'q' => $_GET['q'] ?? null,
        'page_id' => $_GET['page_id'] ?? null,
        'featured' => $_GET['featured'] ?? null,
        'status' => $_GET['status'] ?? null,
    ], static fn($v) => $v !== null && $v !== '')));
}

$q = trim((string) ($_GET['q'] ?? ''));
$pageId = (string) ($_GET['page_id'] ?? '');
$featured = (string) ($_GET['featured'] ?? '');
$status = (string) ($_GET['status'] ?? '');
$res = wa_api('GET', '/api/profile-overviews', [
    'q' => $q, 'page_id' => $pageId, 'featured' => $featured, 'status' => $status,
    'page' => (int) ($_GET['page'] ?? 1),
]);
$items = wa_api_ok($res) ? ($res['data']['items'] ?? []) : [];
$pagination = wa_api_ok($res) ? ($res['data']['pagination'] ?? []) : [];
$pagesRes = wa_api('GET', '/api/pages', ['page_type' => 'website', 'per_page' => 100]);
$pages = wa_api_ok($pagesRes) ? ($pagesRes['data']['items'] ?? []) : [];

ob_start();
?>
<div class="page-head">
  <div><h1>Profile Overviews</h1><p>Text + 3-image collage sections. Assign to one or many website pages.</p></div>
  <?php if (wa_can('profile_overviews.create')): ?><a class="btn" href="<?= wa_e(wa_url('/profile-overviews/create')) ?>">Create profile overview</a><?php endif; ?>
</div>
<div class="panel">
  <form class="toolbar" method="get">
    <input type="search" name="q" value="<?= wa_e($q) ?>" placeholder="Search">
    <select name="page_id">
      <option value="">All pages</option>
      <?php foreach ($pages as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= $pageId === (string)$p['id'] ? 'selected' : '' ?>><?= wa_e($p['title']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="featured">
      <option value="">Featured: any</option>
      <option value="1" <?= $featured==='1'?'selected':'' ?>>Featured</option>
      <option value="0" <?= $featured==='0'?'selected':'' ?>>Not featured</option>
    </select>
    <select name="status">
      <option value="">Status: any</option>
      <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
      <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
    </select>
    <button class="btn btn-secondary" type="submit">Filter</button>
  </form>
  <table class="data">
    <thead><tr><th>Preview</th><th>Title</th><th>Status</th><th>Assigned pages</th><th>#</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $row): ?>
      <tr>
        <td><?php if (!empty($row['image_top_url'])): ?><img class="thumb" src="<?= wa_e($row['image_top_url']) ?>" alt=""><?php endif; ?></td>
        <td><?= wa_e($row['title']) ?></td>
        <td><?= wa_badge((int)$row['is_active']) ?></td>
        <td>
          <?php foreach ($row['pages'] ?? [] as $p): ?>
            <div><?= wa_e($p['title']) ?> <?= (int)$p['is_featured']===1?'★':'' ?> <?= (int)$p['is_active']===1?'':'(inactive)' ?></div>
          <?php endforeach; ?>
        </td>
        <td><?= (int)($row['page_count'] ?? 0) ?></td>
        <td>
          <a href="<?= wa_e(wa_url('/profile-overviews/view?id='.$row['id'])) ?>">View</a>
          <?php if (wa_can('profile_overviews.edit')): ?> · <a href="<?= wa_e(wa_url('/profile-overviews/edit?id='.$row['id'])) ?>">Edit</a>
            · <form class="confirm-form" method="post"><?= wa_csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-ghost" style="padding:0.2rem 0.5rem" type="submit"><?= (int)$row['is_active']===1?'Deactivate':'Activate' ?></button></form>
          <?php endif; ?>
          <?php if (wa_can('profile_overviews.delete')): ?>
            · <form class="confirm-form" method="post"><?= wa_csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-danger" style="padding:0.2rem 0.5rem" type="submit" data-confirm="Soft-delete this profile overview?">Delete</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?= wa_pagination($pagination, '/profile-overviews', ['q'=>$q,'page_id'=>$pageId,'featured'=>$featured,'status'=>$status]) ?>
</div>
<?php
wa_render('Profile Overviews', ob_get_clean());
