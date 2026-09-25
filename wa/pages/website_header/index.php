<?php
declare(strict_types=1);

wa_require_perm('website_header.view');

$error = '';
$settings = website_header_defaults_client();

$res = wa_api('GET', '/api/website-header');
if (wa_api_ok($res)) {
    $settings = array_merge($settings, $res['data'] ?? []);
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && wa_can('website_header.edit')) {
    $payload = [
        'enabled' => isset($_POST['enabled']) ? 1 : 0,
        'transparent' => isset($_POST['transparent']) ? 1 : 0,
        'show_phone' => isset($_POST['show_phone']) ? 1 : 0,
        'show_email' => isset($_POST['show_email']) ? 1 : 0,
        'show_hours' => isset($_POST['show_hours']) ? 1 : 0,
        'show_social' => isset($_POST['show_social']) ? 1 : 0,
        'bg_color' => (string) ($_POST['bg_color'] ?? $settings['bg_color']),
        'text_color' => (string) ($_POST['text_color'] ?? $settings['text_color']),
        'accent_color' => (string) ($_POST['accent_color'] ?? $settings['accent_color']),
        'social_color' => (string) ($_POST['social_color'] ?? $settings['social_color']),
    ];
    $save = wa_api('POST', '/api/website-header', $payload);
    if (wa_api_ok($save)) {
        wa_flash('success', $save['message'] ?? 'Saved');
        wa_redirect('/website-header');
    }
    $error = (string) ($save['message'] ?? 'Save failed');
    $settings = array_merge($settings, $payload);
}

/**
 * @return array{
 *   enabled:int,transparent:int,show_phone:int,show_email:int,show_hours:int,show_social:int,
 *   bg_color:string,text_color:string,accent_color:string,social_color:string
 * }
 */
function website_header_defaults_client(): array
{
    return [
        'enabled' => 1,
        'transparent' => 0,
        'show_phone' => 1,
        'show_email' => 1,
        'show_hours' => 1,
        'show_social' => 1,
        'bg_color' => '#1a1a1a',
        'text_color' => '#ffffff',
        'accent_color' => '#e0893a',
        'social_color' => '#ffffff',
    ];
}

$canEdit = wa_can('website_header.edit');
$previewBg = (int) ($settings['transparent'] ?? 0) === 1
    ? 'transparent'
    : (string) $settings['bg_color'];

ob_start();
?>
<div class="page-head">
  <div>
    <h1>Website Header</h1>
    <p>Control the public top header bar. Phone, email, hours, and social icons come from Contact &amp; Social — no duplicate data.</p>
  </div>
  <a class="btn btn-ghost" href="<?= wa_e(wa_url('/contact-social')) ?>">Contact &amp; Social</a>
