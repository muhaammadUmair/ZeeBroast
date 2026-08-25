<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Orders';
$active_admin = 'orders';
$pdo = db();

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
        <td><a href="<?= base_url('admin/order-view.php?id=' . (int)$o['id']) ?>" class="btn btn-outline btn-sm">View</a></td>
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
