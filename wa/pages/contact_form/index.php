<?php
declare(strict_types=1);

wa_require_perm('contact_form.view');

$error = '';
$settings = [
    'show_full_name' => 1,
    'show_company_name' => 1,
    'show_phone' => 1,
    'show_email' => 1,
    'show_preferred_service' => 1,
    'show_message' => 1,
];

$res = wa_api('GET', '/api/contact-form');
if (wa_api_ok($res)) {
    $settings = array_merge($settings, $res['data'] ?? []);
}
$formPages = is_array($settings['pages'] ?? null) ? $settings['pages'] : [];
unset($settings['pages']);

$fieldsRes = wa_api('GET', '/api/contact-form/fields');
$customFields = wa_api_ok($fieldsRes) ? ($fieldsRes['data']['items'] ?? []) : [];
$fieldTypes = wa_api_ok($fieldsRes) ? ($fieldsRes['data']['field_types'] ?? ['text', 'textarea', 'number', 'date', 'select']) : ['text', 'textarea', 'number', 'date', 'select'];
$availableTables = wa_api_ok($fieldsRes) ? ($fieldsRes['data']['available_tables'] ?? []) : [];

$canEdit = wa_can('contact_form.edit');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $canEdit) {
    $action = (string) ($_POST['form_action'] ?? 'save_settings');

    if ($action === 'save_settings') {
        $payload = [
            'show_full_name' => isset($_POST['show_full_name']) ? 1 : 0,
            'show_company_name' => isset($_POST['show_company_name']) ? 1 : 0,
            'show_phone' => isset($_POST['show_phone']) ? 1 : 0,
            'show_email' => isset($_POST['show_email']) ? 1 : 0,
            'show_preferred_service' => isset($_POST['show_preferred_service']) ? 1 : 0,
            'show_message' => isset($_POST['show_message']) ? 1 : 0,
        ];
        $save = wa_api('POST', '/api/contact-form', $payload);
        if (wa_api_ok($save)) {
            wa_flash('success', $save['message'] ?? 'Saved');
            wa_redirect('/contact-form');
        }
        $error = (string) ($save['message'] ?? 'Save failed');
        $settings = array_merge($settings, $payload);
    }

    if ($action === 'save_pages') {
        $pageIds = array_map('intval', $_POST['page_ids'] ?? []);
        $save = wa_api('POST', '/api/contact-form/pages', ['page_ids' => $pageIds]);
        if (wa_api_ok($save)) {
            wa_flash('success', $save['message'] ?? 'Page visibility saved');
            wa_redirect('/contact-form');
        }
        $error = (string) ($save['message'] ?? 'Could not save page visibility');
        $formPages = is_array($save['data']['pages'] ?? null) ? $save['data']['pages'] : $formPages;
    }

    if ($action === 'add_field') {
        $payload = [
            'label' => trim((string) ($_POST['label'] ?? '')),
            'field_type' => (string) ($_POST['field_type'] ?? 'text'),
            'select_source' => (string) ($_POST['select_source'] ?? 'manual'),
            'source_table' => (string) ($_POST['source_table'] ?? ''),
            'options' => (string) ($_POST['options'] ?? ''),
            'is_required' => isset($_POST['is_required']) ? 1 : 0,
            'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        $save = wa_api('POST', '/api/contact-form/fields', $payload);
        if (wa_api_ok($save)) {
            wa_flash('success', $save['message'] ?? 'Field added');
            wa_redirect('/contact-form');
        }
        $error = (string) ($save['message'] ?? 'Could not add field');
    }

    if ($action === 'update_field') {
        $payload = [
            'id' => (int) ($_POST['id'] ?? 0),
            'label' => trim((string) ($_POST['label'] ?? '')),
            'options' => (string) ($_POST['options'] ?? ''),
            'is_required' => isset($_POST['is_required']) ? 1 : 0,
            'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        $save = wa_api('POST', '/api/contact-form/fields/update', $payload);
        if (wa_api_ok($save)) {
            wa_flash('success', $save['message'] ?? 'Field updated');
            wa_redirect('/contact-form');
        }
        $error = (string) ($save['message'] ?? 'Could not update field');
    }

    if ($action === 'delete_field') {
        $save = wa_api('POST', '/api/contact-form/fields/delete', [
            'id' => (int) ($_POST['id'] ?? 0),
        ]);
        wa_flash(wa_api_ok($save) ? 'success' : 'error', (string) ($save['message'] ?? 'Done'));
        wa_redirect('/contact-form');
    }
}

ob_start();
?>
<div class="page-head">
  <div>
    <h1>Contact Form</h1>
    <p>Choose which fields appear on the public form, which website pages show it, and add custom columns.</p>
  </div>
  <div style="display:flex;gap:0.5rem">
    <?php if (wa_can('contact_inquiries.view')): ?>
      <a class="btn btn-ghost" href="<?= wa_e(wa_url('/contact-inquiries')) ?>">Inquiries</a>
    <?php endif; ?>
  </div>
</div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>

<div class="panel">
  <form method="post" class="form-grid">
    <?= wa_csrf_field() ?>
    <input type="hidden" name="form_action" value="save_settings">
    <div class="full"><strong>Built-in fields (visible on public form)</strong></div>
    <label style="display:flex;align-items:center;gap:0.5rem">
      <input type="checkbox" name="show_full_name" value="1" <?= (int)$settings['show_full_name'] === 1 ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
      Full Name <span class="muted">(required when shown)</span>
    </label>
    <label style="display:flex;align-items:center;gap:0.5rem">
      <input type="checkbox" name="show_company_name" value="1" <?= (int)$settings['show_company_name'] === 1 ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
      Company Name <span class="muted">(optional)</span>
    </label>
    <label style="display:flex;align-items:center;gap:0.5rem">
      <input type="checkbox" name="show_phone" value="1" <?= (int)$settings['show_phone'] === 1 ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
      Phone Number <span class="muted">(required when shown)</span>
    </label>
    <label style="display:flex;align-items:center;gap:0.5rem">
      <input type="checkbox" name="show_email" value="1" <?= (int)$settings['show_email'] === 1 ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
      Email Address <span class="muted">(required when shown)</span>
    </label>
    <label style="display:flex;align-items:center;gap:0.5rem">
      <input type="checkbox" name="show_preferred_service" value="1" <?= (int)$settings['show_preferred_service'] === 1 ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
      Preferred Service <span class="muted">(dropdown from Services)</span>
    </label>
    <label style="display:flex;align-items:center;gap:0.5rem">
      <input type="checkbox" name="show_message" value="1" <?= (int)$settings['show_message'] === 1 ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
      Comments / Message <span class="muted">(required when shown)</span>
    </label>
    <?php if ($canEdit): ?>
      <div class="actions full"><button class="btn" type="submit">Save form settings</button></div>
    <?php endif; ?>
  </form>
</div>

<div class="panel" style="margin-top:1.25rem">
  <form method="post">
    <?= wa_csrf_field() ?>
    <input type="hidden" name="form_action" value="save_pages">
    <div class="page-head" style="margin-bottom:0.75rem">
      <div>
        <h2 style="margin:0;font-size:1.15rem">Display on pages</h2>
        <p class="muted" style="margin:0.25rem 0 0">Checked pages show the contact form section on the public website.</p>
      </div>
    </div>
    <?php if (!$formPages): ?>
      <p class="muted">No website pages found.</p>
    <?php else: ?>
      <div class="page-checks">
        <?php foreach ($formPages as $pg): ?>
          <label class="page-check" style="grid-template-columns:auto 1fr">
            <input type="checkbox" name="page_ids[]" value="<?= (int) ($pg['id'] ?? 0) ?>"
              <?= (int) ($pg['is_visible'] ?? 0) === 1 ? 'checked' : '' ?>
              <?= $canEdit ? '' : 'disabled' ?>>
            <span>
              <?= wa_e((string) ($pg['title'] ?? '')) ?>
              <span class="muted">(/<?= wa_e((string) ($pg['slug'] ?? '')) ?>)</span>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($canEdit && $formPages): ?>
      <div class="actions" style="margin-top:1rem"><button class="btn" type="submit">Save page visibility</button></div>
    <?php endif; ?>
  </form>
</div>

<div class="panel" style="margin-top:1.25rem">
  <div class="page-head" style="margin-bottom:0.75rem">
    <div>
      <h2 style="margin:0;font-size:1.15rem">Custom fields</h2>
      <p class="muted" style="margin:0.25rem 0 0">Saving a new field adds a real column on <code>contact_inquiries</code>. Unchecking Visible hides it on the public form without dropping the column.</p>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Label</th>
          <th>Column</th>
          <th>Type</th>
          <th>Required</th>
          <th>Visible</th>
          <th>Sort</th>
          <?php if ($canEdit): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (!$customFields): ?>
          <tr><td colspan="<?= $canEdit ? 7 : 6 ?>" class="muted">No custom fields yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($customFields as $field): ?>
          <?php
            $fid = (int) ($field['id'] ?? 0);
            $opts = $field['options'] ?? [];
            $optLines = [];
            foreach ($opts as $opt) {
                if (is_array($opt)) {
                    $optLines[] = (string) ($opt['value'] ?? $opt['label'] ?? '');
                } else {
                    $optLines[] = (string) $opt;
                }
            }
            $optsText = implode("\n", array_filter($optLines, static fn ($v) => $v !== ''));
            $isTableSelect = (string) ($field['field_type'] ?? '') === 'select'
                && (string) ($field['select_source'] ?? 'manual') === 'table';
          ?>
          <tr>
            <?php if ($canEdit): ?>
              <td colspan="7" style="padding:0.85rem 0.75rem">
                <form method="post" class="form-grid" style="margin:0;grid-template-columns:repeat(6,minmax(0,1fr));gap:0.65rem;align-items:end">
                  <?= wa_csrf_field() ?>
                  <input type="hidden" name="form_action" value="update_field">
                  <input type="hidden" name="id" value="<?= $fid ?>">
                  <label>Label<input name="label" required value="<?= wa_e((string) ($field['label'] ?? '')) ?>"></label>
                  <label>Column<input value="<?= wa_e((string) ($field['field_key'] ?? '')) ?>" disabled></label>
                  <label>Type<input value="<?= wa_e((string) ($field['field_type'] ?? '') . ($isTableSelect ? ' → ' . (string) ($field['source_table'] ?? '') : '')) ?>" disabled></label>
                  <label>Sort<input type="number" name="sort_order" value="<?= (int) ($field['sort_order'] ?? 0) ?>"></label>
                  <label style="display:flex;align-items:center;gap:0.4rem;padding-bottom:0.55rem">
                    <input type="checkbox" name="is_required" value="1" <?= (int) ($field['is_required'] ?? 0) === 1 ? 'checked' : '' ?>> Required
                  </label>
                  <label style="display:flex;align-items:center;gap:0.4rem;padding-bottom:0.55rem">
                    <input type="checkbox" name="is_visible" value="1" <?= (int) ($field['is_visible'] ?? 0) === 1 ? 'checked' : '' ?>> Visible
                  </label>
                  <?php if ((string) ($field['field_type'] ?? '') === 'select' && !$isTableSelect): ?>
                    <label class="full" style="grid-column:1/-1">Options (one per line)
                      <textarea name="options" rows="2"><?= wa_e($optsText) ?></textarea>
                    </label>
                  <?php else: ?>
                    <input type="hidden" name="options" value="">
                    <?php if ($isTableSelect): ?>
                      <p class="muted full" style="grid-column:1/-1;margin:0">Connected table: <code><?= wa_e((string) ($field['source_table'] ?? '')) ?></code> (cannot change after create)</p>
                    <?php endif; ?>
                  <?php endif; ?>
                  <div class="actions" style="grid-column:1/-1;display:flex;gap:0.5rem">
                    <button class="btn" type="submit">Update</button>
                  </div>
                </form>
                <form method="post" style="margin-top:0.5rem" onsubmit="return confirm('Remove this field from the form? The database column is kept.');">
                  <?= wa_csrf_field() ?>
                  <input type="hidden" name="form_action" value="delete_field">
                  <input type="hidden" name="id" value="<?= $fid ?>">
                  <button class="btn btn-ghost" type="submit">Remove</button>
                </form>
              </td>
            <?php else: ?>
              <td><?= wa_e((string) ($field['label'] ?? '')) ?></td>
              <td><code><?= wa_e((string) ($field['field_key'] ?? '')) ?></code></td>
              <td><?= wa_e((string) ($field['field_type'] ?? '')) ?><?= $isTableSelect ? ' → ' . wa_e((string) ($field['source_table'] ?? '')) : '' ?></td>
              <td><?= (int) ($field['is_required'] ?? 0) === 1 ? 'Yes' : 'No' ?></td>
              <td><?= (int) ($field['is_visible'] ?? 0) === 1 ? 'Yes' : 'No' ?></td>
              <td><?= (int) ($field['sort_order'] ?? 0) ?></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($canEdit): ?>
    <form method="post" class="form-grid" style="margin-top:1.25rem;border-top:1px solid #e5e7eb;padding-top:1rem">
      <?= wa_csrf_field() ?>
      <input type="hidden" name="form_action" value="add_field">
      <div class="full"><strong>Add custom field</strong></div>
      <label>Label<input name="label" required placeholder="e.g. Subject"></label>
      <label>Type
        <select name="field_type" id="cf-field-type">
          <?php foreach ($fieldTypes as $type): ?>
            <option value="<?= wa_e((string) $type) ?>"><?= wa_e((string) $type) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Sort order<input type="number" name="sort_order" value="0" placeholder="Auto"></label>
      <label style="display:flex;align-items:center;gap:0.5rem;padding-top:1.6rem">
        <input type="checkbox" name="is_required" value="1"> Required
      </label>
      <label style="display:flex;align-items:center;gap:0.5rem;padding-top:1.6rem">
        <input type="checkbox" name="is_visible" value="1" checked> Visible on public form
      </label>

      <div class="full" id="cf-select-source-wrap" style="display:none">
        <strong style="display:block;margin-bottom:0.5rem">Select options source</strong>
        <label style="display:inline-flex;align-items:center;gap:0.4rem;margin-right:1.25rem">
          <input type="radio" name="select_source" value="manual" id="cf-source-manual" checked> Manual
        </label>
        <label style="display:inline-flex;align-items:center;gap:0.4rem">
          <input type="radio" name="select_source" value="table" id="cf-source-table"> Connect table
        </label>
      </div>

      <label class="full" id="cf-options-wrap" style="display:none">Options (one per line, for select)
        <textarea name="options" rows="3" placeholder="Option A&#10;Option B"></textarea>
      </label>

      <div class="full" id="cf-tables-wrap" style="display:none">
        <strong style="display:block;margin-bottom:0.5rem">Connect table (foreign key)</strong>
        <?php if (!$availableTables): ?>
          <p class="muted" style="margin:0">All connectable tables are already linked. Remove a connected field to free a table.</p>
        <?php else: ?>
          <?php foreach ($availableTables as $tbl): ?>
            <label style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.35rem">
              <input type="radio" name="source_table" value="<?= wa_e((string) ($tbl['table'] ?? '')) ?>">
              <?= wa_e((string) ($tbl['label'] ?? $tbl['table'] ?? '')) ?>
              <span class="muted">(<code><?= wa_e((string) ($tbl['table'] ?? '')) ?></code>)</span>
            </label>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="actions full"><button class="btn" type="submit">Save &amp; add column</button></div>
    </form>
    <script>
      (function () {
        var type = document.getElementById('cf-field-type');
        var sourceWrap = document.getElementById('cf-select-source-wrap');
        var optionsWrap = document.getElementById('cf-options-wrap');
        var tablesWrap = document.getElementById('cf-tables-wrap');
        var manual = document.getElementById('cf-source-manual');
        var table = document.getElementById('cf-source-table');
        if (!type || !sourceWrap || !optionsWrap || !tablesWrap) return;
        function sync() {
          var isSelect = type.value === 'select';
          sourceWrap.style.display = isSelect ? '' : 'none';
          if (!isSelect) {
            optionsWrap.style.display = 'none';
            tablesWrap.style.display = 'none';
            return;
          }
          var useTable = table && table.checked;
          optionsWrap.style.display = useTable ? 'none' : '';
          tablesWrap.style.display = useTable ? '' : 'none';
        }
        type.addEventListener('change', sync);
        if (manual) manual.addEventListener('change', sync);
        if (table) table.addEventListener('change', sync);
        sync();
      })();
    </script>
  <?php endif; ?>
</div>
<?php
wa_render('Contact Form', ob_get_clean());
