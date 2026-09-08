<?php
$page_title = 'Home';
$active_nav = 'home';
require_once __DIR__ . '/includes/header.php';

$signature = db()->query("SELECT p.*, c.slug AS cat_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.status='active' ORDER BY p.sort_order, p.name LIMIT 8")->fetchAll();
$deals = db()->query("SELECT * FROM deals WHERE status='active' ORDER BY sort_order LIMIT 8")->fetchAll();
$bucketImage = trim((string)setting('bucket_image', ''));
$riderImage = trim((string)setting('rider_image', ''));
?>

<section class="hero<?= $bucketImage !== '' ? ' hero-with-bucket' : '' ?>"<?= $bucketImage !== '' ? ' style="--hero-bucket-image: url(\'' . e(base_url($bucketImage)) . '\');"' : '' ?>>
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
        <div class="badge"><span class="b-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2v4"></path><path d="M7.2 4.7a9 9 0 1 0 9.6 0"></path><circle cx="12" cy="13" r="3"></circle></svg></span>100% Halal<br>Guaranteed</div>
        <div class="badge"><span class="b-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 9h16v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"></path><path d="M8 9V7a4 4 0 0 1 8 0v2"></path><path d="M9 13h6"></path></svg></span>Freshly Prepared<br>Daily</div>
        <div class="badge"><span class="b-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 7h11v8H3z"></path><path d="M14 10h3l3 3v2h-6z"></path><circle cx="7" cy="17" r="2"></circle><circle cx="17" cy="17" r="2"></circle></svg></span>Fast Delivery<br>At Your Doorstep</div>
        <div class="badge"><span class="b-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3l7 3v6c0 5-3.1 7.7-7 9-3.9-1.3-7-4-7-9V6z"></path><path d="m12 8 1.2 2.4 2.6.4-1.9 1.9.5 2.7L12 14l-2.4 1.4.5-2.7-1.9-1.9 2.6-.4z"></path></svg></span>Best Quality<br>Always</div>
      </div>
    </div>
    <!-- <div class="hero-visual">
      <div class="hero-plate">
        🍗
        <span class="flame f1">🔥</span>
        <span class="flame f2">🔥</span>
      </div>
    </div> -->
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
      <div class="food-card">
        <a href="<?= base_url('product.php?slug=' . urlencode($item['slug'])) ?>">
          <?= food_thumb($item['image'], $item['icon']) ?>
        </a>
        <div class="food-body">
          <a href="<?= base_url('product.php?slug=' . urlencode($item['slug'])) ?>"><h3><?= e($item['name']) ?></h3></a>
          <div class="food-price-row">
            <span class="food-price"><span class="from">Starting From</span><?= money($item['sale_price'] ?: $item['price']) ?></span>
            <button class="add-cart-btn js-add-cart" data-type="product" data-id="<?= (int)$item['id'] ?>">+ Add to Cart</button>
          </div>
        </div>
      </div>
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
      <?php $dealImageUrl = media_url($deal['image'] ?? null); ?>
      <div class="deal-card">
        <?php if ($deal['discount_percent'] > 0): ?><span class="deal-save">Save <?= (int)$deal['discount_percent'] ?>%</span><?php endif; ?>
        <div class="deal-thumb">
          <?php if ($dealImageUrl): ?>
            <img src="<?= e($dealImageUrl) ?>" alt="<?= e($deal['title']) ?>" loading="lazy">
          <?php else: ?>
            <?= e($deal['icon'] ?: '🍗') ?>
          <?php endif; ?>
        </div>
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
  <div class="container<?= $riderImage !== '' ? ' delivery-with-rider' : '' ?>"<?= $riderImage !== '' ? ' style="--delivery-rider-image: url(\'' . e(base_url($riderImage)) . '\');"' : '' ?>>
    <div class="delivery-banner">
      <div class="content">
        <span class="eyebrow">Fast Delivery, Hot &amp; Fresh</span>
        <h2>We Deliver Happiness To Your Doorstep!</h2>
        <p>Order now and get piping hot broast chicken delivered straight to your door in <?= e(setting('estimated_delivery_minutes', 30)) ?> minutes or less.</p>
        <a href="<?= base_url('menu.php') ?>" class="btn btn-primary">Order Now</a>
      </div>
      <!-- <span class="rider-emoji">🛵</span> -->
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
