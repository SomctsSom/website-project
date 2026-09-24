<?php
declare(strict_types=1);

wa_require_perm('profile_overviews.view');
$id = (int) ($_GET['id'] ?? 0);
$res = wa_api('GET', '/api/profile-overviews/view', ['id' => $id]);
if (!wa_api_ok($res)) {
    wa_flash('error', $res['message'] ?? 'Not found');
    wa_redirect('/profile-overviews');
}
$p = $res['data'];
ob_start();
?>
<div class="page-head"><div><h1><?= wa_e($p['title']) ?></h1></div>
  <div class="actions">
    <?php if (wa_can('profile_overviews.edit')): ?><a class="btn" href="<?= wa_e(wa_url('/profile-overviews/edit?id='.$p['id'])) ?>">Edit</a><?php endif; ?>
    <a class="btn btn-ghost" href="<?= wa_e(wa_url('/profile-overviews')) ?>">Back</a>
  </div>
</div>
<div class="panel">
  <p>Status: <?= wa_badge((int)$p['is_active']) ?></p>
  <div class="profile-admin-body"><?= (string) ($p['body_html'] ?? '') ?></div>
  <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin:1rem 0">
    <?php foreach (['image_top_url'=>'Top','image_left_url'=>'Left','image_right_url'=>'Right'] as $key => $label): ?>
      <?php if (!empty($p[$key])): ?>
        <div><div class="muted"><?= wa_e($label) ?></div><img src="<?= wa_e((string)$p[$key]) ?>" alt="" style="max-width:220px;border-radius:12px"></div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
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
wa_render('View profile overview', ob_get_clean());
