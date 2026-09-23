<?php
declare(strict_types=1);
/** @var string $appName */
/** @var string $pageTitle */
/** @var string $content */
/** @var array $nav */
/** @var array|null $user */
/** @var array $flashes */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?> · <?= $appName ?></title>
    <link rel="stylesheet" href="<?= wa_e(wa_url('/assets/css/admin.css')) ?>">
</head>
<body>
<?php if ($user): ?>
<header class="topbar">
    <div class="brand"><a href="<?= wa_e(wa_url('/dashboard')) ?>"><?= $appName ?></a></div>
    <nav class="mega-nav" id="megaNav">
        <button type="button" class="nav-toggle" id="navToggle" aria-label="Menu">Menu</button>
        <ul class="nav-root">
            <?php foreach ($nav as $item): ?>
                <?php $children = $item['children'] ?? []; ?>
                <?php if ($children): ?>
                    <li class="has-mega">
                        <button type="button" class="nav-link"><?= wa_e($item['title']) ?></button>
                        <div class="mega-panel">
                            <div class="mega-grid">
                                <?php foreach ($children as $child): ?>
                                    <a href="<?= wa_e(wa_url((string) ($child['url'] ?? '#'))) ?>"><?= wa_e($child['title']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </li>
                <?php else: ?>
                    <li>
                        <a class="nav-link" href="<?= wa_e(wa_url((string) ($item['url'] ?? '#'))) ?>"><?= wa_e($item['title']) ?></a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="user-chip">
        <span><?= wa_e($user['name'] ?? '') ?> · <?= wa_e($user['role_name'] ?? '') ?></span>
        <a href="<?= wa_e(wa_url('/account')) ?>">Account</a>
        <a href="<?= wa_e(wa_url('/logout')) ?>">Logout</a>
    </div>
</header>
<?php endif; ?>

<main class="container">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= wa_e($flash['type']) ?>"><?= wa_e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
<script src="<?= wa_e(wa_url('/assets/js/admin.js')) ?>"></script>
</body>
</html>
