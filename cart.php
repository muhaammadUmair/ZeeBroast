<?php
$page_title = 'Your Cart';
require_once __DIR__ . '/includes/header.php';

$items = cart_items();
$subtotal = cart_subtotal();
?>

<div class="page-header">
  <div class="container">
    <h1>Your Cart</h1>
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / Cart</div>
  </div>
</div>

<div class="section">
  <div class="container flow-wrap">
    <div class="flow-card">
      <div class="flow-title"><a href="<?= base_url('menu.php') ?>">←</a> Your Cart</div>

      <?php if (empty($items)): ?>
        <div class="empty-state">
          <div class="e-icon">🛒</div>
          <p>Your cart is empty.</p>
          <a href="<?= base_url('menu.php') ?>" class="btn btn-primary" style="margin-top:16px">Browse Menu</a>
        </div>
      <?php else: ?>

        <?php foreach ($items as $key => $item): ?>
        <div class="cart-line">
          <div class="thumb"><?= $item['type'] === 'deal' ? '🎉' : '🍗' ?></div>
          <div class="info">
            <h4><?= e($item['name']) ?></h4>
            <div class="price"><?= money($item['price']) ?></div>
          </div>
          <div class="qty-control">
            <button class="js-qty" data-key="<?= e($key) ?>" data-delta="-1">−</button>
            <span class="js-qty-value" data-key="<?= e($key) ?>"><?= (int)$item['qty'] ?></span>
            <button class="js-qty" data-key="<?= e($key) ?>" data-delta="1">+</button>
          </div>
          <button class="icon-btn js-remove" data-key="<?= e($key) ?>" title="Remove" style="width:30px;height:30px;font-size:12px">✕</button>
        </div>
        <?php endforeach; ?>

        <div class="summary-row total">
          <span>Subtotal</span>
          <span><?= money($subtotal) ?></span>
        </div>

        <a href="<?= base_url('checkout.php') ?>" class="btn btn-primary btn-block" style="margin-top:20px">Proceed to Checkout</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
