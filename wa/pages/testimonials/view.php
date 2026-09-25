<?php
declare(strict_types=1);

wa_require_perm('testimonials.view');
$id = (int) ($_GET['id'] ?? 0);
$res = wa_api('GET', '/api/testimonials/view', ['id' => $id]);
if (!wa_api_ok($res)) {
    wa_flash('error', $res['message'] ?? 'Not found');
    wa_redirect('/testimonials');
}
$p = $res['data'];
ob_start();
?>
<div class="page-head"><div><h1><?= wa_e((string)$p['author_name']) ?></h1></div>
  <div class="actions">
    <?php if (wa_can('testimonials.edit')): ?><a class="btn" href="<?= wa_e(wa_url('/testimonials/edit?id='.$p['id'])) ?>">Edit</a><?php endif; ?>
    <a class="btn btn-ghost" href="<?= wa_e(wa_url('/testimonials')) ?>">Back</a>
  </div>
</div>
<div class="panel">
  <p>Status: <?= wa_badge((int)$p['is_active']) ?></p>
  <p style="color:#f15a24;letter-spacing:0.15em"><?= str_repeat('★', (int)($p['rating'] ?? 5)) ?></p>
  <p style="font-size:1.1rem">“<?= wa_e((string)$p['quote']) ?>”</p>
  <?php if (!empty($p['author_role'])): ?>
    <p class="muted"><?= wa_e((string)$p['author_role']) ?></p>
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
wa_render('View testimonial', ob_get_clean());
