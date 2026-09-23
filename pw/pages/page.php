<?php
declare(strict_types=1);

/** @var string $slug */

$menusRes = pw_api('/api/public/menus', ['menu_type' => 'website']);
$menus = !empty($menusRes['success']) ? ($menusRes['data']['items'] ?? []) : [];

$pageRes = pw_api('/api/public/page', ['slug' => $slug]);
$heroRes = null;
if (empty($pageRes['success'])) {
    http_response_code(404);
    $page = ['title' => 'Page not found', 'slug' => $slug, 'template_key' => 'default'];
    $heroes = [];
    $services = [];
} else {
    $page = $pageRes['data'];
    $heroRes = pw_api('/api/public/hero', ['page_id' => $page['id']]);
    $heroes = !empty($heroRes['success']) ? ($heroRes['data']['items'] ?? []) : [];
    $servicesRes = pw_api('/api/public/services', ['page_id' => $page['id']]);
    $services = !empty($servicesRes['success']) ? ($servicesRes['data']['items'] ?? []) : [];
}

$siteName = pw_e((string) pw_env('APP_NAME', 'Public Website'));
$title = pw_e($page['title'] ?? 'Page');
$heroCount = count($heroes);
$serviceCount = count($services);
$heroSize = (string) (($heroRes['data']['size_preset'] ?? null) ?? 'md');
if (!in_array($heroSize, ['sm', 'md', 'lg', 'xl'], true)) {
    $heroSize = 'md';
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $title ?> · <?= $siteName ?></title>
  <link rel="stylesheet" href="<?= pw_e(pw_url('/assets/css/site.css')) ?>">
</head>
<body>
  <header class="site-header<?= $heroCount === 0 ? ' site-header-solid' : '' ?>">
    <a class="logo" href="<?= pw_e(pw_url('/')) ?>"><?= $siteName ?></a>
    <nav class="site-nav">
      <?php
      $renderNav = function (array $items) use (&$renderNav): void {
          echo '<ul>';
          foreach ($items as $item) {
              $href = (string) ($item['url'] ?? '#');
              if ($href === '' && !empty($item['pages'][0]['slug'])) {
                  $href = '/' . $item['pages'][0]['slug'];
              }
              echo '<li><a href="' . pw_e(pw_url($href)) . '">' . pw_e($item['title']) . '</a>';
              if (!empty($item['children'])) {
                  $renderNav($item['children']);
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
      <button type="button" class="hero-nav hero-prev" data-hero-prev aria-label="Previous slide">‹</button>
      <button type="button" class="hero-nav hero-next" data-hero-next aria-label="Next slide">›</button>
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

  <?php
  $hasPageData = $heroCount > 0 || $serviceCount > 0;
  ?>
  <?php if (!$hasPageData): ?>
  <main class="content content-no-hero content-empty-page">
    <h2><?= $title ?></h2>
    <p class="muted-note">This page (`<?= pw_e($page['slug'] ?? '') ?>`) has no hero or services to display yet.</p>
  </main>
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
