<?php
declare(strict_types=1);

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
wa_require_perm($isEdit ? 'contact_social.edit' : 'contact_social.create');

$item = [
    'company_summary' => '',
    'company_name' => '',
    'tagline' => '',
    'email' => '',
    'business_hours' => '',
    'logo_path' => null,
    'logo_url' => null,
    'sort_order' => 0,
    'is_active' => 1,
    'addresses' => [['label' => 'Office', 'address_text' => '']],
    'phones' => [['label' => 'Main', 'phone' => '']],
    'pages' => [],
];
if ($isEdit) {
    $view = wa_api('GET', '/api/contact-infos/view', ['id' => $id]);
    if (!wa_api_ok($view)) {
        wa_flash('error', $view['message'] ?? 'Not found');
        wa_redirect('/contact-social/contact');
    }
    $item = $view['data'];
    if (empty($item['addresses'])) {
        $item['addresses'] = [['label' => '', 'address_text' => '']];
    }
    if (empty($item['phones'])) {
        $item['phones'] = [['label' => '', 'phone' => '']];
    }
}

$pagesRes = wa_api('GET', '/api/pages', ['page_type' => 'website', 'per_page' => 100]);
$pages = wa_api_ok($pagesRes) ? ($pagesRes['data']['items'] ?? []) : [];
$assignedMap = [];
foreach ($item['pages'] ?? [] as $p) {
    $assignedMap[(int) $p['page_id']] = $p;
}

