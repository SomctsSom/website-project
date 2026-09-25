<?php
declare(strict_types=1);

wa_require_perm('navbar_colors.view');

$error = '';
$colors = [
    'transparent' => 0,
    'bg_color' => '#12352e',
    'menu_color' => '#ffffff',
    'active_color' => '#e0893a',
    'page_bg_color' => '#f2ebe0',
];

$res = wa_api('GET', '/api/navbar-colors');
if (wa_api_ok($res)) {
    $colors = array_merge($colors, $res['data'] ?? []);
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && wa_can('navbar_colors.edit')) {
    $payload = [
        'transparent' => isset($_POST['transparent']) ? 1 : 0,
        'bg_color' => (string) ($_POST['bg_color'] ?? $colors['bg_color']),
        'menu_color' => (string) ($_POST['menu_color'] ?? $colors['menu_color']),
        'active_color' => (string) ($_POST['active_color'] ?? $colors['active_color']),
        'page_bg_color' => (string) ($_POST['page_bg_color'] ?? $colors['page_bg_color']),
    ];
    $save = wa_api('POST', '/api/navbar-colors', $payload);
    if (wa_api_ok($save)) {
        wa_flash('success', $save['message'] ?? 'Saved');
        wa_redirect('/navbar-colors');
    }
    $error = (string) ($save['message'] ?? 'Save failed');
    $colors = array_merge($colors, $payload);
}

ob_start();
?>
<div class="page-head">
  <div>
    <h1>Navbar Colors</h1>
    <p>Control public website navbar colors and the default page background.</p>
  </div>
</div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="post" class="form-grid">
    <?= wa_csrf_field() ?>
    <label class="full">
      <span>Transparent navbar background</span>
      <input type="checkbox" name="transparent" value="1" <?= (int)$colors['transparent'] === 1 ? 'checked' : '' ?> <?= wa_can('navbar_colors.edit') ? '' : 'disabled' ?>>
      <span class="muted" style="margin-left:0.5rem">When checked, navbar has no background — hero/page image shows through.</span>
    </label>
    <label>Navbar background color
      <input type="color" name="bg_color" value="<?= wa_e((string)$colors['bg_color']) ?>" <?= wa_can('navbar_colors.edit') ? '' : 'disabled' ?>>
      <input type="text" value="<?= wa_e((string)$colors['bg_color']) ?>" readonly style="margin-top:0.35rem">
    </label>
    <label>Menu name color
      <input type="color" name="menu_color" value="<?= wa_e((string)$colors['menu_color']) ?>" <?= wa_can('navbar_colors.edit') ? '' : 'disabled' ?>>
      <input type="text" value="<?= wa_e((string)$colors['menu_color']) ?>" readonly style="margin-top:0.35rem">
    </label>
    <label>Selected / active menu color
      <input type="color" name="active_color" value="<?= wa_e((string)$colors['active_color']) ?>" <?= wa_can('navbar_colors.edit') ? '' : 'disabled' ?>>
      <input type="text" value="<?= wa_e((string)$colors['active_color']) ?>" readonly style="margin-top:0.35rem">
    </label>
    <label class="full">Default page background color
      <input type="color" name="page_bg_color" value="<?= wa_e((string)$colors['page_bg_color']) ?>" <?= wa_can('navbar_colors.edit') ? '' : 'disabled' ?>>
      <input type="text" value="<?= wa_e((string)$colors['page_bg_color']) ?>" readonly style="margin-top:0.35rem;max-width:8rem">
      <span class="muted" style="display:block;margin-top:0.35rem">
        Used when a page has no own color. Set a color per page under Website pages → Edit page → Page background color.
      </span>
    </label>
    <div class="full" style="padding:1rem;border:1px solid #ddd;border-radius:10px;background:<?= (int)$colors['transparent']===1 ? 'url(data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect width="20" height="20" fill="#ccc"/><rect x="20" y="20" width="20" height="20" fill="#ccc"/><rect x="20" width="20" height="20" fill="#eee"/><rect y="20" width="20" height="20" fill="#eee"/></svg>') . ') center/20px 20px' : wa_e((string)$colors['bg_color']) ?>;color:<?= wa_e((string)$colors['menu_color']) ?>">
      <strong>Navbar preview<?= (int)$colors['transparent']===1 ? ' (transparent)' : '' ?></strong>
      <div style="display:flex;gap:1.25rem;margin-top:0.75rem;font-weight:600">
        <span style="color:<?= wa_e((string)$colors['active_color']) ?>;border-bottom:2px solid <?= wa_e((string)$colors['active_color']) ?>">Home</span>
        <span>About</span>
        <span>Services</span>
      </div>
    </div>
    <div class="full" style="padding:1rem;border:1px solid #ddd;border-radius:10px;background:<?= wa_e((string)$colors['page_bg_color']) ?>">
      <strong>Default page background preview</strong>
      <p class="muted" style="margin:0.5rem 0 0">Fallback body color for pages that use “site default”.</p>
    </div>
    <?php if (wa_can('navbar_colors.edit')): ?>
      <div class="actions full"><button class="btn" type="submit">Save colors</button></div>
    <?php endif; ?>
  </form>
</div>
<?php
wa_render('Navbar Colors', ob_get_clean());
