<?php
declare(strict_types=1);

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
wa_require_perm($isEdit ? 'pages.edit' : 'pages.create');

$page = [
    'page_type' => (string) ($_GET['page_type'] ?? 'website'),
    'title' => '', 'slug' => '', 'template_key' => '', 'is_active' => 1, 'menus' => [],
    'banner_eyebrow' => '', 'banner_title' => '', 'banner_subtitle' => '',
    'banner_enabled' => 1,
    'banner_size_preset' => 'md',
    'banner_image_path' => null, 'banner_image_url' => null,
    'bg_color' => null,
    'card_bg_color' => null,
];
$listMeta = wa_api('GET', '/api/pages', ['page_type' => $page['page_type'], 'per_page' => 1]);
$adminTemplates = wa_api_ok($listMeta) ? ($listMeta['data']['approved_admin_templates'] ?? []) : [];
$websiteTemplates = wa_api_ok($listMeta) ? ($listMeta['data']['approved_website_templates'] ?? []) : [];

$defaultPageBg = '#f2ebe0';
$defaultCardBg = '#ffffff';
$navColorsRes = wa_api('GET', '/api/navbar-colors');
if (wa_api_ok($navColorsRes)) {
    $defaultPageBg = (string) ($navColorsRes['data']['page_bg_color'] ?? $defaultPageBg);
}

if ($isEdit) {
    $view = wa_api('GET', '/api/pages/view', ['id' => $id]);
    if (!wa_api_ok($view)) {
        wa_flash('error', $view['message'] ?? 'Not found');
        wa_redirect('/website-pages');
    }
    $page = $view['data'];
}

$templates = $page['page_type'] === 'admin' ? $adminTemplates : $websiteTemplates;
$menusRes = wa_api('GET', '/api/menus', ['menu_type' => $page['page_type'], 'tree' => 0]);
$menus = wa_api_ok($menusRes) ? ($menusRes['data']['items'] ?? []) : [];
$assignedMenus = array_map(static fn($m) => (int) $m['id'], $page['menus'] ?? []);
$isWebsite = ($page['page_type'] ?? '') === 'website';
$sectionOrder = $isWebsite ? ($page['section_order'] ?? []) : [];
if ($isWebsite && !$sectionOrder) {
    $sectionOrder = [
        ['section_key' => 'services', 'label' => 'Services', 'sort_order' => 10, 'is_enabled' => 1],
        ['section_key' => 'testimonials', 'label' => 'Testimonials', 'sort_order' => 20, 'is_enabled' => 1],
        ['section_key' => 'contact_social', 'label' => 'Contact & Social', 'sort_order' => 25, 'is_enabled' => 1],
        ['section_key' => 'contact_form', 'label' => 'Contact Form', 'sort_order' => 28, 'is_enabled' => 0],
        ['section_key' => 'profile_overviews', 'label' => 'Profile Overview', 'sort_order' => 30, 'is_enabled' => 1],
        ['section_key' => 'vision_missions', 'label' => 'Vision & Mission', 'sort_order' => 40, 'is_enabled' => 1],
        ['section_key' => 'features', 'label' => 'Features', 'sort_order' => 50, 'is_enabled' => 1],
    ];
}

