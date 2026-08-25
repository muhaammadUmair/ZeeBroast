<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Dashboard';
$active_admin = 'dashboard';

$pdo = db();
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','confirmed','preparing','on_the_way')")->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$todayOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todayRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'")->fetchColumn();

$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 8")->fetchAll();

$statusColors = [
    'pending' => 'badge-yellow', 'confirmed' => 'badge-blue', 'preparing' => 'badge-blue',
    'on_the_way' => 'badge-blue', 'delivered' => 'badge-green', 'cancelled' => 'badge-red',
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="stat-grid">
  <div class="stat-card"><div class="label">Total Orders</div><div class="value"><?= $totalOrders ?></div><div class="sub"><?= $todayOrders ?> today</div></div>
  <div class="stat-card"><div class="label">Pending / Active Orders</div><div class="value"><?= $pendingOrders ?></div><div class="sub">Needs attention</div></div>
  <div class="stat-card"><div class="label">Total Revenue</div><div class="value"><?= money($totalRevenue) ?></div><div class="sub"><?= money($todayRevenue) ?> today</div></div>
  <div class="stat-card"><div class="label">Registered Customers</div><div class="value"><?= $totalCustomers ?></div><div class="sub">All time</div></div>
</div>

<div class="panel">
  <div class="panel-head">
    <h2>Recent Orders</h2>
    <a href="<?= base_url('admin/orders.php') ?>" class="btn btn-outline btn-sm">View All</a>
  </div>
  <table>
    <thead><tr><th>Order ID</th><th>Customer</th><th>Type</th><th>Total</th><th>Status</th><th>Placed</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($recentOrders)): ?>
      <tr><td colspan="7">No orders yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($recentOrders as $o): ?>
      <tr>
        <td>#<?= e($o['order_code']) ?></td>
        <td><?= e($o['guest_name']) ?></td>
        <td><?= e(ucfirst($o['order_type'])) ?></td>
        <td><?= money($o['total']) ?></td>
        <td><span class="badge <?= $statusColors[$o['status']] ?? 'badge-gray' ?>"><?= e(ucfirst(str_replace('_', ' ', $o['status']))) ?></span></td>
        <td><?= e(date('d M, h:i A', strtotime($o['created_at']))) ?></td>
        <td><a href="<?= base_url('admin/order-view.php?id=' . (int)$o['id']) ?>" class="btn btn-outline btn-sm">View</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
