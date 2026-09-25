<?php
declare(strict_types=1);

wa_require_perm('contact_social.view');

ob_start();
?>
<div class="page-head">
  <div>
    <h1>Contact &amp; Social</h1>
    <p>Manage social media links and company contact details for public pages.</p>
  </div>
</div>
<div class="panel" style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
  <a class="btn" href="<?= wa_e(wa_url('/contact-social/social')) ?>">Social media links</a>
  <a class="btn btn-secondary" href="<?= wa_e(wa_url('/contact-social/contact')) ?>">Contact address</a>
  <?php if (wa_can('website_header.view')): ?>
    <a class="btn btn-ghost" href="<?= wa_e(wa_url('/website-header')) ?>">Website Header</a>
  <?php endif; ?>
  <?php if (wa_can('footer_colors.view')): ?>
    <a class="btn btn-ghost" href="<?= wa_e(wa_url('/footer-colors')) ?>">Footer colors</a>
  <?php endif; ?>
</div>
<?php
wa_render('Contact & Social', ob_get_clean());
