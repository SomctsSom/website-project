<?php
declare(strict_types=1);

wa_require_perm('audit.view');

$q = trim((string) ($_GET['q'] ?? ''));
$entity = trim((string) ($_GET['entity_type'] ?? ''));
$action = trim((string) ($_GET['action'] ?? ''));
$res = wa_api('GET', '/api/audit', [
    'q' => $q, 'entity_type' => $entity, 'action' => $action,
    'page' => (int) ($_GET['page'] ?? 1),
]);
$items = wa_api_ok($res) ? ($res['data']['items'] ?? []) : [];
$pagination = wa_api_ok($res) ? ($res['data']['pagination'] ?? []) : [];

ob_start();
?>
<div class="page-head"><div><h1>Audit logs</h1><p>Read-only activity history (secrets excluded).</p></div></div>
<div class="panel">
  <form class="toolbar" method="get">
    <input type="search" name="q" value="<?= wa_e($q) ?>" placeholder="Search">
    <input name="entity_type" value="<?= wa_e($entity) ?>" placeholder="entity">
    <input name="action" value="<?= wa_e($action) ?>" placeholder="action">
    <button class="btn btn-secondary" type="submit">Search</button>
  </form>
  <table class="data">
    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th></tr></thead>
    <tbody>
    <?php foreach ($items as $row): ?>
      <tr>
        <td><?= wa_e($row['created_at_display'] ?? $row['created_at']) ?></td>
        <td><?= wa_e(($row['user_name'] ?? '') . ' ' . ($row['user_email'] ?? '')) ?></td>
        <td><?= wa_e($row['action']) ?></td>
        <td><?= wa_e($row['entity_type']) ?> #<?= wa_e((string)$row['entity_id']) ?></td>
        <td><code style="font-size:0.8rem"><?= wa_e(is_array($row['details'] ?? null) ? json_encode($row['details']) : (string)($row['details_json'] ?? '')) ?></code></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?= wa_pagination($pagination, '/audit-logs', ['q'=>$q,'entity_type'=>$entity,'action'=>$action]) ?>
</div>
<?php
wa_render('Audit logs', ob_get_clean());
