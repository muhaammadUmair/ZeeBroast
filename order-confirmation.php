<?php
$page_title = 'Order Confirmed';
require_once __DIR__ . '/includes/header.php';

$code = $_GET['code'] ?? '';
$stmt = db()->prepare('SELECT * FROM orders WHERE order_code = ?');
$stmt->execute([$code]);
$order = $stmt->fetch();

if (!$order) {
    redirect(base_url('index.php'));
}
?>

<div class="section">
  <div class="container flow-wrap">
    <div class="flow-card text-center">
      <div class="confirm-check">✓</div>
      <h2 style="font-size:22px;font-weight:800;margin-bottom:8px">Thank You!</h2>
      <p class="muted" style="margin-bottom:22px">Your order has been placed successfully.</p>

      <div style="text-align:left">
        <div class="summary-row"><span>Order ID</span><span>#<?= e($order['order_code']) ?></span></div>
        <div class="summary-row"><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
        <div class="summary-row"><span>Delivery Fee</span><span><?= $order['delivery_fee'] > 0 ? money($order['delivery_fee']) : 'Free' ?></span></div>
        <?php if ($order['discount'] > 0): ?><div class="summary-row"><span>Discount</span><span>-<?= money($order['discount']) ?></span></div><?php endif; ?>
        <div class="summary-row total"><span>Total</span><span><?= money($order['total']) ?></span></div>
      </div>

      <p class="muted" style="margin:18px 0">Estimated Delivery<br><strong style="color:#fff;font-size:18px"><?= e(format_minutes_range($order['estimated_minutes'])) ?></strong></p>

      <a href="<?= base_url('track-order.php?code=' . urlencode($order['order_code'])) ?>" class="btn btn-primary btn-block">Track Order</a>
      <a href="<?= base_url('menu.php') ?>" class="btn btn-outline btn-block" style="margin-top:10px">Order More</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
