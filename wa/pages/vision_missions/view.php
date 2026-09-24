<?php
declare(strict_types=1);

wa_require_perm('vision_missions.view');
$id = (int) ($_GET['id'] ?? 0);
$res = wa_api('GET', '/api/vision-missions/view', ['id' => $id]);
if (!wa_api_ok($res)) {
    wa_flash('error', $res['message'] ?? 'Not found');
    wa_redirect('/vision-missions');
}
$p = $res['data'];
ob_start();
?>
<div class="page-head"><div><h1><?= wa_e($p['title']) ?></h1><p><?= wa_e((string)($p['statement_type_label'] ?? $p['statement_type'])) ?></p></div>
  <div class="actions">
    <?php if (wa_can('vision_missions.edit')): ?><a class="btn" href="<?= wa_e(wa_url('/vision-missions/edit?id='.$p['id'])) ?>">Edit</a><?php endif; ?>
    <a class="btn btn-ghost" href="<?= wa_e(wa_url('/vision-missions')) ?>">Back</a>
  </div>
</div>
<div class="panel">
  <p>Status: <?= wa_badge((int)$p['is_active']) ?></p>
  <div class="profile-admin-body"><?= (string) ($p['body_html'] ?? '') ?></div>
  <?php if (!empty($p['image_url'])): ?>
    <div style="margin:1rem 0"><img src="<?= wa_e((string)$p['image_url']) ?>" alt="" style="max-width:280px;border-radius:12px"></div>
  <?php endif; ?>
  <h3>Assigned pages</h3>
  <table class="data">
    <thead><tr><th>Page</th><th>Active</th><th>Featured</th><th>Order</th></tr></thead>
    <tbody>
    <?php foreach ($p['pages'] ?? [] as $row): ?>
      <tr>
        <td><?= wa_e($row['title']) ?></td>
        <td><?= wa_badge((int)$row['is_active']) ?></td>
        <td><?= wa_badge((int)$row['is_featured'], 'Featured', 'No') ?></td>
        <td><?= (int)$row['sort_order'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
wa_render('View Vision & Mission', ob_get_clean());