</div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="post" class="form-grid">
    <?= wa_csrf_field() ?>

    <div class="full">
      <strong>Visibility</strong>
    </div>
    <label class="full" style="display:flex;align-items:flex-start;gap:0.5rem">
      <input type="checkbox" name="enabled" value="1" <?= (int)$settings['enabled'] === 1 ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?> style="margin-top:0.2rem">
      <span>
        <strong>Header visibility</strong><br>
        <span class="muted">Checked = show website header on public pages. Unchecked = hide it.</span>
      </span>
    </label>

    <div class="full" style="margin-top:0.35rem">
      <strong>Display items</strong>
    </div>
    <label style="display:flex;align-items:center;gap:0.5rem">
      <input type="checkbox" name="show_phone" value="1" <?= (int)$settings['show_phone'] === 1 ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
      Show phone
    </label>
    <label style="display:flex;align-items:center;gap:0.5rem">
      <input type="checkbox" name="show_email" value="1" <?= (int)$settings['show_email'] === 1 ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
      Show email
    </label>
    <label style="display:flex;align-items:center;gap:0.5rem">
      <input type="checkbox" name="show_hours" value="1" <?= (int)$settings['show_hours'] === 1 ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
      Show working hours
    </label>
    <label style="display:flex;align-items:center;gap:0.5rem">
      <input type="checkbox" name="show_social" value="1" <?= (int)$settings['show_social'] === 1 ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
      Show social icons
    </label>

    <div class="full" style="margin-top:0.5rem">
      <strong>Colors</strong>
    </div>
    <label class="full">
      <span>Transparent header background</span>
      <input type="checkbox" name="transparent" value="1" <?= (int)$settings['transparent'] === 1 ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
      <span class="muted" style="margin-left:0.5rem">When checked, header has no background — page/hero shows through. Uncheck to use the background color below.</span>
    </label>
    <label>Background color
      <input type="color" name="bg_color" value="<?= wa_e((string)$settings['bg_color']) ?>" <?= $canEdit ? '' : 'disabled' ?>>
      <input type="text" value="<?= wa_e((string)$settings['bg_color']) ?>" readonly style="margin-top:0.35rem">
    </label>
    <label>Text color
      <input type="color" name="text_color" value="<?= wa_e((string)$settings['text_color']) ?>" <?= $canEdit ? '' : 'disabled' ?>>
      <input type="text" value="<?= wa_e((string)$settings['text_color']) ?>" readonly style="margin-top:0.35rem">
    </label>
    <label>Icon accent color
      <input type="color" name="accent_color" value="<?= wa_e((string)$settings['accent_color']) ?>" <?= $canEdit ? '' : 'disabled' ?>>
      <input type="text" value="<?= wa_e((string)$settings['accent_color']) ?>" readonly style="margin-top:0.35rem">
    </label>
    <label>Social icons color
      <input type="color" name="social_color" value="<?= wa_e((string)$settings['social_color']) ?>" <?= $canEdit ? '' : 'disabled' ?>>
      <input type="text" value="<?= wa_e((string)$settings['social_color']) ?>" readonly style="margin-top:0.35rem">
    </label>

    <div class="full" style="padding:0.85rem 1.1rem;border-radius:8px;background:<?= wa_e($previewBg) ?>;<?= (int)$settings['transparent'] === 1 ? 'background-image:linear-gradient(45deg,#eee 25%,transparent 25%),linear-gradient(-45deg,#eee 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#eee 75%),linear-gradient(-45deg,transparent 75%,#eee 75%);background-size:16px 16px;background-position:0 0,0 8px,8px -8px,-8px 0;' : '' ?>color:<?= wa_e((string)$settings['text_color']) ?>;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;border:1px solid #ddd">
      <div style="display:flex;align-items:center;gap:0.65rem;flex-wrap:wrap;font-size:0.9rem">
        <?php if ((int)$settings['show_phone'] === 1): ?>
          <span><span style="color:<?= wa_e((string)$settings['accent_color']) ?>">☎</span> +252618941010</span>
          <span style="opacity:0.45">|</span>
        <?php endif; ?>
        <?php if ((int)$settings['show_email'] === 1): ?>
          <span><span style="color:<?= wa_e((string)$settings['accent_color']) ?>">✉</span> navo@gmail.com</span>
          <span style="opacity:0.45">|</span>
        <?php endif; ?>
        <?php if ((int)$settings['show_hours'] === 1): ?>
          <span><span style="color:<?= wa_e((string)$settings['accent_color']) ?>">🕒</span> Monday - Friday (08:00AM - 06:00PM)</span>
        <?php endif; ?>
      </div>
      <?php if ((int)$settings['show_social'] === 1): ?>
        <div style="display:flex;gap:0.75rem;color:<?= wa_e((string)$settings['social_color']) ?>">
          <span>f</span><span>in</span><span>ig</span><span>✕</span>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($canEdit): ?>
      <div class="actions full"><button class="btn" type="submit">Save header settings</button></div>
    <?php endif; ?>
  </form>
</div>
<?php
wa_render('Website Header', ob_get_clean());
