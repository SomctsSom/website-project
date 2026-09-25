<?php
declare(strict_types=1);

wa_require_perm('footer_colors.view');

$error = '';
$colors = footer_color_defaults_client();

$res = wa_api('GET', '/api/footer-colors');
if (wa_api_ok($res)) {
    $colors = array_merge($colors, $res['data'] ?? []);
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && wa_can('footer_colors.edit')) {
    $payload = [
        'bg_color' => (string) ($_POST['bg_color'] ?? $colors['bg_color']),
        'text_color' => (string) ($_POST['text_color'] ?? $colors['text_color']),
        'heading_color' => (string) ($_POST['heading_color'] ?? $colors['heading_color']),
        'accent_color' => (string) ($_POST['accent_color'] ?? $colors['accent_color']),
        'social_bg_color' => (string) ($_POST['social_bg_color'] ?? $colors['social_bg_color']),
    ];
    $save = wa_api('POST', '/api/footer-colors', $payload);
    if (wa_api_ok($save)) {
        wa_flash('success', $save['message'] ?? 'Saved');
        wa_redirect('/footer-colors');
    }
    $error = (string) ($save['message'] ?? 'Save failed');
    $colors = array_merge($colors, $payload);
}

/**
 * @return array{bg_color:string,text_color:string,heading_color:string,accent_color:string,social_bg_color:string}
 */
function footer_color_defaults_client(): array
{
    return [
        'bg_color' => '#111111',
        'text_color' => '#d8d8d8',
        'heading_color' => '#ffffff',
        'accent_color' => '#e0893a',
        'social_bg_color' => '#2a2a2a',
    ];
}

ob_start();
?>
<div class="page-head">
  <div>
    <h1>Footer Colors</h1>
    <p>Control public website footer background, text, headings, and icon colors.</p>
  </div>
  <a class="btn btn-ghost" href="<?= wa_e(wa_url('/contact-social')) ?>">Contact &amp; Social</a>
</div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="post" class="form-grid">
    <?= wa_csrf_field() ?>
    <label>Background color
      <input type="color" name="bg_color" value="<?= wa_e((string)$colors['bg_color']) ?>" <?= wa_can('footer_colors.edit') ? '' : 'disabled' ?>>
      <input type="text" value="<?= wa_e((string)$colors['bg_color']) ?>" readonly style="margin-top:0.35rem">
    </label>
    <label>Text / links color
      <input type="color" name="text_color" value="<?= wa_e((string)$colors['text_color']) ?>" <?= wa_can('footer_colors.edit') ? '' : 'disabled' ?>>
      <input type="text" value="<?= wa_e((string)$colors['text_color']) ?>" readonly style="margin-top:0.35rem">
    </label>
    <label>Heading color
      <input type="color" name="heading_color" value="<?= wa_e((string)$colors['heading_color']) ?>" <?= wa_can('footer_colors.edit') ? '' : 'disabled' ?>>
      <input type="text" value="<?= wa_e((string)$colors['heading_color']) ?>" readonly style="margin-top:0.35rem">
    </label>
    <label>Accent / icons color
      <input type="color" name="accent_color" value="<?= wa_e((string)$colors['accent_color']) ?>" <?= wa_can('footer_colors.edit') ? '' : 'disabled' ?>>
      <input type="text" value="<?= wa_e((string)$colors['accent_color']) ?>" readonly style="margin-top:0.35rem">
    </label>
    <label>Social icon circle
      <input type="color" name="social_bg_color" value="<?= wa_e((string)$colors['social_bg_color']) ?>" <?= wa_can('footer_colors.edit') ? '' : 'disabled' ?>>
      <input type="text" value="<?= wa_e((string)$colors['social_bg_color']) ?>" readonly style="margin-top:0.35rem">
    </label>

    <div class="full" style="padding:1.25rem;border-radius:12px;background:<?= wa_e((string)$colors['bg_color']) ?>;color:<?= wa_e((string)$colors['text_color']) ?>">
      <strong style="color:<?= wa_e((string)$colors['heading_color']) ?>">Footer preview</strong>
      <p style="margin:0.55rem 0;color:<?= wa_e((string)$colors['accent_color']) ?>;font-weight:700">Tagline / accent</p>
      <p style="margin:0 0 0.75rem;color:<?= wa_e((string)$colors['text_color']) ?>">Body text and menu links sample.</p>
      <div style="display:flex;gap:0.75rem;align-items:center">
        <span style="color:<?= wa_e((string)$colors['accent_color']) ?>">☎</span>
        <span>+252 61 0000000</span>
        <span style="display:inline-flex;width:2rem;height:2rem;border-radius:999px;align-items:center;justify-content:center;background:<?= wa_e((string)$colors['social_bg_color']) ?>;color:#fff">f</span>
      </div>
    </div>
    <?php if (wa_can('footer_colors.edit')): ?>
      <div class="actions full"><button class="btn" type="submit">Save colors</button></div>
    <?php endif; ?>
  </form>
</div>
<?php
wa_render('Footer Colors', ob_get_clean());