$error = '';
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $menuIds = array_map('intval', $_POST['menu_ids'] ?? []);
    $fields = [
        'page_type' => (string) $page['page_type'],
        'title' => trim((string) ($_POST['title'] ?? '')),
        'slug' => trim((string) ($_POST['slug'] ?? '')),
        'template_key' => trim((string) ($_POST['template_key'] ?? ($isWebsite ? 'default' : ''))),
        'is_active' => isset($_POST['is_active']) ? '1' : '0',
        'menu_ids' => json_encode($menuIds, JSON_UNESCAPED_UNICODE),
    ];
    if ($isWebsite) {
        $fields['banner_enabled'] = isset($_POST['banner_enabled']) ? '1' : '0';
        $fields['banner_size_preset'] = trim((string) ($_POST['banner_size_preset'] ?? 'md'));
        $fields['banner_eyebrow'] = trim((string) ($_POST['banner_eyebrow'] ?? ''));
        $fields['banner_title'] = trim((string) ($_POST['banner_title'] ?? ''));
        $fields['banner_subtitle'] = trim((string) ($_POST['banner_subtitle'] ?? ''));
        if (!empty($_POST['clear_banner_image'])) {
            $fields['clear_banner_image'] = '1';
        }
        if (!empty($_POST['clear_bg_color'])) {
            $fields['clear_bg_color'] = '1';
            $fields['bg_color'] = '';
        } else {
            $fields['bg_color'] = trim((string) ($_POST['bg_color'] ?? ''));
        }
        if (!empty($_POST['clear_card_bg_color'])) {
            $fields['clear_card_bg_color'] = '1';
            $fields['card_bg_color'] = '';
        } else {
            $fields['card_bg_color'] = trim((string) ($_POST['card_bg_color'] ?? ''));
        }
        $secKeys = $_POST['section_key'] ?? [];
        $secEnabled = $_POST['section_enabled'] ?? [];
        $sectionsPayload = [];
        if (is_array($secKeys)) {
            foreach ($secKeys as $i => $key) {
                $key = trim((string) $key);
                if ($key === '') {
                    continue;
                }
                $sectionsPayload[] = [
                    'section_key' => $key,
                    'sort_order' => ((int) $i + 1) * 10,
                    'is_enabled' => isset($secEnabled[$i]) ? 1 : 0,
                ];
            }
        }
        $fields['sections_json'] = json_encode($sectionsPayload, JSON_UNESCAPED_UNICODE);
    }
    if ($isEdit) {
        $fields['id'] = (string) $id;
        if ($isWebsite) {
            $res = wa_api_multipart('/api/pages/update', $fields, $_FILES['banner_image'] ?? [], 'banner_image');
        } else {
            $res = wa_api('POST', '/api/pages/update', array_merge($fields, ['menu_ids' => $menuIds, 'id' => $id, 'is_active' => (int) $fields['is_active']]));
        }
    } else {
        if ($isWebsite) {
            $res = wa_api_multipart('/api/pages/create', $fields, $_FILES['banner_image'] ?? [], 'banner_image');
        } else {
            $res = wa_api('POST', '/api/pages/create', array_merge($fields, ['menu_ids' => $menuIds, 'is_active' => (int) $fields['is_active']]));
        }
    }
    if (wa_api_ok($res)) {
        wa_flash('success', 'Page saved');
        wa_redirect($page['page_type'] === 'admin' ? '/admin-pages' : '/website-pages');
    }
    $error = (string) ($res['message'] ?? 'Save failed');
    $page = array_merge($page, [
        'title' => $fields['title'],
        'slug' => $fields['slug'],
        'template_key' => $fields['template_key'],
        'is_active' => (int) $fields['is_active'],
        'banner_enabled' => (int) ($fields['banner_enabled'] ?? ($page['banner_enabled'] ?? 1)),
        'banner_size_preset' => $fields['banner_size_preset'] ?? ($page['banner_size_preset'] ?? 'md'),
        'banner_eyebrow' => $fields['banner_eyebrow'] ?? ($page['banner_eyebrow'] ?? ''),
        'banner_title' => $fields['banner_title'] ?? ($page['banner_title'] ?? ''),
        'banner_subtitle' => $fields['banner_subtitle'] ?? ($page['banner_subtitle'] ?? ''),
        'bg_color' => !empty($fields['clear_bg_color']) ? null : ($fields['bg_color'] ?? ($page['bg_color'] ?? null)),
        'card_bg_color' => !empty($fields['clear_card_bg_color']) ? null : ($fields['card_bg_color'] ?? ($page['card_bg_color'] ?? null)),
    ]);
    $assignedMenus = $menuIds;
    if ($isWebsite && !empty($sectionsPayload)) {
        $labels = [];
        foreach ($sectionOrder as $s) {
            $labels[(string) $s['section_key']] = (string) ($s['label'] ?? $s['section_key']);
        }
        $sectionOrder = [];
        foreach ($sectionsPayload as $s) {
            $sectionOrder[] = [
                'section_key' => $s['section_key'],
                'label' => $labels[$s['section_key']] ?? $s['section_key'],
                'sort_order' => $s['sort_order'],
                'is_enabled' => $s['is_enabled'],
            ];
        }
    }
}

