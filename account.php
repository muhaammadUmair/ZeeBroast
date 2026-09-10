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

$loyaltyVisible = loyalty_enabled() && !empty($user['allow_referral_points']);
$loyaltyBalance = 0;
$loyaltyConfig = loyalty_config();
$loyaltyTransactions = [];
if ($loyaltyVisible) {
    $loyaltyBalance = loyalty_balance((int)$user['id']);

    // Fetch the full ledger ascending to compute an accurate running balance per row,
    // then reverse to show newest first (limited to the most recent 50 rows).
    $txStmt = db()->prepare('SELECT t.*, o.order_code FROM loyalty_points_transactions t LEFT JOIN orders o ON o.id = t.order_id WHERE t.user_id = ? ORDER BY t.created_at ASC, t.id ASC');
    $txStmt->execute([$user['id']]);
    $allTransactions = $txStmt->fetchAll();

    $running = 0;
    foreach ($allTransactions as &$row) {
        $running += (int)$row['points'];
        $row['balance_after'] = $running;
    }
    unset($row);

    $loyaltyTransactions = array_slice(array_reverse($allTransactions), 0, 50);
}
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

<?php if ($loyaltyVisible): ?>
<div class="section" style="padding-top:0">
  <div class="container">
    <div class="flow-card" style="width:100%">
      <h2 style="font-size:18px;font-weight:700;margin-bottom:16px">Loyalty Points</h2>
      <div class="info-grid" style="margin-bottom:16px">
        <div><p class="muted">Available Points</p><p style="font-size:22px;font-weight:800"><?= (int)$loyaltyBalance ?></p></div>
        <div><p class="muted">Value</p><p style="font-size:22px;font-weight:800"><?= money_precise(loyalty_points_value($loyaltyBalance, $loyaltyConfig)) ?></p></div>
        <div><p class="muted">Rate</p><p style="font-size:22px;font-weight:800">1 = <?= money_precise($loyaltyConfig['point_value']) ?></p></div>
        <div><p class="muted">Earn Rate</p><p style="font-size:22px;font-weight:800"><?= money($loyaltyConfig['spend_amount_per_point']) ?> = 1pt</p></div>
      </div>

      <?php if (empty($loyaltyTransactions)): ?>
        <p class="muted">No loyalty transactions yet.</p>
      <?php else: ?>
        <table style="width:100%">
          <thead><tr><th>Date</th><th>Type</th><th>Order</th><th>Points</th><th>Value</th><th>Balance After</th><th>Description</th></tr></thead>
          <tbody>
            <?php foreach ($loyaltyTransactions as $t): ?>
            <tr>
              <td><?= e(date('d M Y', strtotime($t['created_at']))) ?></td>
              <td><?= e(ucfirst(strtolower($t['transaction_type']))) ?></td>
              <td><?= $t['order_code'] ? '<a href="' . e(base_url('track-order.php?code=' . urlencode($t['order_code']))) . '">#' . e($t['order_code']) . '</a>' : '—' ?></td>
              <td><?= $t['points'] > 0 ? '+' . (int)$t['points'] : (int)$t['points'] ?></td>
              <td><?= money_precise($t['monetary_value']) ?></td>
              <td><?= (int)$t['balance_after'] ?></td>
              <td><?= e($t['description'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
