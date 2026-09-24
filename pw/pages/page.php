<?php
declare(strict_types=1);

/** @var string $slug */

$menusRes = pw_api('/api/public/menus', ['menu_type' => 'website']);
$menus = !empty($menusRes['success']) ? ($menusRes['data']['items'] ?? []) : [];

$navColorsRes = pw_api('/api/public/navbar-colors');
$navColors = !empty($navColorsRes['success']) ? ($navColorsRes['data'] ?? []) : [];
$navTransparent = (int) ($navColors['transparent'] ?? 0) === 1;
$navBg = (string) ($navColors['bg_color'] ?? '#12352e');
$navMenu = (string) ($navColors['menu_color'] ?? '#ffffff');
$navActive = (string) ($navColors['active_color'] ?? '#e0893a');
$pageBg = (string) ($navColors['page_bg_color'] ?? '#f2ebe0');
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $navBg)) {
    $navBg = '#12352e';
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $navMenu)) {
    $navMenu = '#ffffff';
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $navActive)) {
    $navActive = '#e0893a';
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $pageBg)) {
    $pageBg = '#f2ebe0';
}

$pageRes = pw_api('/api/public/page', ['slug' => $slug]);
$heroRes = null;
if (empty($pageRes['success'])) {
    http_response_code(404);
    $page = ['title' => 'Page not found', 'slug' => $slug, 'template_key' => 'default'];
    $heroes = [];
    $services = [];
    $profiles = [];
    $visionMissions = [];
    $features = [];
} else {
    $page = $pageRes['data'];
    $heroRes = pw_api('/api/public/hero', ['page_id' => $page['id']]);
    $heroes = !empty($heroRes['success']) ? ($heroRes['data']['items'] ?? []) : [];
    $servicesRes = pw_api('/api/public/services', ['page_id' => $page['id']]);
    $services = !empty($servicesRes['success']) ? ($servicesRes['data']['items'] ?? []) : [];
    $profilesRes = pw_api('/api/public/profile-overviews', ['page_id' => $page['id']]);
    $profiles = !empty($profilesRes['success']) ? ($profilesRes['data']['items'] ?? []) : [];
    $visionRes = pw_api('/api/public/vision-missions', ['page_id' => $page['id']]);
    $visionMissions = !empty($visionRes['success']) ? ($visionRes['data']['items'] ?? []) : [];
    $featuresRes = pw_api('/api/public/features', ['page_id' => $page['id']]);
    $features = !empty($featuresRes['success']) ? ($featuresRes['data']['items'] ?? []) : [];
}

$siteName = pw_e((string) pw_env('APP_NAME', 'Public Website'));
$title = pw_e($page['title'] ?? 'Page');
$heroCount = count($heroes);
$serviceCount = count($services);
$profileCount = count($profiles);
$visionCount = count($visionMissions);
$featureCount = count($features);
$heroSize = (string) (($heroRes['data']['size_preset'] ?? null) ?? 'md');
if (!in_array($heroSize, ['sm', 'md', 'lg', 'xl'], true)) {
    $heroSize = 'md';
}

