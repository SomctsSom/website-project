<?php
declare(strict_types=1);

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
wa_require_perm($isEdit ? 'vision_missions.edit' : 'vision_missions.create');

$item = [
    'title' => '', 'body' => '', 'statement_type' => 'vision',
    'sort_order' => 0, 'is_active' => 1, 'pages' => [],
    'image_url' => null,
];
if ($isEdit) {
    $view = wa_api('GET', '/api/vision-missions/view', ['id' => $id]);
    if (!wa_api_ok($view)) {
        wa_flash('error', $view['message'] ?? 'Not found');
        wa_redirect('/vision-missions');
    }
    $item = $view['data'];
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
    $fields = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'body' => trim((string) ($_POST['body'] ?? '')),
        'statement_type' => (string) ($_POST['statement_type'] ?? 'vision'),
        'sort_order' => (string) ((int) ($_POST['sort_order'] ?? 0)),
        'is_active' => isset($_POST['is_active']) ? '1' : '0',
        'pages_json' => json_encode($assignments, JSON_UNESCAPED_UNICODE),
    ];
    if (!empty($_POST['clear_image'])) {
        $fields['clear_image'] = '1';
    }
    $files = [
        'image' => $_FILES['image'] ?? [],
    ];
    if ($isEdit) {
        $fields['id'] = (string) $id;
        $res = wa_api_multipart('/api/vision-missions/update', $fields, $files);
    } else {
        $res = wa_api_multipart('/api/vision-missions/create', $fields, $files);
    }
    if (wa_api_ok($res)) {
        wa_flash('success', $res['message'] ?? 'Saved');
        wa_redirect('/vision-missions');
    }
    $error = (string) ($res['message'] ?? 'Save failed');
    $item = array_merge($item, [
        'title' => $fields['title'],
        'body' => $fields['body'],
        'statement_type' => $fields['statement_type'],
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
<div class="page-head"><div><h1><?= $isEdit ? 'Edit Vision &amp; Mission' : 'Create Vision &amp; Mission' ?></h1></div>
  <a class="btn btn-ghost" href="<?= wa_e(wa_url('/vision-missions')) ?>">Back</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= wa_csrf_field() ?>
    <label>Type
      <select name="statement_type" required>
        <option value="vision" <?= ($item['statement_type'] ?? '') === 'vision' ? 'selected' : '' ?>>Vision</option>
        <option value="mission" <?= ($item['statement_type'] ?? '') === 'mission' ? 'selected' : '' ?>>Mission</option>
      </select>
    </label>
    <label>Title<input name="title" required value="<?= wa_e((string)$item['title']) ?>" placeholder="Our Vision"></label>
    <label>Sort order<input type="number" name="sort_order" value="<?= (int)$item['sort_order'] ?>"></label>
    <label class="full">Body
      <textarea id="visionBody" name="body" rows="12" class="richtext-input"><?= wa_e((string)($item['body'] ?? '')) ?></textarea>
    </label>
    <label>Image (optional)
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
    </label>
    <label><span>Active</span><input type="checkbox" name="is_active" value="1" <?= (int)$item['is_active']===1?'checked':'' ?>></label>
    <?php if (!empty($item['image_url'])): ?>
      <div class="full">
        <img class="thumb" style="width:180px;height:120px;object-fit:cover" src="<?= wa_e((string)$item['image_url']) ?>" alt="">
        <label style="display:flex;gap:0.35rem;align-items:center;margin-top:0.35rem">
          <input type="checkbox" name="clear_image" value="1"> Remove image
        </label>
      </div>
    <?php endif; ?>
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
    selector: '#visionBody',
    height: 320,
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
wa_render($isEdit ? 'Edit Vision & Mission' : 'Create Vision & Mission', ob_get_clean());
