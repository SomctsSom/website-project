<?php
declare(strict_types=1);

wa_require_perm('contact_social.view');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && wa_can('contact_social.delete')) {
        $res = wa_api('POST', '/api/contact-infos/delete', ['id' => $id]);
        wa_flash(wa_api_ok($res) ? 'success' : 'error', $res['message'] ?? 'Done');
    }
    wa_redirect('/contact-social/contact');
}

$res = wa_api('GET', '/api/contact-infos', ['per_page' => 50]);
$items = wa_api_ok($res) ? ($res['data']['items'] ?? []) : [];

ob_start();
?>
<div class="page-head">
  <div>
    <h1>Contact address</h1>
    <p>Company summary, addresses, and phones.</p>
  </div>
  <div style="display:flex;gap:0.5rem">
    <a class="btn btn-ghost" href="<?= wa_e(wa_url('/contact-social')) ?>">Back</a>
    <?php if (wa_can('contact_social.create')): ?>
      <a class="btn" href="<?= wa_e(wa_url('/contact-social/contact/create')) ?>">Create contact</a>
    <?php endif; ?>
  </div>
</div>
<div class="panel table-wrap">
  <table class="table">
    <thead>
      <tr><th>Company</th><th>Summary</th><th>Addresses</th><th>Phones</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php if (!$items): ?>
        <tr><td colspan="6" class="muted">No contact blocks yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($items as $row): ?>
        <tr>
          <td><?= wa_e((string) ($row['company_name'] ?? '—')) ?></td>
          <td><?= wa_e(mb_strimwidth((string) ($row['company_summary'] ?? ''), 0, 80, '…')) ?></td>
          <td><?= (int) ($row['address_count'] ?? 0) ?></td>
          <td><?= (int) ($row['phone_count'] ?? 0) ?></td>
          <td><?= (int) ($row['is_active'] ?? 0) === 1 ? 'Active' : 'Inactive' ?></td>
          <td class="row-actions">
            <a href="<?= wa_e(wa_url('/contact-social/contact/view?id=' . (int) $row['id'])) ?>">View</a>
            <?php if (wa_can('contact_social.edit')): ?>
              · <a href="<?= wa_e(wa_url('/contact-social/contact/edit?id=' . (int) $row['id'])) ?>">Edit</a>
            <?php endif; ?>
            <?php if (wa_can('contact_social.delete')): ?>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this contact block?')"><?= wa_csrf_field() ?>
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <button class="linkish danger" type="submit">Delete</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
wa_render('Contact address', ob_get_clean());