$list = $page['page_type'] === 'admin' ? '/admin-pages' : '/website-pages';
ob_start();
?>
<div class="page-head"><div><h1><?= $isEdit ? 'Edit page' : 'Create page' ?></h1></div>
  <a class="btn btn-ghost" href="<?= wa_e(wa_url($list)) ?>">Back</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="post" class="form-grid"<?= $isWebsite ? ' enctype="multipart/form-data"' : '' ?>>
    <?= wa_csrf_field() ?>
    <label>Title<input name="title" required value="<?= wa_e((string)$page['title']) ?>"></label>
    <label>Slug<input name="slug" required value="<?= wa_e((string)$page['slug']) ?>"></label>
    <label>Template key
      <select name="template_key" required>
        <?php foreach ($templates as $tk): ?>
          <option value="<?= wa_e($tk) ?>" <?= ($page['template_key'] ?? '') === $tk ? 'selected' : '' ?>><?= wa_e($tk) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label><span>Active</span><input type="checkbox" name="is_active" value="1" <?= (int)$page['is_active']===1?'checked':'' ?>></label>

    <?php if ($isWebsite): ?>
    <div class="full" style="border-top:1px solid #e5e7eb;padding-top:1rem;margin-top:0.25rem">
      <strong>Page colors</strong>
      <p class="muted" style="margin:0.35rem 0 0.75rem">
        Body and card backgrounds for this page. Section bands can still use their own section styles.
        Check “Use default” to fall back to the built-in colors.
      </p>
    </div>
    <?php
      $pageBgValue = trim((string) ($page['bg_color'] ?? ''));
      $pageBgPicker = $pageBgValue !== '' ? $pageBgValue : $defaultPageBg;
      if (!preg_match('/^#[0-9a-fA-F]{6}$/', $pageBgPicker)) {
          $pageBgPicker = $defaultPageBg;
      }
      $usingDefault = $pageBgValue === '';

      $cardBgValue = trim((string) ($page['card_bg_color'] ?? ''));
      $cardBgPicker = $cardBgValue !== '' ? $cardBgValue : $defaultCardBg;
      if (!preg_match('/^#[0-9a-fA-F]{6}$/', $cardBgPicker)) {
          $cardBgPicker = $defaultCardBg;
      }
      $usingCardDefault = $cardBgValue === '';
    ?>
    <label>Page background
      <input type="color" name="bg_color" id="pageBgColor" value="<?= wa_e($pageBgPicker) ?>" data-default="<?= wa_e($defaultPageBg) ?>">
      <input type="text" id="pageBgColorText" value="<?= wa_e($pageBgPicker) ?>" maxlength="7" pattern="#[0-9A-Fa-f]{6}" style="margin-top:0.35rem;max-width:8rem" autocomplete="off">
    </label>
    <label style="display:flex;align-items:flex-end;gap:0.5rem">
      <span style="display:inline-flex;align-items:center;gap:0.4rem">
        <input type="checkbox" name="clear_bg_color" value="1" id="clearPageBg" <?= $usingDefault ? 'checked' : '' ?>>
        Use default
      </span>
    </label>
    <div class="full" id="pageBgPreview" style="padding:0.85rem 1rem;border:1px solid #ddd;border-radius:10px;background:<?= wa_e($pageBgPicker) ?>">
      <strong>Page preview</strong>
      <span class="muted" id="pageBgPreviewLabel" style="margin-left:0.5rem"><?= $usingDefault ? 'Default (' . wa_e($defaultPageBg) . ')' : wa_e($pageBgValue) ?></span>
    </div>

    <label>Cards background
      <input type="color" name="card_bg_color" id="cardBgColor" value="<?= wa_e($cardBgPicker) ?>" data-default="<?= wa_e($defaultCardBg) ?>">
      <input type="text" id="cardBgColorText" value="<?= wa_e($cardBgPicker) ?>" maxlength="7" pattern="#[0-9A-Fa-f]{6}" style="margin-top:0.35rem;max-width:8rem" autocomplete="off">
    </label>
    <label style="display:flex;align-items:flex-end;gap:0.5rem">
      <span style="display:inline-flex;align-items:center;gap:0.4rem">
        <input type="checkbox" name="clear_card_bg_color" value="1" id="clearCardBg" <?= $usingCardDefault ? 'checked' : '' ?>>
        Use default
      </span>
    </label>
    <div class="full" id="cardBgPreview" style="padding:0.85rem 1rem;border:1px solid #ddd;border-radius:10px;background:<?= wa_e($cardBgPicker) ?>">
      <strong>Cards preview</strong>
      <span class="muted" id="cardBgPreviewLabel" style="margin-left:0.5rem"><?= $usingCardDefault ? 'Default (' . wa_e($defaultCardBg) . ')' : wa_e($cardBgValue) ?></span>
    </div>

    <div class="full" style="border-top:1px solid #e5e7eb;padding-top:1rem;margin-top:0.25rem">
      <strong>Page banner</strong>
      <p class="muted" style="margin:0.35rem 0 0.75rem">When enabled, shown on the public site if this page has no hero slider. Background should be an image.</p>
    </div>
    <label><span>Show banner</span><input type="checkbox" name="banner_enabled" value="1" <?= (int)($page['banner_enabled'] ?? 1) === 1 ? 'checked' : '' ?>></label>
    <label>Banner size
      <?php $bannerSize = (string) ($page['banner_size_preset'] ?? 'md'); ?>
      <select name="banner_size_preset">
        <option value="sm" <?= $bannerSize === 'sm' ? 'selected' : '' ?>>Small</option>
        <option value="md" <?= $bannerSize === 'md' ? 'selected' : '' ?>>Medium</option>
        <option value="lg" <?= $bannerSize === 'lg' ? 'selected' : '' ?>>Large</option>
        <option value="xl" <?= $bannerSize === 'xl' ? 'selected' : '' ?>>Extra large</option>
      </select>
    </label>
    <label>Banner eyebrow<input name="banner_eyebrow" value="<?= wa_e((string)($page['banner_eyebrow'] ?? '')) ?>" placeholder="PROFESSIONAL DISPATCHING"></label>
    <label>Banner title<input name="banner_title" value="<?= wa_e((string)($page['banner_title'] ?? '')) ?>" placeholder="About Navo Dispatch LLC"></label>
    <label class="full">Banner subtitle<input name="banner_subtitle" value="<?= wa_e((string)($page['banner_subtitle'] ?? '')) ?>" placeholder="Professional Truck & NEMT Dispatching Services"></label>
    <label class="full">Banner background image<?= $isEdit ? ' (optional replace)' : '' ?>
      <input type="file" name="banner_image" accept="image/jpeg,image/png,image/webp,image/gif">
    </label>
    <?php if (!empty($page['banner_image_url'])): ?>
      <div class="full">
        <img class="thumb" style="width:220px;height:80px;object-fit:cover;border-radius:8px" src="<?= wa_e((string)$page['banner_image_url']) ?>" alt="">
        <label style="display:inline-flex;gap:0.4rem;align-items:center;margin-left:0.75rem">
          <input type="checkbox" name="clear_banner_image" value="1"> Remove image
        </label>
      </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($isWebsite && $isEdit): ?>
    <div class="full" style="border-top:1px solid #e5e7eb;padding-top:1rem;margin-top:0.5rem">
      <strong>Content section order</strong>
      <p class="muted" style="margin:0.35rem 0 0.75rem">
        Hero (and page banner when there is no hero) always stay at the top.
        Reorder the parts below for this page. New modules appear here automatically.
      </p>
      <div id="sectionOrderRows" class="page-checks">
        <?php foreach ($sectionOrder as $idx => $sec): ?>
          <div class="page-check section-order-row" style="grid-template-columns:1fr auto auto auto;align-items:center">
            <input type="hidden" name="section_key[]" value="<?= wa_e((string)$sec['section_key']) ?>">
            <strong><?= wa_e((string)($sec['label'] ?? $sec['section_key'])) ?></strong>
            <label>Show <input type="checkbox" name="section_enabled[<?= (int)$idx ?>]" value="1" <?= (int)($sec['is_enabled'] ?? 1) === 1 ? 'checked' : '' ?>></label>
            <button type="button" class="btn btn-ghost section-up" style="padding:0.25rem 0.55rem">Up</button>
            <button type="button" class="btn btn-ghost section-down" style="padding:0.25rem 0.55rem">Down</button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="full">
      <strong>Assign to menus (optional)</strong>
      <div class="page-checks">
        <?php foreach ($menus as $m): ?>
          <label class="page-check" style="grid-template-columns:auto 1fr">
            <input type="checkbox" name="menu_ids[]" value="<?= (int)$m['id'] ?>" <?= in_array((int)$m['id'], $assignedMenus, true) ? 'checked' : '' ?>>
            <span><?= wa_e($m['title']) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="actions full"><button class="btn" type="submit">Save</button></div>
  </form>
