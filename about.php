<?php
$page_title = 'About Us';
$active_nav = 'about';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>About Us</h1>
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / About Us</div>
  </div>
</div>

<div class="section">
  <div class="container">
    <div class="about-hero">
      <span class="eyebrow">Our Story</span>
      <h2 style="font-size:30px;font-weight:800;max-width:700px;margin:0 auto 16px">Serving Crispy Happiness Since Day One</h2>
      <p class="muted" style="max-width:640px;margin:0 auto">
        <?= e(setting('site_name')) ?> was born from a love of perfectly crispy, juicy broast chicken. Every piece is
        marinated in our secret spice blend, freshly prepared daily, and cooked to golden perfection —
        because you deserve nothing less than irresistible.
      </p>
    </div>

    <div class="value-grid">
      <div class="value-card"><div class="v-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"></path></svg></div><h4>100% Halal</h4><p>Certified halal ingredients in every dish we serve.</p></div>
      <div class="value-card"><div class="v-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 10h8M9 6h6M7 10v8h10v-8M9 18v2h6v-2M12 6V3"></path><path d="M10 13h4"></path></svg></div><h4>Freshly Prepared</h4><p>Made fresh daily, never frozen, never compromised.</p></div>
      <div class="value-card"><div class="v-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M13 3 5 14h6l-1 7 8-11h-6l1-7Z"></path></svg></div><h4>Fast Delivery</h4><p>Hot food delivered to your doorstep in record time.</p></div>
      <div class="value-card"><div class="v-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3Z"></path></svg></div><h4>Best Quality</h4><p>Consistent quality and taste, order after order.</p></div>
    </div>
  </div>
</div>

<section class="section section-alt">
  <div class="container delivery-with-rider" style="--delivery-rider-image: url('<?= e(base_url('assets/img/Rider.png')) ?>');">
    <div class="delivery-banner">
      <div class="content">
        <span class="eyebrow">Fast Delivery, Hot &amp; Fresh</span>
        <h2>We Deliver Happiness To Your Doorstep!</h2>
        <p>Craving something crispy? We're just a click away.</p>
        <a href="<?= base_url('menu.php') ?>" class="btn btn-primary">Order Now</a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
