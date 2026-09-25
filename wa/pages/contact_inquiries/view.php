<?php
declare(strict_types=1);

wa_require_perm('contact_inquiries.view');
$id = (int) ($_GET['id'] ?? 0);
$res = wa_api('GET', '/api/contact-inquiries/view', ['id' => $id]);
if (!wa_api_ok($res)) {
    wa_flash('error', $res['message'] ?? 'Not found');
    wa_redirect('/contact-inquiries');
}
$row = $res['data'];
$error = '';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && wa_can('contact_inquiries.edit')) {
    $status = (string) ($_POST['status'] ?? '');
    $save = wa_api('POST', '/api/contact-inquiries/update', [
        'id' => $id,
        'status' => $status,
    ]);
    if (wa_api_ok($save)) {
        wa_flash('success', $save['message'] ?? 'Saved');
        wa_redirect('/contact-inquiries/view?id=' . $id);
    }
    $error = (string) ($save['message'] ?? 'Save failed');
    $row['status'] = $status;
}

$statuses = $row['statuses'] ?? [];

ob_start();
?>
<div class="page-head">
  <div><h1>Inquiry #<?= (int) $row['id'] ?></h1></div>
  <a class="btn btn-ghost" href="<?= wa_e(wa_url('/contact-inquiries')) ?>">Back</a>
</div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <p><strong>Name:</strong> <?= wa_e((string) ($row['full_name'] ?? '—')) ?></p>
  <p><strong>Company:</strong> <?= wa_e((string) ($row['company_name'] ?? '—')) ?></p>
  <p><strong>Phone:</strong> <?= wa_e((string) ($row['phone'] ?? '—')) ?></p>
  <p><strong>Email:</strong> <?= wa_e((string) ($row['email'] ?? '—')) ?></p>
  <p><strong>Preferred service:</strong> <?= wa_e((string) ($row['preferred_service_title'] ?? '—')) ?></p>
  <p><strong>Received:</strong> <?= wa_e((string) ($row['created_at'] ?? '')) ?></p>
  <?php foreach (($row['custom_fields'] ?? []) as $cf): ?>
    <?php
      $cfVal = $cf['value'] ?? null;
      $cfDisplay = ($cfVal === null || $cfVal === '') ? '—' : (string) $cfVal;
    ?>
    <p>
      <strong><?= wa_e((string) ($cf['label'] ?? $cf['field_key'] ?? 'Field')) ?>:</strong>
      <?= ((string) ($cf['field_type'] ?? '')) === 'textarea' ? nl2br(wa_e($cfDisplay)) : wa_e($cfDisplay) ?>
    </p>
  <?php endforeach; ?>
  <p><strong>Message:</strong></p>
  <p><?= nl2br(wa_e((string) ($row['message'] ?? ''))) ?></p>

  <?php if (wa_can('contact_inquiries.edit')): ?>
    <form method="post" class="form-grid" style="margin-top:1.25rem;border-top:1px solid #e5e7eb;padding-top:1rem">
      <?= wa_csrf_field() ?>
      <label>Status
        <select name="status">
          <?php foreach ($statuses as $st): ?>
            <option value="<?= wa_e((string) $st['value']) ?>" <?= (string) ($row['status'] ?? '') === (string) $st['value'] ? 'selected' : '' ?>><?= wa_e((string) $st['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="actions"><button class="btn" type="submit">Update status</button></div>
    </form>
  <?php else: ?>
    <p><strong>Status:</strong> <?= wa_e((string) ($row['status_label'] ?? $row['status'] ?? '')) ?></p>
  <?php endif; ?>
</div>
<?php
wa_render('Contact Inquiry', ob_get_clean());