</div>
<?php if ($isWebsite): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const isHex = (v) => /^#[0-9a-fA-F]{6}$/.test(v);

  const bindColorField = ({ colorId, textId, clearId, previewId, labelId, defaultLabel }) => {
    const color = document.getElementById(colorId);
    const text = document.getElementById(textId);
    const clear = document.getElementById(clearId);
    const preview = document.getElementById(previewId);
    const previewLabel = document.getElementById(labelId);
    if (!color) return;
    const siteDefault = color.getAttribute('data-default') || '#ffffff';

    const paint = (hex, usingDefault) => {
      if (preview) preview.style.background = hex;
      if (previewLabel) {
        previewLabel.textContent = usingDefault
          ? (defaultLabel + ' (' + siteDefault + ')')
          : hex;
      }
    };

    const applyCustom = (hex) => {
      if (!isHex(hex)) return;
      const v = hex.toLowerCase();
      color.value = v;
      if (text) text.value = v;
      if (clear) clear.checked = false;
      paint(v, false);
    };

    color.addEventListener('input', () => applyCustom(color.value));
    color.addEventListener('click', () => {
      if (clear && clear.checked) clear.checked = false;
    });

    if (text) {
      text.addEventListener('input', () => {
        let v = text.value.trim();
        if (v && v[0] !== '#') v = '#' + v;
        text.value = v;
        if (isHex(v)) applyCustom(v);
      });
    }

    if (clear) {
      clear.addEventListener('change', () => {
        if (clear.checked) {
          color.value = siteDefault;
          if (text) text.value = siteDefault;
          paint(siteDefault, true);
        } else {
          applyCustom(color.value);
        }
      });
    }
  };

  bindColorField({
    colorId: 'pageBgColor',
    textId: 'pageBgColorText',
    clearId: 'clearPageBg',
    previewId: 'pageBgPreview',
    labelId: 'pageBgPreviewLabel',
    defaultLabel: 'Default',
  });
  bindColorField({
    colorId: 'cardBgColor',
    textId: 'cardBgColorText',
    clearId: 'clearCardBg',
    previewId: 'cardBgPreview',
    labelId: 'cardBgPreviewLabel',
    defaultLabel: 'Default',
  });

  const root = document.getElementById('sectionOrderRows');
  if (root) {
    const reindex = () => {
      root.querySelectorAll('.section-order-row').forEach((row, idx) => {
        const box = row.querySelector('input[type="checkbox"]');
        if (box) box.name = 'section_enabled[' + idx + ']';
      });
    };
    root.addEventListener('click', (e) => {
      const t = e.target;
      if (!(t instanceof HTMLElement)) return;
      const row = t.closest('.section-order-row');
      if (!row) return;
      if (t.classList.contains('section-up') && row.previousElementSibling) {
        root.insertBefore(row, row.previousElementSibling);
        reindex();
      }
      if (t.classList.contains('section-down') && row.nextElementSibling) {
        root.insertBefore(row.nextElementSibling, row);
        reindex();
      }
    });
  }
});
</script>
<?php endif; ?>
<?php
wa_render($isEdit ? 'Edit page' : 'Create page', ob_get_clean());
