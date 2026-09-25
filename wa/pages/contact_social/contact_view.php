<?php
declare(strict_types=1);

wa_require_perm('contact_social.view');
$id = (int) ($_GET['id'] ?? 0);
$res = wa_api('GET', '/api/contact-infos/view', ['id' => $id]);
if (!wa_api_ok($res)) {
    wa_flash('error', $res['message'] ?? 'Not found');
    wa_redirect('/contact-social/contact');
}
$p = $res['data'];
ob_start();
?>
<div class="page-head">
  <div><h1>Contact details</h1></div>
  <div style="display:flex;gap:0.5rem">
    <?php if (wa_can('contact_social.edit')): ?>
      <a class="btn" href="<?= wa_e(wa_url('/contact-social/contact/edit?id=' . (int) $p['id'])) ?>">Edit</a>
    <?php endif; ?>
    <a class="btn btn-ghost" href="<?= wa_e(wa_url('/contact-social/contact')) ?>">Back</a>
  </div>
</div>
<div class="panel">
  <?php if (!empty($p['company_name'])): ?><p><strong><?= wa_e((string) $p['company_name']) ?></strong></p><?php endif; ?>
  <?php if (!empty($p['tagline'])): ?><p><?= wa_e((string) $p['tagline']) ?></p><?php endif; ?>
  <p><?= nl2br(wa_e((string) ($p['company_summary'] ?? ''))) ?></p>
  <?php if (!empty($p['email'])): ?><p>Email: <?= wa_e((string) $p['email']) ?></p><?php endif; ?>
  <?php if (!empty($p['business_hours'])): ?><p>Hours: <?= wa_e((string) $p['business_hours']) ?></p><?php endif; ?>
  <h3>Addresses</h3>
  <ul>
    <?php foreach (($p['addresses'] ?? []) as $a): ?>
      <li><?php if (!empty($a['label'])): ?><strong><?= wa_e((string) $a['label']) ?>:</strong> <?php endif; ?><?= wa_e((string) $a['address_text']) ?></li>
    <?php endforeach; ?>
  </ul>
  <h3>Phones</h3>
  <ul>
    <?php foreach (($p['phones'] ?? []) as $ph): ?>
      <li><?php if (!empty($ph['label'])): ?><strong><?= wa_e((string) $ph['label']) ?>:</strong> <?php endif; ?><?= wa_e((string) $ph['phone']) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php
wa_render('Contact view', ob_get_clean());
