<?php
declare(strict_types=1);

wa_require_perm('contact_inquiries.view');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && wa_can('contact_inquiries.delete')) {
        $res = wa_api('POST', '/api/contact-inquiries/delete', ['id' => $id]);
        wa_flash(wa_api_ok($res) ? 'success' : 'error', (string) ($res['message'] ?? 'Done'));
    }
    wa_redirect('/contact-inquiries?' . http_build_query(array_filter([
        'q' => $_GET['q'] ?? null,
        'status' => $_GET['status'] ?? null,
        'page' => $_GET['page'] ?? null,
    ], static fn ($v) => $v !== null && $v !== '')));
}

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$res = wa_api('GET', '/api/contact-inquiries', [
    'q' => $q,
    'status' => $status,
    'page' => $page,
    'per_page' => 20,
]);
$items = wa_api_ok($res) ? ($res['data']['items'] ?? []) : [];
$pagination = wa_api_ok($res) ? ($res['data']['pagination'] ?? []) : [];
$statuses = wa_api_ok($res) ? ($res['data']['statuses'] ?? []) : [];

ob_start();
?>
<div class="page-head">
  <div>
    <h1>Contact Inquiries</h1>
    <p>Messages submitted from the public Contact Us form.</p>
  </div>
  <div style="display:flex;gap:0.5rem">
    <?php if (wa_can('contact_form.view')): ?>
      <a class="btn btn-ghost" href="<?= wa_e(wa_url('/contact-form')) ?>">Form settings</a>
    <?php endif; ?>
  </div>
</div>
<div class="panel">
  <form method="get" class="form-grid" style="margin-bottom:1rem">
    <label>Search<input name="q" value="<?= wa_e($q) ?>" placeholder="Name, email, phone…"></label>
    <label>Status
      <select name="status">
        <option value="">All</option>
        <?php foreach ($statuses as $st): ?>
          <option value="<?= wa_e((string) $st['value']) ?>" <?= $status === (string) $st['value'] ? 'selected' : '' ?>><?= wa_e((string) $st['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <div class="actions"><button class="btn" type="submit">Filter</button></div>
  </form>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Service</th>
          <th>Status</th>
          <th>Received</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$items): ?>
          <tr><td colspan="7" class="muted">No inquiries yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $row): ?>
          <tr>
            <td><?= wa_e((string) ($row['full_name'] ?? '—')) ?></td>
            <td><?= wa_e((string) ($row['email'] ?? '—')) ?></td>
            <td><?= wa_e((string) ($row['phone'] ?? '—')) ?></td>
            <td><?= wa_e((string) ($row['preferred_service_title'] ?? '—')) ?></td>
            <td><?= wa_e((string) ($row['status_label'] ?? $row['status'] ?? '')) ?></td>
            <td><?= wa_e((string) ($row['created_at'] ?? '')) ?></td>
            <td class="row-actions">
              <a href="<?= wa_e(wa_url('/contact-inquiries/view?id=' . (int) $row['id'])) ?>">View</a>
              <?php if (wa_can('contact_inquiries.delete')): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this inquiry?')">
                  <?= wa_csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button class="linkish danger" type="submit">Delete</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= wa_pagination($pagination, '/contact-inquiries', ['q' => $q, 'status' => $status]) ?>
</div>
<?php
wa_render('Contact Inquiries', ob_get_clean());
