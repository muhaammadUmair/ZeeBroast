<?php
$page_title = 'Track Order';
require_once __DIR__ . '/includes/header.php';

$code = $_GET['code'] ?? ($_POST['code'] ?? '');
$order = null;

if ($code !== '') {
    $stmt = db()->prepare('SELECT * FROM orders WHERE order_code = ?');
    $stmt->execute([$code]);
    $order = $stmt->fetch();
}

$steps = ['pending' => 'Order Received', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing', 'on_the_way' => 'On The Way', 'delivered' => 'Delivered'];
$statusOrder = array_keys($steps);
?>

<div class="page-header">
  <div class="container">
    <h1>Track Your Order</h1>
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / Track Order</div>
  </div>
</div>

<div class="section">
  <div class="container flow-wrap">
    <div class="flow-card">
      <form method="get" style="display:flex;gap:10px;margin-bottom:24px">
        <input class="form-control" type="text" name="code" placeholder="Enter your Order ID e.g. ZB1234AB" value="<?= e($code) ?>">
        <button class="btn btn-primary" type="submit">Track</button>
      </form>

      <?php if ($code !== '' && !$order): ?>
        <div class="alert alert-error">No order found with that ID.</div>
      <?php elseif ($order): ?>
        <?php if ($order['status'] === 'cancelled'): ?>
          <div class="alert alert-error">This order was cancelled.</div>
        <?php else: ?>
          <?php $currentIdx = array_search($order['status'], $statusOrder); ?>
          <?php foreach ($steps as $key => $label): $idx = array_search($key, $statusOrder); $done = $idx <= $currentIdx; ?>
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
            <div style="width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;background:<?= $done ? 'var(--red)' : 'var(--card-bg)' ?>;border:1px solid var(--border);color:#fff"><?= $done ? '✓' : '' ?></div>
            <span style="color:<?= $done ? '#fff' : 'var(--text-muted)' ?>;font-weight:<?= $done ? 600 : 400 ?>"><?= e($label) ?></span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
        <div class="summary-row total" style="margin-top:20px"><span>Order Total</span><span><?= money($order['total']) ?></span></div>
        <p class="muted" style="margin-top:10px">Estimated Delivery: <?= e(format_minutes_range($order['estimated_minutes'])) ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
