<?php
/**
 * Shared storefront header. Expects $page_title and optional $active_nav to be set
 * by the including page before requiring this file.
 */
require_once __DIR__ . '/../config/config.php';

$active_nav = $active_nav ?? '';
$cart_count = cart_count();
$user = current_user();
$favicon_value = trim((string)setting('fcon_image', ''));
$favicon_url = '';
$logo_value = trim((string)setting('logo_image', ''));
$logo_url = '';

if ($favicon_value !== '') {
    if (preg_match('#^https?://#i', $favicon_value)) {
        $favicon_url = $favicon_value;
    } elseif (str_starts_with($favicon_value, 'uploads/')) {
        $favicon_url = base_url($favicon_value);
    } else {
        $favicon_url = base_url($favicon_value);
    }
}

if ($logo_value !== '') {
    if (preg_match('#^https?://#i', $logo_value)) {
        $logo_url = $logo_value;
    } else {
        $logo_url = base_url($logo_value);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(($page_title ?? '') ? $page_title . ' — ' . setting('site_name') : setting('site_name') . ' — ' . setting('site_tagline')) ?></title>
<meta name="description" content="<?= e(setting('site_tagline')) ?> — Order crispy broast chicken, burgers, wings and fries online.">
<?php if ($favicon_url): ?>
<link rel="icon" type="image/png" href="<?= e($favicon_url) ?>">
<link rel="shortcut icon" href="<?= e($favicon_url) ?>">
<?php else: ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍗</text></svg>">
<?php endif; ?>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
<script>window.ZB_BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
</head>
<body>

<div class="topbar">
  <div class="container">
    <div class="topbar-left">
      <span><span class="topbar-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 4h3l2 5-2.5 1.5a12 12 0 0 0 6 6L15 14l5 2v3a1 1 0 0 1-1 1C10.7 20 4 13.3 4 5a1 1 0 0 1 1-1Z"></path></svg></span><?= e(setting('phone')) ?></span>
      <span><span class="topbar-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg></span><?= e(setting('address')) ?></span>
    </div>
    <div class="topbar-right">
      <span><?php if (setting('halal_badge')): ?><span class="halal-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"></path></svg></span>100% Halal Certified<?php endif; ?></span>
    </div>
  </div>
</div>

<header class="site-header">
  <div class="container">
    <a href="<?= base_url('index.php') ?>" class="logo" aria-label="<?= e(setting('site_name')) ?> home">
      <?php if ($logo_url): ?>
        <img src="<?= e($logo_url) ?>" alt="<?= e(setting('site_name')) ?>" class="logo-image">
      <?php else: ?>
        <span><span class="zee">ZEE</span><span class="broast">BROAST</span></span>
      <?php endif; ?>
    </a>

    <nav class="main-nav" id="mainNav">
      <a href="<?= base_url('index.php') ?>" class="<?= $active_nav === 'home' ? 'active' : '' ?>">Home</a>
      <a href="<?= base_url('menu.php') ?>" class="<?= $active_nav === 'menu' ? 'active' : '' ?>">Menu</a>
      <a href="<?= base_url('category.php') ?>" class="<?= $active_nav === 'categories' ? 'active' : '' ?>">Categories</a>
      <a href="<?= base_url('deals.php') ?>" class="<?= $active_nav === 'deals' ? 'active' : '' ?>">Deals</a>
      <a href="<?= base_url('about.php') ?>" class="<?= $active_nav === 'about' ? 'active' : '' ?>">About Us</a>
      <a href="<?= base_url('contact.php') ?>" class="<?= $active_nav === 'contact' ? 'active' : '' ?>">Contact</a>
    </nav>

    <div class="header-actions">
      <a href="<?= base_url('menu.php') ?>" class="icon-btn icon-btn-search" title="Search" aria-label="Search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="5.8"></circle><path d="m15.2 15.2 4.3 4.3"></path></svg></a>
      <a href="<?= base_url('cart.php') ?>" class="icon-btn" title="Cart" aria-label="Cart">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h2l1.5 10h10L20 8H7"></path><circle cx="9" cy="19" r="1.2"></circle><circle cx="17" cy="19" r="1.2"></circle></svg>
        <?php if ($cart_count > 0): ?><span class="icon-badge"><?= $cart_count ?></span><?php endif; ?>
      </a>
      <a href="<?= base_url($user ? 'account.php' : 'login.php') ?>" class="icon-btn" title="Account" aria-label="Account"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"></circle><path d="M5 20c.8-3.3 3.1-5 7-5s6.2 1.7 7 5"></path></svg></a>
      <a href="<?= base_url('menu.php') ?>" class="btn btn-primary btn-sm order-now-desktop">Order Now</a>
      <button class="nav-toggle" id="navToggle" aria-label="Open menu"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
    </div>
  </div>
</header>
<script>
document.getElementById('navToggle')?.addEventListener('click', function () {
  document.getElementById('mainNav').classList.toggle('open');
});
</script>
