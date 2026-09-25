<?php
declare(strict_types=1);

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
wa_require_perm($isEdit ? 'contact_social.edit' : 'contact_social.create');

$item = [
    'icon_class' => 'fab fa-facebook-f',
    'link_url' => '',
    'label' => '',
    'sort_order' => 0,
    'is_visible' => 1,
    'is_active' => 1,
    'pages' => [],
];
$rows = [
    ['icon_class' => 'fab fa-facebook-f', 'link_url' => '', 'label' => 'Facebook', 'is_visible' => 1],
    ['icon_class' => 'fab fa-twitter', 'link_url' => '', 'label' => 'Twitter', 'is_visible' => 1],
    ['icon_class' => 'fab fa-linkedin-in', 'link_url' => '', 'label' => 'LinkedIn', 'is_visible' => 1],
];

if ($isEdit) {
    $view = wa_api('GET', '/api/social-links/view', ['id' => $id]);
    if (!wa_api_ok($view)) {
        wa_flash('error', $view['message'] ?? 'Not found');
        wa_redirect('/contact-social/social');
    }
    $item = $view['data'];
    $rows = [[
        'icon_class' => $item['icon_class'] ?? 'fab fa-link',
        'link_url' => $item['link_url'] ?? '',
        'label' => $item['label'] ?? '',
        'is_visible' => (int) ($item['is_visible'] ?? 1),
    ]];
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

    if ($isEdit) {
        $fields = [
            'id' => $id,
            'icon_class' => trim((string) ($_POST['icon_class'][0] ?? '')),
            'link_url' => trim((string) ($_POST['link_url'][0] ?? '')),
            'label' => trim((string) ($_POST['label'][0] ?? '')),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_visible' => isset($_POST['is_visible'][0]) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'pages_json' => json_encode($assignments, JSON_UNESCAPED_UNICODE),
        ];
        $res = wa_api('POST', '/api/social-links/update', $fields);
    } else {
        $bulk = [];
        $icons = $_POST['icon_class'] ?? [];
        $urls = $_POST['link_url'] ?? [];
        $labels = $_POST['label'] ?? [];
        $vis = $_POST['is_visible'] ?? [];
        if (is_array($urls)) {
            foreach ($urls as $i => $url) {
                $url = trim((string) $url);
                if ($url === '') {
                    continue;
                }
                $bulk[] = [
                    'icon_class' => trim((string) ($icons[$i] ?? 'fab fa-link')),
                    'link_url' => $url,
                    'label' => trim((string) ($labels[$i] ?? '')),
                    'is_visible' => isset($vis[$i]) ? 1 : 0,
                    'sort_order' => ((int) $i + 1) * 10,
                    'is_active' => 1,
                ];
            }
        }
        $fields = [
            'items_json' => json_encode($bulk, JSON_UNESCAPED_UNICODE),
            'pages_json' => json_encode($assignments, JSON_UNESCAPED_UNICODE),
        ];
        $res = wa_api('POST', '/api/social-links/create', $fields);
        $rows = $bulk ?: $rows;
    }

    if (wa_api_ok($res)) {
        wa_flash('success', $res['message'] ?? 'Saved');
        wa_redirect('/contact-social/social');
    }
    $error = (string) ($res['message'] ?? 'Save failed');
    $assignedMap = [];
    foreach ($assignments as $a) {
        $assignedMap[$a['page_id']] = $a;
    }
}

ob_start();
?>
<div class="page-head">
  <div><h1><?= $isEdit ? 'Edit social link' : 'Add social links' ?></h1></div>
  <a class="btn btn-ghost" href="<?= wa_e(wa_url('/contact-social/social')) ?>">Back</a>