$bannerTitle = trim((string) ($page['banner_title'] ?? ''));
if ($bannerTitle === '') {
    $bannerTitle = (string) ($page['title'] ?? 'Page');
}
$bannerEyebrow = trim((string) ($page['banner_eyebrow'] ?? ''));
$bannerSubtitle = trim((string) ($page['banner_subtitle'] ?? ''));
$bannerImage = trim((string) ($page['banner_image_url'] ?? ''));
$bannerEnabled = (int) ($page['banner_enabled'] ?? 0) === 1;
$bannerSize = (string) ($page['banner_size_preset'] ?? 'md');
if (!in_array($bannerSize, ['sm', 'md', 'lg', 'xl'], true)) {
    $bannerSize = 'md';
}
$showPageBanner = $heroCount === 0 && !empty($pageRes['success']) && $bannerEnabled;
// Overlay navbar on hero OR on page banner when transparent (so image shows through, not body sand)
$navOverlay = $heroCount > 0 || ($navTransparent && $showPageBanner);
$headerClass = 'site-header';
if (!$navOverlay) {
    $headerClass .= ' site-header-solid';
}
if ($navTransparent) {
    $headerClass .= ' is-transparent';
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $title ?> · <?= $siteName ?></title>
  <link rel="stylesheet" href="<?= pw_e(pw_url('/assets/css/site.css')) ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/v4-shims.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body style="--page-bg: <?= pw_e($pageBg) ?>; --sand: <?= pw_e($pageBg) ?>; background: <?= pw_e($pageBg) ?>;">
  <header
    class="<?= pw_e($headerClass) ?>"
    style="--nav-bg: <?= pw_e($navBg) ?>; --nav-menu: <?= pw_e($navMenu) ?>; --nav-active: <?= pw_e($navActive) ?>;"
  >
    <a class="logo" href="<?= pw_e(pw_url('/')) ?>"><?= $siteName ?></a>
    <nav class="site-nav" data-site-nav aria-label="Main">
      <?php
      $currentPath = '/' . ltrim((string) $slug, '/');
      $renderNav = function (array $items, int $depth = 0) use (&$renderNav, $currentPath): void {
          $ulClass = $depth === 0 ? 'nav-root' : 'nav-sub';
          echo '<ul class="' . $ulClass . '">';
          foreach ($items as $item) {
              $href = (string) ($item['url'] ?? '#');
              if ($href === '' && !empty($item['pages'][0]['slug'])) {
                  $href = '/' . $item['pages'][0]['slug'];
              }
              if ($href === '') {
                  $href = '#';
              }
              $children = $item['children'] ?? [];
              $hasChildren = is_array($children) && count($children) > 0;
              $normalized = $href === '/' ? '/home' : rtrim($href, '/');
              $isActive = $normalized === $currentPath || $normalized === '/' . ltrim($currentPath, '/');
              $childActive = false;
              if ($hasChildren) {
                  foreach ($children as $child) {
                      $ch = (string) ($child['url'] ?? '');
                      if ($ch === '' && !empty($child['pages'][0]['slug'])) {
                          $ch = '/' . $child['pages'][0]['slug'];
                      }
                      $chNorm = $ch === '/' ? '/home' : rtrim($ch, '/');
                      if ($chNorm === $currentPath || $chNorm === '/' . ltrim($currentPath, '/')) {
                          $childActive = true;
                          break;
                      }
                  }
              }
              $liClass = 'nav-item';
              if ($hasChildren) {
                  $liClass .= ' has-children';
              }
              if ($isActive || $childActive) {
                  $liClass .= ' is-active';
              }
              echo '<li class="' . $liClass . '">';
              echo '<a class="nav-link' . ($isActive ? ' is-current' : '') . '" href="' . pw_e(pw_url($href)) . '"';
              if ($hasChildren) {
                  echo ' aria-haspopup="true" aria-expanded="false"';
              }
              echo '>' . pw_e((string) $item['title']);
              if ($hasChildren) {
                  echo '<span class="nav-caret" aria-hidden="true"></span>';
              }
              echo '</a>';
              if ($hasChildren) {
                  $renderNav($children, $depth + 1);
              }
              echo '</li>';
          }
          echo '</ul>';
      };
      $renderNav($menus);
      ?>
    </nav>
  </header>

  <?php if ($heroCount > 0): ?>
  <section class="hero-slider hero-size-<?= pw_e($heroSize) ?><?= $heroCount > 1 ? ' has-multiple' : '' ?>" data-hero-slider data-hero-size="<?= pw_e($heroSize) ?>" aria-roledescription="carousel" aria-label="Page hero">
    <div class="hero-track">
      <?php foreach ($heroes as $index => $hero): ?>
        <article
          class="hero-slide<?= $index === 0 ? ' is-active' : '' ?>"
          data-slide="<?= (int) $index ?>"
          style="--hero-image: url('<?= pw_e((string) $hero['image_url']) ?>')"
          <?= $index === 0 ? '' : 'hidden' ?>
          aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"
        >
          <div class="hero-overlay">
            <div class="hero-copy">
              <p class="brand"><?= $siteName ?></p>
              <h1><?= pw_e((string) $hero['title']) ?></h1>
              <?php if (!empty($hero['description'])): ?>
                <p class="lede"><?= pw_e((string) $hero['description']) ?></p>
              <?php endif; ?>
              <?php if (!empty($hero['button_text']) && !empty($hero['button_url'])): ?>
                <a class="cta" href="<?= pw_e(pw_url((string) $hero['button_url'])) ?>"><?= pw_e((string) $hero['button_text']) ?></a>
              <?php endif; ?>
              <?php if ((int) ($hero['is_featured'] ?? 0) === 1): ?>
                <span class="hero-featured-tag">Featured</span>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if ($heroCount > 1): ?>
      <button type="button" class="hero-nav hero-prev" data-hero-prev aria-label="Previous slide"></button>
      <button type="button" class="hero-nav hero-next" data-hero-next aria-label="Next slide"></button>
      <div class="hero-dots" role="tablist" aria-label="Hero slides">
        <?php foreach ($heroes as $index => $hero): ?>
          <button
            type="button"
            class="hero-dot<?= $index === 0 ? ' is-active' : '' ?>"
            data-hero-dot="<?= (int) $index ?>"
            role="tab"
            aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
            aria-label="Show slide <?= (int) ($index + 1) ?>"
          ></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($showPageBanner): ?>
  <section
    class="page-banner banner-size-<?= pw_e($bannerSize) ?><?= $bannerImage !== '' ? ' has-image' : ' no-image' ?>"
    <?php if ($bannerImage !== ''): ?>style="--banner-image: url('<?= pw_e($bannerImage) ?>')"<?php endif; ?>
    aria-label="Page banner"
  >
    <div class="page-banner-overlay">
      <div class="page-banner-inner">
        <?php if ($bannerEyebrow !== ''): ?>
          <p class="page-banner-eyebrow"><?= pw_e($bannerEyebrow) ?></p>
        <?php endif; ?>
        <h1 class="page-banner-title"><?= pw_e($bannerTitle) ?></h1>
        <?php if ($bannerSubtitle !== ''): ?>
          <p class="page-banner-subtitle"><?= pw_e($bannerSubtitle) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php
  $hasPageData = $heroCount > 0 || $serviceCount > 0 || $profileCount > 0 || $visionCount > 0 || $featureCount > 0;
  ?>
  <?php if (!$hasPageData): ?>
  <main class="content<?= $showPageBanner ? '' : ' content-no-hero' ?> content-empty-page">
    <h2><?= $title ?></h2>
    <p class="muted-note">This page (`<?= pw_e($page['slug'] ?? '') ?>`) has no content sections to display yet.</p>
  </main>
  <?php endif; ?>

  <?php if ($profileCount > 0): ?>
  <section class="profile-section" aria-label="Profile overview">
    <?php foreach ($profiles as $profile): ?>
      <div class="profile-overview<?= (int)($profile['is_featured'] ?? 0) === 1 ? ' is-featured' : '' ?>">
        <div class="profile-copy">
          <h2 class="profile-title"><?= pw_e((string) $profile['title']) ?></h2>
          <div class="profile-body">
            <?= pw_rich((string) ($profile['body_html'] ?? '')) ?>
          </div>
        </div>
        <div class="profile-media">
          <?php if (!empty($profile['image_top_url'])): ?>
            <img class="profile-img profile-img-top" src="<?= pw_e((string) $profile['image_top_url']) ?>" alt="">
          <?php endif; ?>
          <div class="profile-media-row">
            <?php if (!empty($profile['image_left_url'])): ?>
              <img class="profile-img profile-img-left" src="<?= pw_e((string) $profile['image_left_url']) ?>" alt="">
            <?php endif; ?>
            <?php if (!empty($profile['image_right_url'])): ?>
              <img class="profile-img profile-img-right" src="<?= pw_e((string) $profile['image_right_url']) ?>" alt="">
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <?php if ($visionCount > 0): ?>
  <section class="vision-section" aria-labelledby="vision-heading">
    <div class="vision-inner">
      <h2 id="vision-heading">Vision &amp; Mission</h2>
      <div class="vision-grid">
        <?php foreach ($visionMissions as $vm): ?>
          <article class="vision-card type-<?= pw_e((string)($vm['statement_type'] ?? 'vision')) ?><?= (int)($vm['is_featured'] ?? 0) === 1 ? ' is-featured' : '' ?>">
            <?php if (!empty($vm['image_url'])): ?>
              <img class="vision-image" src="<?= pw_e((string)$vm['image_url']) ?>" alt="">
            <?php endif; ?>
            <div class="vision-body">
              <p class="vision-type"><?= pw_e((string)($vm['statement_type_label'] ?? 'Vision')) ?></p>
              <h3><?= pw_e((string)$vm['title']) ?></h3>
              <div class="vision-copy">
                <?= pw_rich((string)($vm['body_html'] ?? '')) ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($featureCount > 0): ?>
  <?php foreach ($features as $feature): ?>
  <section class="features-section<?= (int)($feature['is_featured'] ?? 0) === 1 ? ' is-featured' : '' ?>" aria-label="<?= pw_e((string)$feature['title']) ?>">
    <div class="features-inner">
      <header class="features-header">
        <h2 class="features-title"><?= pw_e((string)$feature['title']) ?></h2>
        <?php if (!empty($feature['description'])): ?>
          <p class="features-lede"><?= pw_e((string)$feature['description']) ?></p>
        <?php endif; ?>
      </header>
      <?php if (!empty($feature['cards'])): ?>
      <div class="features-grid">
        <?php foreach ($feature['cards'] as $card): ?>
          <article class="feature-card">
            <div class="feature-icon" aria-hidden="true">
              <?php if (!empty($card['icon_url'])): ?>
                <img src="<?= pw_e((string)$card['icon_url']) ?>" alt="">
              <?php else: ?>
                <?= (string)($card['icon_html'] ?? '') ?>
              <?php endif; ?>
            </div>
            <h3 class="feature-card-title"><?= pw_e((string)$card['title']) ?></h3>
            <?php if (!empty($card['description'])): ?>
              <p class="feature-card-desc"><?= pw_e((string)$card['description']) ?></p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($serviceCount > 0): ?>
  <section class="services-section" aria-labelledby="services-heading">
    <div class="services-inner">
      <h2 id="services-heading">Services</h2>
      <p class="services-lede">Services linked to this page.</p>
      <div class="services-grid">
        <?php foreach ($services as $svc): ?>
          <article class="service-card<?= (int)($svc['is_featured'] ?? 0) === 1 ? ' is-featured' : '' ?>">
            <?php if (!empty($svc['image_url'])): ?>
              <img class="service-image" src="<?= pw_e((string)$svc['image_url']) ?>" alt="">
            <?php endif; ?>
            <div class="service-body">
              <?php if ((int)($svc['is_featured'] ?? 0) === 1): ?>
                <span class="service-badge">Featured</span>
              <?php endif; ?>
              <h3><?= pw_e((string)$svc['title']) ?></h3>
              <?php if (!empty($svc['description'])): ?>
                <p><?= pw_e((string)$svc['description']) ?></p>
              <?php endif; ?>
              <?php if (!empty($svc['items'])): ?>
                <ul class="service-items">
                  <?php foreach ($svc['items'] as $item): ?>
                    <li>
                      <strong><?= pw_e((string)$item['title']) ?></strong>
                      <?php if (!empty($item['body'])): ?>
                        <span><?= pw_e((string)$item['body']) ?></span>
                      <?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
              <?php if (!empty($svc['button_text']) && !empty($svc['button_url'])): ?>
                <a class="service-cta" href="<?= pw_e(pw_url((string)$svc['button_url'])) ?>"><?= pw_e((string)$svc['button_text']) ?></a>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <footer class="site-footer">
    <p>&copy; <?= date('Y') ?> <?= $siteName ?></p>
  </footer>
  <script src="<?= pw_e(pw_url('/assets/js/site.js')) ?>"></script>
</body>
</html>
