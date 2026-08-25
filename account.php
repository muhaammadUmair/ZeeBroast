<?php
$page_title = 'My Account';
require_once __DIR__ . '/includes/header.php';

if (!is_logged_in()) {
    redirect(base_url('login.php'));
}

$user = current_user();
$orders = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
$orders->execute([$user['id']]);
$orders = $orders->fetchAll();
?>

<div class="page-header">
  <div class="container">
    <h1>My Account</h1>
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / My Account</div>
  </div>
</div>

<div class="section">
  <div class="container">
    <div class="info-grid">
      <div class="flow-card">
        <h2 style="font-size:18px;font-weight:700;margin-bottom:16px">Profile</h2>
        <p class="muted">Name</p><p style="margin-bottom:12px"><?= e($user['full_name']) ?></p>
        <p class="muted">Email</p><p style="margin-bottom:12px"><?= e($user['email']) ?></p>
        <p class="muted">Phone</p><p style="margin-bottom:20px"><?= e($user['phone'] ?: '—') ?></p>
        <a href="<?= base_url('logout.php') ?>" class="btn btn-outline btn-block">Logout</a>
      </div>

      <div class="flow-card">
        <h2 style="font-size:18px;font-weight:700;margin-bottom:16px">Order History</h2>
        <?php if (empty($orders)): ?>
          <p class="muted">You haven't placed any orders yet.</p>
        <?php else: ?>
          <?php foreach ($orders as $o): ?>
          <div class="cart-line">
            <div class="info">
              <h4>#<?= e($o['order_code']) ?></h4>
              <p class="muted" style="font-size:12px"><?= e(date('d M Y, h:i A', strtotime($o['created_at']))) ?> · <?= e(ucfirst(str_replace('_', ' ', $o['status']))) ?></p>
            </div>
            <div class="price"><?= money($o['total']) ?></div>
            <a href="<?= base_url('track-order.php?code=' . urlencode($o['order_code'])) ?>" class="btn btn-outline btn-sm">Track</a>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
