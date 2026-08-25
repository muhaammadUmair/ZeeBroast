<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Coupons';
$active_admin = 'coupons';
$pdo = db();
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'save') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discount_type = $_POST['discount_type'] === 'flat' ? 'flat' : 'percent';
        $discount_value = (float)($_POST['discount_value'] ?? 0);
        $min_order_amount = (float)($_POST['min_order_amount'] ?? 0);
        $expires_at = $_POST['expires_at'] !== '' ? $_POST['expires_at'] : null;
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        if ($code === '' || $discount_value <= 0) {
            $error = 'Please provide a code and a valid discount value.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, expires_at, status) VALUES (?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE discount_type=VALUES(discount_type), discount_value=VALUES(discount_value), min_order_amount=VALUES(min_order_amount), expires_at=VALUES(expires_at), status=VALUES(status)');
            $stmt->execute([$code, $discount_type, $discount_value, $min_order_amount, $expires_at, $status]);
            $success = 'Coupon saved.';
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM coupons WHERE id = ?')->execute([(int)$_POST['id']]);
        $success = 'Coupon deleted.';
    }
}

$coupons = $pdo->query('SELECT * FROM coupons ORDER BY id DESC')->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head"><h2>Add Coupon</h2></div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="form_action" value="save">
    <div class="form-grid">
      <div class="form-group"><label>Code</label><input class="form-control" name="code" placeholder="WELCOME10" required></div>
      <div class="form-group"><label>Discount Type</label>
        <select class="form-control" name="discount_type">
          <option value="percent">Percentage (%)</option>
          <option value="flat">Flat Amount (Rs.)</option>
        </select>
      </div>
      <div class="form-group"><label>Discount Value</label><input class="form-control" type="number" step="0.01" name="discount_value" required></div>
      <div class="form-group"><label>Minimum Order Amount</label><input class="form-control" type="number" step="0.01" name="min_order_amount" value="0"></div>
      <div class="form-group"><label>Expires At</label><input class="form-control" type="date" name="expires_at"></div>
      <div class="form-group"><label>Status</label>
        <select class="form-control" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
      </div>
    </div>
    <button type="submit" class="btn btn-primary">Save Coupon</button>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>All Coupons</h2></div>
  <table>
    <thead><tr><th>Code</th><th>Discount</th><th>Min Order</th><th>Expires</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($coupons)): ?><tr><td colspan="6">No coupons yet.</td></tr><?php endif; ?>
      <?php foreach ($coupons as $c): ?>
      <tr>
        <td><strong><?= e($c['code']) ?></strong></td>
        <td><?= $c['discount_type'] === 'percent' ? (int)$c['discount_value'] . '%' : money($c['discount_value']) ?></td>
        <td><?= money($c['min_order_amount']) ?></td>
        <td><?= e($c['expires_at'] ?: '—') ?></td>
        <td><span class="badge <?= $c['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= e(ucfirst($c['status'])) ?></span></td>
        <td>
          <form method="post" onsubmit="return confirm('Delete this coupon?');">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
