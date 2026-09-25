<?php
declare(strict_types=1);

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
wa_require_perm($isEdit ? 'testimonials.edit' : 'testimonials.create');

$item = [
    'rating' => 5, 'quote' => '', 'author_name' => '', 'author_role' => '',
    'sort_order' => 0, 'is_active' => 1, 'pages' => [],
];
if ($isEdit) {
    $view = wa_api('GET', '/api/testimonials/view', ['id' => $id]);
    if (!wa_api_ok($view)) {
        wa_flash('error', $view['message'] ?? 'Not found');
        wa_redirect('/testimonials');
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
        'rating' => (int) ($_POST['rating'] ?? 5),
        'quote' => trim((string) ($_POST['quote'] ?? '')),
        'author_name' => trim((string) ($_POST['author_name'] ?? '')),
        'author_role' => trim((string) ($_POST['author_role'] ?? '')),
        'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'pages_json' => json_encode($assignments, JSON_UNESCAPED_UNICODE),
    ];
    if ($isEdit) {
        $fields['id'] = $id;
        $res = wa_api('POST', '/api/testimonials/update', $fields);
    } else {
        $res = wa_api('POST', '/api/testimonials/create', $fields);
    }
    if (wa_api_ok($res)) {
        wa_flash('success', $res['message'] ?? 'Saved');
        wa_redirect('/testimonials');
    }
    $error = (string) ($res['message'] ?? 'Save failed');
    $item = array_merge($item, $fields);
    $assignedMap = [];
    foreach ($assignments as $a) {
        $assignedMap[$a['page_id']] = $a;
    }
}

ob_start();
?>
<div class="page-head"><div><h1><?= $isEdit ? 'Edit testimonial' : 'Create testimonial' ?></h1></div>
  <a class="btn btn-ghost" href="<?= wa_e(wa_url('/testimonials')) ?>">Back</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="post" class="form-grid">
    <?= wa_csrf_field() ?>
    <label>Star rating
      <select name="rating" required>
        <?php for ($i = 5; $i >= 1; $i--): ?>
          <option value="<?= $i ?>" <?= (int)($item['rating'] ?? 5) === $i ? 'selected' : '' ?>><?= $i ?> ★</option>
        <?php endfor; ?>
      </select>
    </label>
    <label>Sort order<input type="number" name="sort_order" value="<?= (int)$item['sort_order'] ?>"></label>
    <label class="full">Quote
      <textarea name="quote" rows="4" required placeholder="Write your testimonial or customer comment here…"><?= wa_e((string)($item['quote'] ?? '')) ?></textarea>
    </label>
    <label>Author name<input name="author_name" required value="<?= wa_e((string)$item['author_name']) ?>" placeholder="Enter the customer’s full name"></label>
    <label>Author role<input name="author_role" value="<?= wa_e((string)($item['author_role'] ?? '')) ?>" placeholder="Enter role or title (e.g. Driver · Owner-Operator)"></label>
    <label><span>Active</span><input type="checkbox" name="is_active" value="1" <?= (int)$item['is_active']===1?'checked':'' ?>></label>
    <div class="full">
      <strong>Assign to website pages</strong>
      <p class="muted">Select one or many pages.</p>
      <div class="page-checks">
        <?php foreach ($pages as $p):
            $pid = (int) $p['id'];
            $a = $assignedMap[$pid] ?? null;
            $checked = $a !== null;
            $active = $a ? (int)($a['is_active'] ?? 1) === 1 : true;
            $feat = $a ? (int)($a['is_featured'] ?? 0) === 1 : false;
            $sort = $a ? (int)($a['sort_order'] ?? 0) : 0;
        ?>
          <div class="page-check">
            <label><input type="checkbox" name="page_selected[]" value="<?= $pid ?>" <?= $checked ? 'checked' : '' ?>> <?= wa_e($p['title']) ?> <span class="muted">/<?= wa_e($p['slug']) ?></span></label>
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
<?php
wa_render($isEdit ? 'Edit testimonial' : 'Create testimonial', ob_get_clean());
