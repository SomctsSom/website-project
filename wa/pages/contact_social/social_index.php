<?php
declare(strict_types=1);

wa_require_perm('contact_social.view');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && wa_can('contact_social.delete')) {
        $res = wa_api('POST', '/api/social-links/delete', ['id' => $id]);
        wa_flash(wa_api_ok($res) ? 'success' : 'error', $res['message'] ?? 'Done');
    } elseif ($action === 'toggle' && wa_can('contact_social.edit')) {
        $view = wa_api('GET', '/api/social-links/view', ['id' => $id]);
        if (wa_api_ok($view)) {
            $row = $view['data'];
            $res = wa_api('POST', '/api/social-links/update', [
                'id' => $id,
                'icon_class' => $row['icon_class'],
                'link_url' => $row['link_url'],
                'label' => $row['label'] ?? '',
                'sort_order' => $row['sort_order'],
                'is_visible' => (int) ($row['is_visible'] ?? 1) === 1 ? 0 : 1,
                'is_active' => $row['is_active'],
                'pages_json' => json_encode($row['pages'] ?? [], JSON_UNESCAPED_UNICODE),
            ]);
            wa_flash(wa_api_ok($res) ? 'success' : 'error', $res['message'] ?? 'Updated');
        } else {
            wa_flash('error', $view['message'] ?? 'Not found');
        }
    }
    wa_redirect('/contact-social/social');
}

$q = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$res = wa_api('GET', '/api/social-links', array_filter([
    'q' => $q,
    'status' => $status,
    'page' => $page,
    'per_page' => 50,
], static fn($v) => $v !== '' && $v !== null));
$items = wa_api_ok($res) ? ($res['data']['items'] ?? []) : [];
$pagination = wa_api_ok($res) ? ($res['data']['pagination'] ?? []) : [];
$listError = wa_api_ok($res) ? '' : (string) ($res['message'] ?? 'Failed to load social links');

ob_start();
?>
<div class="page-head">
  <div>
    <h1>Social media links</h1>
    <p>Icon, link, and visibility. Edit or remove any link below.</p>
  </div>
  <div style="display:flex;gap:0.5rem">
    <a class="btn btn-ghost" href="<?= wa_e(wa_url('/contact-social')) ?>">Back</a>
    <?php if (wa_can('contact_social.create')): ?>
      <a class="btn" href="<?= wa_e(wa_url('/contact-social/social/create')) ?>">Add links</a>
    <?php endif; ?>
  </div>
</div>
<?php if ($listError !== ''): ?>
  <div class="alert alert-error"><?= wa_e($listError) ?></div>
<?php endif; ?>
<form class="filters" method="get">
  <input name="q" value="<?= wa_e($q) ?>" placeholder="Search label or URL">
  <select name="status">
    <option value="">All</option>
    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
  </select>
  <button class="btn btn-ghost" type="submit">Filter</button>
</form>
<div class="panel table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th>Icon</th>
        <th>Label</th>
        <th>Link</th>
        <th>Visible</th>
        <th>Order</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$items): ?>
        <tr><td colspan="6" class="muted">No social links yet.<?= wa_can('contact_social.create') ? ' Use “Add links” to create some.' : '' ?></td></tr>
      <?php endif; ?>
      <?php foreach ($items as $row): ?>
        <tr>
          <td style="font-size:1.25rem;width:3rem"><?= (string) ($row['icon_html'] ?? '') ?></td>
          <td><?= wa_e((string) ($row['label'] ?? '—')) ?></td>
          <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <a href="<?= wa_e((string) $row['link_url']) ?>" target="_blank" rel="noopener"><?= wa_e((string) $row['link_url']) ?></a>
          </td>
          <td>
            <span class="badge <?= (int) ($row['is_visible'] ?? 0) === 1 ? 'badge-ok' : 'badge-off' ?>">
              <?= (int) ($row['is_visible'] ?? 0) === 1 ? 'Visible' : 'Hidden' ?>
            </span>
          </td>
          <td><?= (int) ($row['sort_order'] ?? 0) ?></td>
          <td class="row-actions" style="white-space:nowrap">
            <?php if (wa_can('contact_social.edit')): ?>
              <a class="btn btn-ghost" style="padding:0.25rem 0.55rem" href="<?= wa_e(wa_url('/contact-social/social/edit?id=' . (int) $row['id'])) ?>">Edit</a>
              <form method="post" style="display:inline"><?= wa_csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <button class="btn btn-ghost" style="padding:0.25rem 0.55rem" type="submit">
                  <?= (int) ($row['is_visible'] ?? 0) === 1 ? 'Hide' : 'Show' ?>
                </button>
              </form>
            <?php endif; ?>
            <?php if (wa_can('contact_social.delete')): ?>
              <form method="post" style="display:inline" onsubmit="return confirm('Remove this social link?')"><?= wa_csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <button class="btn btn-danger" style="padding:0.25rem 0.55rem" type="submit">Remove</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?= wa_pagination($pagination, '/contact-social/social', ['q' => $q, 'status' => $status]) ?>
</div>
<?php
wa_render('Social media', ob_get_clean());
