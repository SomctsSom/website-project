<?php
declare(strict_types=1);

wa_require_perm('trash.view');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && wa_can('trash.restore')) {
    $res = wa_api('POST', '/api/trash/restore', [
        'module' => $_POST['module'] ?? '',
        'id' => (int) ($_POST['id'] ?? 0),
    ]);
    wa_flash(wa_api_ok($res) ? 'success' : 'error', (string) ($res['message'] ?? 'Done'));
    wa_redirect('/trash?module=' . urlencode((string) ($_POST['module'] ?? 'users')));
}

$module = (string) ($_GET['module'] ?? 'users');
$q = trim((string) ($_GET['q'] ?? ''));
$res = wa_api('GET', '/api/trash', ['module' => $module, 'q' => $q, 'page' => (int) ($_GET['page'] ?? 1)]);
$items = wa_api_ok($res) ? ($res['data']['items'] ?? []) : [];
$modules = wa_api_ok($res) ? ($res['data']['modules'] ?? []) : [];
$pagination = wa_api_ok($res) ? ($res['data']['pagination'] ?? []) : [];

ob_start();
?>
<div class="page-head"><div><h1>Trash</h1><p>Soft-deleted records. Restore only — no permanent delete.</p></div></div>
<div class="panel">
  <form class="toolbar" method="get">
    <select name="module">
      <?php foreach ($modules as $m): ?>
        <option value="<?= wa_e($m) ?>" <?= $module === $m ? 'selected' : '' ?>><?= wa_e(ucfirst($m)) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="search" name="q" value="<?= wa_e($q) ?>">
    <button class="btn btn-secondary" type="submit">Filter</button>
  </form>
  <table class="data">
    <thead><tr><th>ID</th><th>Label</th><th>Extra</th><th>Deleted</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $row): ?>
      <tr>
        <td><?= (int)$row['id'] ?></td>
        <td><?= wa_e((string)$row['label']) ?></td>
        <td><?= wa_e((string)$row['extra']) ?></td>
        <td><?= wa_e($row['deleted_at_display'] ?? $row['deleted_at']) ?></td>
        <td>
          <?php if (wa_can('trash.restore')): ?>
            <form method="post" class="confirm-form"><?= wa_csrf_field() ?>
              <input type="hidden" name="module" value="<?= wa_e($module) ?>">
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <button class="btn" type="submit" data-confirm="Restore this record?">Restore</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?= wa_pagination($pagination, '/trash', ['module' => $module, 'q' => $q]) ?>
</div>
<?php
wa_render('Trash', ob_get_clean());
