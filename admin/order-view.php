<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) redirect(base_url('admin/orders.php'));

$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $newStatus = $_POST['status'] ?? '';
    if (in_array($newStatus, ['pending','confirmed','preparing','on_the_way','delivered','cancelled'])) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
        $order['status'] = $newStatus;
        $success = 'Order status updated.';
    }
    $newPayStatus = $_POST['payment_status'] ?? '';
    if (in_array($newPayStatus, ['pending','paid','failed','refunded'])) {
        $pdo->prepare('UPDATE orders SET payment_status = ? WHERE id = ?')->execute([$newPayStatus, $id]);
        $order['payment_status'] = $newPayStatus;
    }
}

$items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$items->execute([$id]);
$items = $items->fetchAll();

$admin_page_title = 'Order #' . $order['order_code'];
$active_admin = 'orders';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="form-grid">
  <div class="panel">
    <div class="panel-head"><h2>Order Items</h2></div>
    <table>
      <thead><tr><th>Item</th><th>Unit Price</th><th>Qty</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($items as $it): ?>
        <tr>
          <td><?= e($it['item_name']) ?></td>
          <td><?= money($it['unit_price']) ?></td>
          <td><?= (int)$it['quantity'] ?></td>
          <td><?= money($it['line_total']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div style="margin-top:16px">
      <div style="display:flex;justify-content:space-between;padding:6px 0;color:var(--muted)"><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
      <div style="display:flex;justify-content:space-between;padding:6px 0;color:var(--muted)"><span>Delivery Fee</span><span><?= money($order['delivery_fee']) ?></span></div>
      <?php if ($order['discount'] > 0): ?><div style="display:flex;justify-content:space-between;padding:6px 0;color:var(--muted)"><span>Discount</span><span>-<?= money($order['discount']) ?></span></div><?php endif; ?>
      <div style="display:flex;justify-content:space-between;padding:10px 0;font-weight:700;font-size:16px;border-top:1px solid var(--border);margin-top:6px"><span>Total</span><span><?= money($order['total']) ?></span></div>
    </div>
  </div>

  <div>
    <div class="panel">
      <div class="panel-head"><h2>Customer &amp; Delivery</h2></div>
      <p style="color:var(--muted);font-size:12px">Name</p><p style="margin-bottom:10px"><?= e($order['guest_name']) ?></p>
      <p style="color:var(--muted);font-size:12px">Phone</p><p style="margin-bottom:10px"><?= e($order['guest_phone']) ?></p>
      <?php if ($order['order_type'] === 'delivery'): ?>
      <p style="color:var(--muted);font-size:12px">Address</p>
      <p style="margin-bottom:10px"><?= e($order['house_no']) ?>, <?= e($order['street']) ?>, <?= e($order['city']) ?></p>
      <?php if ($order['delivery_instructions']): ?><p style="color:var(--muted);font-size:12px">Instructions</p><p style="margin-bottom:10px"><?= e($order['delivery_instructions']) ?></p><?php endif; ?>
      <?php else: ?>
      <p style="margin-bottom:10px">Takeaway order</p>
      <?php endif; ?>
      <p style="color:var(--muted);font-size:12px">Payment Method</p><p><?= e(strtoupper($order['payment_method'])) ?></p>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Update Status</h2></div>
      <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label>Order Status</label>
          <select class="form-control" name="status">
            <?php foreach (['pending','confirmed','preparing','on_the_way','delivered','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_',' ',$s))) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Payment Status</label>
          <select class="form-control" name="payment_status">
            <?php foreach (['pending','paid','failed','refunded'] as $s): ?>
            <option value="<?= $s ?>" <?= $order['payment_status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Update</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
