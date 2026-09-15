<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Orders';
$active_admin = 'orders';
$pdo = db();
$actionMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
  $orderId = (int)($_POST['order_id'] ?? 0);
  $newStatus = $_POST['status'] ?? '';
  $newPaymentStatus = $_POST['payment_status'] ?? '';

  if ($orderId > 0 && in_array($newStatus, ['pending', 'confirmed', 'preparing', 'on_the_way', 'delivered', 'cancelled'], true)
    && in_array($newPaymentStatus, ['pending', 'paid', 'failed', 'refunded'], true)) {
    $orderStmt = $pdo->prepare('SELECT order_code FROM orders WHERE id = ? LIMIT 1');
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch();

    if ($order) {
      $pdo->prepare('UPDATE orders SET status = ?, payment_status = ? WHERE id = ?')
        ->execute([$newStatus, $newPaymentStatus, $orderId]);
      loyalty_handle_order_status_change($orderId, $newStatus, $newPaymentStatus);
      sync_order_status_to_pos($order['order_code'], $newStatus, $newPaymentStatus);
      $actionMessage = 'Order updated successfully.';
    }
  }
}

$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$where = [];
$params = [];
if ($statusFilter !== '') { $where[] = 'status = ?'; $params[] = $statusFilter; }
if ($search !== '') { $where[] = '(order_code LIKE ? OR guest_name LIKE ? OR guest_phone LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM orders $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusColors = [
    'pending' => 'badge-yellow', 'confirmed' => 'badge-blue', 'preparing' => 'badge-blue',
    'on_the_way' => 'badge-blue', 'delivered' => 'badge-green', 'cancelled' => 'badge-red',
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div class="panel-head"><h2>All Orders</h2></div>
  <?php if ($actionMessage): ?><div class="alert alert-success"><?= e($actionMessage) ?></div><?php endif; ?>

  <form method="get" style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap">
    <input class="form-control" type="text" name="q" placeholder="Search order ID / name / phone" value="<?= e($search) ?>" style="max-width:260px">
    <select class="form-control" name="status" style="max-width:200px">
      <option value="">All Statuses</option>
      <?php foreach (['pending','confirmed','preparing','on_the_way','delivered','cancelled'] as $s): ?>
      <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_',' ',$s))) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline" type="submit">Filter</button>
  </form>

  <table>
    <thead><tr><th>Order ID</th><th>Customer</th><th>Type</th><th>Payment</th><th>Total</th><th>Status</th><th>Placed</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($orders)): ?><tr><td colspan="8">No orders found.</td></tr><?php endif; ?>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td>#<?= e($o['order_code']) ?></td>
        <td><?= e($o['guest_name']) ?><br><span style="color:var(--muted);font-size:11.5px"><?= e($o['guest_phone']) ?></span></td>
        <td><?= e(ucfirst($o['order_type'])) ?></td>
        <td><?= e(strtoupper($o['payment_method'])) ?></td>
        <td><?= money($o['total']) ?></td>
        <td><span class="badge <?= $statusColors[$o['status']] ?? 'badge-gray' ?>"><?= e(ucfirst(str_replace('_', ' ', $o['status']))) ?></span></td>
        <td><?= e(date('d M, h:i A', strtotime($o['created_at']))) ?></td>
        <td>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="<?= base_url('admin/order-view.php?id=' . (int)$o['id']) ?>" class="btn btn-outline btn-sm">View</a>
            <?php if ($o['status'] !== 'delivered' && $o['status'] !== 'cancelled'): ?>
            <form method="post" action="<?= base_url('admin/orders.php') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
              <input type="hidden" name="status" value="delivered">
              <input type="hidden" name="payment_status" value="<?= e($o['payment_status']) ?>">
              <button type="submit" class="btn btn-primary btn-sm">Delivered</button>
            </form>
            <?php endif; ?>
            <?php if ($o['payment_status'] !== 'paid'): ?>
            <form method="post" action="<?= base_url('admin/orders.php') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
              <input type="hidden" name="status" value="<?= e($o['status']) ?>">
              <input type="hidden" name="payment_status" value="paid">
              <button type="submit" class="btn btn-outline btn-sm">Mark Paid</button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): $q = array_merge($_GET, ['page' => $i]); ?>
    <a href="?<?= http_build_query($q) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
