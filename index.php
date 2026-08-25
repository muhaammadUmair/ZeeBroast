<?php
$page_title = 'Home';
$active_nav = 'home';
require_once __DIR__ . '/includes/header.php';

$signature = db()->query("SELECT p.*, c.slug AS cat_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_featured = 1 AND p.status='active' ORDER BY p.sort_order LIMIT 4")->fetchAll();
$deals = db()->query("SELECT * FROM deals WHERE status='active' ORDER BY sort_order LIMIT 3")->fetchAll();
?>

<section class="hero">
  <div class="container">
    <div class="hero-text">
      <h1><?= e(setting('hero_title_line1', 'CRISPY.')) ?><br>
          <span class="accent"><?= e(setting('hero_title_line2', 'JUICY.')) ?></span><br>
          <?= e(setting('hero_title_line3', 'IRRESISTIBLE.')) ?></h1>
      <p class="lead"><?= e(setting('hero_subtitle')) ?></p>
      <div class="hero-cta">
        <a href="<?= base_url('menu.php') ?>" class="btn btn-primary">Order Now →</a>
        <a href="#" class="play-link"><span class="play-icon">▶</span> Watch Video</a>
      </div>
      <div class="hero-badges">
        <div class="badge"><span class="b-icon">✅</span>100% Halal<br>Guaranteed</div>
        <div class="badge"><span class="b-icon">👨‍🍳</span>Freshly Prepared<br>Daily</div>
        <div class="badge"><span class="b-icon">🚀</span>Fast Delivery<br>At Your Doorstep</div>
        <div class="badge"><span class="b-icon">⭐</span>Best Quality<br>Always</div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="hero-plate">
        🍗
        <span class="flame f1">🔥</span>
        <span class="flame f2">🔥</span>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Our Signature Menu</span>
      <h2>Crunch in Every Bite</h2>
      <p>From our signature broast to juicy burgers, explore the flavors you love.</p>
    </div>

    <div class="card-grid">
      <?php foreach ($signature as $item): ?>
      <a href="<?= base_url('product.php?slug=' . urlencode($item['slug'])) ?>" class="food-card">
        <?= food_thumb($item['image'], $item['icon']) ?>
        <div class="food-body">
          <h3><?= e($item['name']) ?></h3>
          <div class="food-price-row">
            <span class="food-price"><span class="from">Starting From</span><?= money($item['sale_price'] ?: $item['price']) ?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="text-center" style="margin-top:34px">
      <a href="<?= base_url('menu.php') ?>" class="btn btn-outline">Explore Menu</a>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Deals That Satisfy</span>
      <h2>Best Deals, Bigger Satisfaction</h2>
      <p>Enjoy our exclusive deals made for every craving.</p>
    </div>

    <div class="deal-grid">
      <?php foreach ($deals as $deal): ?>
      <div class="deal-card">
        <?php if ($deal['discount_percent'] > 0): ?><span class="deal-save">Save <?= (int)$deal['discount_percent'] ?>%</span><?php endif; ?>
        <div class="deal-thumb"><?= e($deal['icon'] ?: '🍗') ?></div>
        <div class="deal-body">
          <h3><?= e($deal['title']) ?></h3>
          <p><?= e($deal['description']) ?></p>
          <div class="deal-price-row">
            <span class="old"><?= money($deal['original_price']) ?></span>
            <span class="new"><?= money($deal['deal_price']) ?></span>
          </div>
          <button class="btn btn-primary btn-block js-add-cart" data-type="deal" data-id="<?= (int)$deal['id'] ?>">Add to Cart</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center" style="margin-top:34px">
      <a href="<?= base_url('deals.php') ?>" class="btn btn-outline">View All Deals</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="delivery-banner">
      <div class="content">
        <span class="eyebrow">Fast Delivery, Hot &amp; Fresh</span>
        <h2>We Deliver Happiness To Your Doorstep!</h2>
        <p>Order now and get piping hot broast chicken delivered straight to your door in <?= e(setting('estimated_delivery_minutes', 30)) ?> minutes or less.</p>
        <a href="<?= base_url('menu.php') ?>" class="btn btn-primary">Order Now</a>
      </div>
      <span class="rider-emoji">🛵</span>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