</div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="post" class="form-grid" id="socialBulkForm">
    <?= wa_csrf_field() ?>
    <?php if ($isEdit): ?>
      <label>Sort order<input type="number" name="sort_order" value="<?= (int) ($item['sort_order'] ?? 0) ?>"></label>
      <label><span>Active</span><input type="checkbox" name="is_active" value="1" <?= (int) ($item['is_active'] ?? 1) === 1 ? 'checked' : '' ?>></label>
    <?php else: ?>
      <p class="full muted">Fill one or more rows. Empty URL rows are skipped. All new links share the same page assignment below.</p>
    <?php endif; ?>

    <div class="full" id="socialRows">
      <?php foreach ($rows as $i => $row): ?>
        <div class="social-row" style="display:grid;gap:0.75rem;grid-template-columns:1.2fr 1.6fr 1fr auto;align-items:end;margin-bottom:0.75rem;padding-bottom:0.75rem;border-bottom:1px solid #eee">
          <label>Icon class<input name="icon_class[]" value="<?= wa_e((string) ($row['icon_class'] ?? '')) ?>" placeholder="fab fa-facebook-f"></label>
          <label>Link URL<input name="link_url[]" value="<?= wa_e((string) ($row['link_url'] ?? '')) ?>" placeholder="https://..." <?= $isEdit ? 'required' : '' ?>></label>
          <label>Label<input name="label[]" value="<?= wa_e((string) ($row['label'] ?? '')) ?>" placeholder="Facebook"></label>
          <label style="padding-bottom:0.55rem">Visible <input type="checkbox" name="is_visible[<?= (int) $i ?>]" value="1" <?= (int) ($row['is_visible'] ?? 1) === 1 ? 'checked' : '' ?>></label>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if (!$isEdit): ?>
      <div class="full"><button type="button" class="btn btn-ghost" id="addSocialRow">+ Add another row</button></div>
    <?php endif; ?>

    <div class="full">
      <strong>Assign to website pages</strong>
      <div class="page-checks">
        <?php foreach ($pages as $p):
            $pid = (int) $p['id'];
            $a = $assignedMap[$pid] ?? null;
            $checked = $a !== null || (!$isEdit && ($p['slug'] ?? '') === 'home');
            $active = $a ? (int) ($a['is_active'] ?? 1) === 1 : true;
            $feat = $a ? (int) ($a['is_featured'] ?? 0) === 1 : false;
            $sort = $a ? (int) ($a['sort_order'] ?? 0) : 0;
        ?>
          <div class="page-check">
            <label><input type="checkbox" name="page_selected[]" value="<?= $pid ?>" <?= $checked ? 'checked' : '' ?>> <?= wa_e($p['title']) ?></label>
            <label>Active <input type="checkbox" name="page_active[<?= $pid ?>]" value="1" <?= $active ? 'checked' : '' ?>></label>
            <label>Featured <input type="checkbox" name="page_featured[<?= $pid ?>]" value="1" <?= $feat ? 'checked' : '' ?>></label>
            <label>Order <input type="number" name="page_sort[<?= $pid ?>]" value="<?= $sort ?>" style="width:4.5rem"></label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="actions full"><button class="btn" type="submit">Save</button></div>
  </form>
</div>
<?php if (!$isEdit): ?>
<script>
document.getElementById('addSocialRow')?.addEventListener('click', () => {
  const root = document.getElementById('socialRows');
  if (!root) return;
  const idx = root.querySelectorAll('.social-row').length;
  const div = document.createElement('div');
  div.className = 'social-row';
  div.style.cssText = 'display:grid;gap:0.75rem;grid-template-columns:1.2fr 1.6fr 1fr auto;align-items:end;margin-bottom:0.75rem;padding-bottom:0.75rem;border-bottom:1px solid #eee';
  div.innerHTML = `
    <label>Icon class<input name="icon_class[]" value="fab fa-instagram" placeholder="fab fa-instagram"></label>
    <label>Link URL<input name="link_url[]" value="" placeholder="https://..."></label>
    <label>Label<input name="label[]" value="" placeholder="Instagram"></label>
    <label style="padding-bottom:0.55rem">Visible <input type="checkbox" name="is_visible[${idx}]" value="1" checked></label>`;
  root.appendChild(div);
});
</script>
<?php endif; ?>
<?php
wa_render($isEdit ? 'Edit social link' : 'Add social links', ob_get_clean());
