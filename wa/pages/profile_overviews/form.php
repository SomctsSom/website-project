<?php
declare(strict_types=1);

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
wa_require_perm($isEdit ? 'profile_overviews.edit' : 'profile_overviews.create');

$profile = [
    'title' => '', 'body' => '', 'sort_order' => 0, 'is_active' => 1, 'pages' => [],
    'image_top_url' => null, 'image_left_url' => null, 'image_right_url' => null,
];
if ($isEdit) {
    $view = wa_api('GET', '/api/profile-overviews/view', ['id' => $id]);
    if (!wa_api_ok($view)) {
        wa_flash('error', $view['message'] ?? 'Not found');
        wa_redirect('/profile-overviews');
    }
    $profile = $view['data'];
}

$pagesRes = wa_api('GET', '/api/pages', ['page_type' => 'website', 'per_page' => 100]);
$pages = wa_api_ok($pagesRes) ? ($pagesRes['data']['items'] ?? []) : [];
$assignedMap = [];
foreach ($profile['pages'] ?? [] as $p) {
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
    $fields = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'body' => trim((string) ($_POST['body'] ?? '')),
        'sort_order' => (string) ((int) ($_POST['sort_order'] ?? 0)),
        'is_active' => isset($_POST['is_active']) ? '1' : '0',
        'pages_json' => json_encode($assignments, JSON_UNESCAPED_UNICODE),
    ];
    foreach (['top', 'left', 'right'] as $slot) {
        if (!empty($_POST['clear_image_' . $slot])) {
            $fields['clear_image_' . $slot] = '1';
        }
    }
    $files = [
        'image_top' => $_FILES['image_top'] ?? [],
        'image_left' => $_FILES['image_left'] ?? [],
        'image_right' => $_FILES['image_right'] ?? [],
    ];
    if ($isEdit) {
        $fields['id'] = (string) $id;
        $res = wa_api_multipart('/api/profile-overviews/update', $fields, $files);
    } else {
        $res = wa_api_multipart('/api/profile-overviews/create', $fields, $files);
    }
    if (wa_api_ok($res)) {
        wa_flash('success', $res['message'] ?? 'Saved');
        wa_redirect('/profile-overviews');
    }
    $error = (string) ($res['message'] ?? 'Save failed');
    $profile = array_merge($profile, [
        'title' => $fields['title'],
        'body' => $fields['body'],
        'sort_order' => (int) $fields['sort_order'],
        'is_active' => (int) $fields['is_active'],
    ]);
    $assignedMap = [];
    foreach ($assignments as $a) {
        $assignedMap[$a['page_id']] = $a;
    }
}

ob_start();
?>
<div class="page-head"><div><h1><?= $isEdit ? 'Edit profile overview' : 'Create profile overview' ?></h1></div>
  <a class="btn btn-ghost" href="<?= wa_e(wa_url('/profile-overviews')) ?>">Back</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= wa_csrf_field() ?>
    <label>Title<input name="title" required value="<?= wa_e((string)$profile['title']) ?>" placeholder="Profile Overview"></label>
    <label>Sort order<input type="number" name="sort_order" value="<?= (int)$profile['sort_order'] ?>"></label>
    <label class="full">Body paragraphs
      <textarea id="profileBody" name="body" rows="12" class="richtext-input"><?= wa_e((string)($profile['body'] ?? '')) ?></textarea>
    </label>
    <label>Top image (wide)
      <input type="file" name="image_top" accept="image/jpeg,image/png,image/webp,image/gif">
    </label>
    <label>Bottom left image
      <input type="file" name="image_left" accept="image/jpeg,image/png,image/webp,image/gif">
    </label>
    <label>Bottom right image
      <input type="file" name="image_right" accept="image/jpeg,image/png,image/webp,image/gif">
    </label>
    <label><span>Active</span><input type="checkbox" name="is_active" value="1" <?= (int)$profile['is_active']===1?'checked':'' ?>></label>
    <div class="full" style="display:flex;gap:0.75rem;flex-wrap:wrap">
      <?php foreach (['top' => 'image_top_url', 'left' => 'image_left_url', 'right' => 'image_right_url'] as $slot => $key): ?>
        <?php if (!empty($profile[$key])): ?>
          <div>
            <img class="thumb" style="width:140px;height:90px;object-fit:cover" src="<?= wa_e((string)$profile[$key]) ?>" alt="">
            <label style="display:flex;gap:0.35rem;align-items:center;margin-top:0.35rem">
              <input type="checkbox" name="clear_image_<?= wa_e($slot) ?>" value="1"> Remove <?= wa_e($slot) ?>
            </label>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <div class="full">
      <strong>Assign to website pages</strong>
      <p class="muted">Select one or many pages. Featured items sort first on that page.</p>
      <div class="page-checks">
        <?php foreach ($pages as $p):
            $pid = (int) $p['id'];
            $a = $assignedMap[$pid] ?? null;
            $checked = $a !== null;
            $active = $a ? (int)($a['is_active'] ?? 1) === 1 : true;
            $featured = $a ? (int)($a['is_featured'] ?? 0) === 1 : false;
            $sort = $a ? (int)($a['sort_order'] ?? 0) : 0;
        ?>
          <div class="page-check">
            <label><input type="checkbox" name="page_selected[]" value="<?= $pid ?>" <?= $checked ? 'checked' : '' ?>> <?= wa_e($p['title']) ?> <span class="muted">/<?= wa_e($p['slug']) ?></span></label>
            <label>Active <input type="checkbox" name="page_active[<?= $pid ?>]" value="1" <?= $active ? 'checked' : '' ?>></label>
            <label>Featured <input type="checkbox" name="page_featured[<?= $pid ?>]" value="1" <?= $featured ? 'checked' : '' ?>></label>
            <label>Order <input type="number" name="page_sort[<?= $pid ?>]" value="<?= $sort ?>" style="width:4.5rem"></label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="actions full"><button class="btn" type="submit">Save</button></div>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.0/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (!window.tinymce) {
    return;
  }
  tinymce.init({
    selector: '#profileBody',
    height: 360,
    menubar: false,
    branding: false,
    promotion: false,
    plugins: 'lists link autolink',
    toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link | removeformat',
    style_formats: [
      { title: 'Paragraph', format: 'p' },
      { title: 'Heading 2', format: 'h2' },
      { title: 'Heading 3', format: 'h3' },
      { title: 'Heading 4', format: 'h4' }
    ],
    content_style: 'body{font-family:Segoe UI,Arial,sans-serif;font-size:15px;line-height:1.6;color:#222}',
    setup: (editor) => {
      const form = document.querySelector('form.form-grid');
      if (!form) {
        return;
      }
      form.addEventListener('submit', () => {
        editor.save();
      });
    }
  });
});
</script>
<?php
wa_render($isEdit ? 'Edit profile overview' : 'Create profile overview', ob_get_clean());