$error = '';
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $selected = $_POST['page_selected'] ?? [];
    $assignments = [];
    foreach ($selected as $pid) {
        $pid = (int) $pid;
        $assignments[] = [
            'page_id' => $pid,
            'is_active' => isset($_POST['page_active'][$pid]) ? 1 : 0,
            'is_featured' => isset($_POST['page_featured'][$pid]) ? 1 : 0,
            'sort_order' => (int) ($_POST['page_sort'][$pid] ?? 0),
        ];
    }
    $addrLabels = $_POST['addr_label'] ?? [];
    $addrTexts = $_POST['addr_text'] ?? [];
    $addresses = [];
    if (is_array($addrTexts)) {
        foreach ($addrTexts as $i => $text) {
            $text = trim((string) $text);
            if ($text === '') {
                continue;
            }
            $addresses[] = [
                'label' => trim((string) ($addrLabels[$i] ?? '')),
                'address_text' => $text,
                'sort_order' => ((int) $i + 1) * 10,
            ];
        }
    }
    $phoneLabels = $_POST['phone_label'] ?? [];
    $phoneNums = $_POST['phone_num'] ?? [];
    $phones = [];
    if (is_array($phoneNums)) {
        foreach ($phoneNums as $i => $num) {
            $num = trim((string) $num);
            if ($num === '') {
                continue;
            }
            $phones[] = [
                'label' => trim((string) ($phoneLabels[$i] ?? '')),
                'phone' => $num,
                'sort_order' => ((int) $i + 1) * 10,
            ];
        }
    }
    $fields = [
        'company_summary' => trim((string) ($_POST['company_summary'] ?? '')),
        'company_name' => trim((string) ($_POST['company_name'] ?? '')),
        'tagline' => trim((string) ($_POST['tagline'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'business_hours' => trim((string) ($_POST['business_hours'] ?? '')),
        'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? '1' : '0',
        'addresses_json' => json_encode($addresses, JSON_UNESCAPED_UNICODE),
        'phones_json' => json_encode($phones, JSON_UNESCAPED_UNICODE),
        'pages_json' => json_encode($assignments, JSON_UNESCAPED_UNICODE),
    ];
    if (!empty($_POST['clear_logo'])) {
        $fields['clear_logo'] = '1';
    }
    if ($isEdit) {
        $fields['id'] = (string) $id;
        $res = wa_api_multipart('/api/contact-infos/update', $fields, $_FILES['logo'] ?? [], 'logo');
    } else {
        $res = wa_api_multipart('/api/contact-infos/create', $fields, $_FILES['logo'] ?? [], 'logo');
    }
    if (wa_api_ok($res)) {
        wa_flash('success', $res['message'] ?? 'Saved');
        wa_redirect('/contact-social/contact');
    }
    $error = (string) ($res['message'] ?? 'Save failed');
    $item = array_merge($item, [
        'company_summary' => $fields['company_summary'],
        'company_name' => $fields['company_name'],
        'tagline' => $fields['tagline'],
        'email' => $fields['email'],
        'business_hours' => $fields['business_hours'],
        'sort_order' => (int) $fields['sort_order'],
        'is_active' => (int) $fields['is_active'],
        'addresses' => $addresses ?: [['label' => '', 'address_text' => '']],
        'phones' => $phones ?: [['label' => '', 'phone' => '']],
    ]);
    $assignedMap = [];
    foreach ($assignments as $a) {
        $assignedMap[$a['page_id']] = $a;
    }
}

ob_start();
?>
<div class="page-head">
  <div><h1><?= $isEdit ? 'Edit contact' : 'Create contact' ?></h1></div>
  <a class="btn btn-ghost" href="<?= wa_e(wa_url('/contact-social/contact')) ?>">Back</a>
</div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="post" class="form-grid" enctype="multipart/form-data">
    <?= wa_csrf_field() ?>
    <label class="full">Footer logo<?= $isEdit ? ' (optional replace)' : '' ?>
      <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif">
    </label>
    <?php if (!empty($item['logo_url'])): ?>
      <div class="full" style="display:flex;align-items:center;gap:0.75rem">
        <img class="thumb" style="width:72px;height:72px;object-fit:contain;border-radius:8px;background:#111;padding:0.35rem" src="<?= wa_e((string) $item['logo_url']) ?>" alt="">
        <label style="display:inline-flex;gap:0.4rem;align-items:center">
          <input type="checkbox" name="clear_logo" value="1"> Remove logo
        </label>
      </div>
    <?php endif; ?>

    <label class="full">Company name
      <input name="company_name" value="<?= wa_e((string) ($item['company_name'] ?? '')) ?>" placeholder="Company name">
    </label>
    <label class="full">Tagline
      <input name="tagline" value="<?= wa_e((string) ($item['tagline'] ?? '')) ?>" placeholder="Professional Truck & NEMT Dispatching Services">
    </label>
    <label class="full">Company summary
      <textarea name="company_summary" rows="3" placeholder="Short description of the company"><?= wa_e((string) ($item['company_summary'] ?? '')) ?></textarea>
    </label>
    <label>Email<input type="email" name="email" value="<?= wa_e((string) ($item['email'] ?? '')) ?>" placeholder="info@example.com"></label>
    <label>Business hours<input name="business_hours" value="<?= wa_e((string) ($item['business_hours'] ?? '')) ?>" placeholder="Monday - Friday (08:00AM - 06:00PM)"></label>
    <label>Sort order<input type="number" name="sort_order" value="<?= (int) ($item['sort_order'] ?? 0) ?>"></label>
    <label><span>Active</span><input type="checkbox" name="is_active" value="1" <?= (int) ($item['is_active'] ?? 1) === 1 ? 'checked' : '' ?>></label>

    <div class="full">
      <strong>Addresses</strong>
      <p class="muted">Add one or many.</p>
      <div id="addrRows">
        <?php foreach (($item['addresses'] ?? []) as $i => $a): ?>
          <div class="addr-row" style="display:grid;gap:0.75rem;grid-template-columns:1fr 2fr;margin-bottom:0.65rem">
            <label>Label<input name="addr_label[]" value="<?= wa_e((string) ($a['label'] ?? '')) ?>" placeholder="Office"></label>
            <label>Address<input name="addr_text[]" value="<?= wa_e((string) ($a['address_text'] ?? '')) ?>" placeholder="Street, city, country"></label>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-ghost" id="addAddr">+ Add address</button>
    </div>

    <div class="full">
      <strong>Phones</strong>
      <p class="muted">Add one or many.</p>
      <div id="phoneRows">
        <?php foreach (($item['phones'] ?? []) as $i => $ph): ?>
          <div class="phone-row" style="display:grid;gap:0.75rem;grid-template-columns:1fr 2fr;margin-bottom:0.65rem">
            <label>Label<input name="phone_label[]" value="<?= wa_e((string) ($ph['label'] ?? '')) ?>" placeholder="Main"></label>
            <label>Phone<input name="phone_num[]" value="<?= wa_e((string) ($ph['phone'] ?? '')) ?>" placeholder="+1 ..."></label>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-ghost" id="addPhone">+ Add phone</button>
    </div>

    <div class="full" style="border-top:1px solid #e5e7eb;padding-top:1rem;margin-top:0.35rem">
      <strong>Show this footer on pages</strong>
      <p class="muted" style="margin:0.35rem 0 0.75rem">
        Select pages that should display this contact footer. Unchecked pages will not show it.
      </p>
      <div class="page-checks">
        <?php foreach ($pages as $p):
            $pid = (int) $p['id'];
            $a = $assignedMap[$pid] ?? null;
            $checked = $a !== null;
            $active = $a ? (int) ($a['is_active'] ?? 1) === 1 : true;
            $feat = $a ? (int) ($a['is_featured'] ?? 0) === 1 : false;
            $sort = $a ? (int) ($a['sort_order'] ?? 0) : 0;
        ?>
          <div class="page-check">
            <label>
              <input type="checkbox" name="page_selected[]" value="<?= $pid ?>" <?= $checked ? 'checked' : '' ?>>
              <?= wa_e($p['title']) ?> <span class="muted">/<?= wa_e($p['slug']) ?></span>
            </label>
            <label>Show <input type="checkbox" name="page_active[<?= $pid ?>]" value="1" <?= $active ? 'checked' : '' ?>></label>
            <label>Featured <input type="checkbox" name="page_featured[<?= $pid ?>]" value="1" <?= $feat ? 'checked' : '' ?>></label>
            <label>Order <input type="number" name="page_sort[<?= $pid ?>]" value="<?= $sort ?>" style="width:4.5rem"></label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="actions full"><button class="btn" type="submit">Save</button></div>
  </form>
</div>
<script>
document.getElementById('addAddr')?.addEventListener('click', () => {
  const root = document.getElementById('addrRows');
  if (!root) return;
  const div = document.createElement('div');
  div.className = 'addr-row';
  div.style.cssText = 'display:grid;gap:0.75rem;grid-template-columns:1fr 2fr;margin-bottom:0.65rem';
  div.innerHTML = `<label>Label<input name="addr_label[]" value="" placeholder="Office"></label>
    <label>Address<input name="addr_text[]" value="" placeholder="Street, city, country"></label>`;
  root.appendChild(div);
});
document.getElementById('addPhone')?.addEventListener('click', () => {
  const root = document.getElementById('phoneRows');
  if (!root) return;
  const div = document.createElement('div');
  div.className = 'phone-row';
  div.style.cssText = 'display:grid;gap:0.75rem;grid-template-columns:1fr 2fr;margin-bottom:0.65rem';
  div.innerHTML = `<label>Label<input name="phone_label[]" value="" placeholder="Main"></label>
    <label>Phone<input name="phone_num[]" value="" placeholder="+1 ..."></label>`;
  root.appendChild(div);
});
</script>
<?php
wa_render($isEdit ? 'Edit contact' : 'Create contact', ob_get_clean());
