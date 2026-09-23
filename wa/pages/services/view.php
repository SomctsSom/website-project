<?php
declare(strict_types=1);

wa_require_perm('services.view');
$id = (int) ($_GET['id'] ?? 0);
$res = wa_api('GET', '/api/services/view', ['id' => $id]);
if (!wa_api_ok($res)) {
    wa_flash('error', $res['message'] ?? 'Not found');
    wa_redirect('/services');
}
$s = $res['data'];
ob_start();
?>
<div class="page-head"><div><h1><?= wa_e($s['title']) ?></h1></div>
  <div class="actions">
    <?php if (wa_can('services.edit')): ?><a class="btn" href="<?= wa_e(wa_url('/services/edit?id='.$s['id'])) ?>">Edit</a><?php endif; ?>
    <a class="btn btn-ghost" href="<?= wa_e(wa_url('/services')) ?>">Back</a>
  </div>
</div>
<div class="panel">
  <?php if (!empty($s['image_url'])): ?><p><img src="<?= wa_e($s['image_url']) ?>" alt="" style="max-width:280px;border-radius:12px"></p><?php endif; ?>
  <p><?= wa_e((string)$s['description']) ?></p>
  <p>Status: <?= wa_badge((int)$s['is_active']) ?></p>
  <?php if (!empty($s['items'])): ?>
    <h3>List under description</h3>
    <ul>
      <?php foreach ($s['items'] as $item): ?>
        <li><strong><?= wa_e($item['title']) ?></strong><?php if (!empty($item['body'])): ?> — <?= wa_e((string)$item['body']) ?><?php endif; ?> <?= wa_badge((int)$item['is_active']) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="muted">No list items (optional).</p>
  <?php endif; ?>
  <h3>Assigned pages</h3>
  <table class="data">
    <thead><tr><th>Page</th><th>Active</th><th>Featured</th><th>Order</th></tr></thead>
    <tbody>
    <?php foreach ($s['pages'] ?? [] as $p): ?>
      <tr>
        <td><?= wa_e($p['title']) ?></td>
        <td><?= wa_badge((int)$p['is_active']) ?></td>
        <td><?= wa_badge((int)$p['is_featured'], 'Featured', 'No') ?></td>
        <td><?= (int)$p['sort_order'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
wa_render('View service', ob_get_clean());
