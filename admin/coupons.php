<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Coupons';
$active_admin = 'coupons';
$pdo = db();
$error = null;
$success = null;
ensure_referral_coupon_schema();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'save') {
        $editId = (int)($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discount_type = $_POST['discount_type'] === 'flat' ? 'flat' : 'percent';
        $isReferral = isset($_POST['is_referral']) ? 1 : 0;
        // Referral codes are a points trigger only - force the discount to zero regardless of input.
        $discount_value = $isReferral ? 0.0 : (float)($_POST['discount_value'] ?? 0);
        $min_order_amount = (float)($_POST['min_order_amount'] ?? 0);
        $expires_at = $_POST['expires_at'] !== '' ? $_POST['expires_at'] : null;
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        if ($code === '' || $discount_value < 0) {
            $error = 'Please provide a code and a non-negative discount value.';
        } else {
            $dupe = $pdo->prepare('SELECT id FROM coupons WHERE code = ? AND id != ?');
            $dupe->execute([$code, $editId]);
            if ($dupe->fetch()) {
                $error = 'Another coupon already uses this code.';
            } elseif ($editId > 0) {
                $stmt = $pdo->prepare('UPDATE coupons SET code=?, discount_type=?, discount_value=?, min_order_amount=?, expires_at=?, status=?, is_referral=? WHERE id=?');
                $stmt->execute([$code, $discount_type, $discount_value, $min_order_amount, $expires_at, $status, $isReferral, $editId]);
                $success = 'Coupon updated.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, expires_at, status, is_referral) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$code, $discount_type, $discount_value, $min_order_amount, $expires_at, $status, $isReferral]);
                $success = 'Coupon saved.';
            }
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM coupons WHERE id = ?')->execute([(int)$_POST['id']]);
        $success = 'Coupon deleted.';
    }
}

$editingCoupon = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editingCoupon = $stmt->fetch() ?: null;
}

$coupons = $pdo->query('SELECT * FROM coupons ORDER BY id DESC')->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head"><h2><?= $editingCoupon ? 'Edit Coupon' : 'Add Coupon' ?></h2></div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="form_action" value="save">
    <input type="hidden" name="id" value="<?= (int)($editingCoupon['id'] ?? 0) ?>">
    <div class="form-grid">
      <div class="form-group"><label>Code</label><input class="form-control" name="code" value="<?= e($editingCoupon['code'] ?? '') ?>" placeholder="WELCOME10" required></div>
      <div class="form-group"><label>Discount Type</label>
        <select class="form-control" name="discount_type">
          <option value="percent" <?= ($editingCoupon['discount_type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>Percentage (%)</option>
          <option value="flat" <?= ($editingCoupon['discount_type'] ?? '') === 'flat' ? 'selected' : '' ?>>Flat Amount (Rs.)</option>
        </select>
      </div>
      <div class="form-group"><label>Discount Value</label><input class="form-control" type="number" step="0.01" min="0" name="discount_value" value="<?= e($editingCoupon['discount_value'] ?? '') ?>" required></div>
      <div class="form-group"><label>Minimum Order Amount</label><input class="form-control" type="number" step="0.01" name="min_order_amount" value="<?= e($editingCoupon['min_order_amount'] ?? '0') ?>"></div>
      <div class="form-group"><label>Expires At</label><input class="form-control" type="date" name="expires_at" value="<?= e($editingCoupon['expires_at'] ?? '') ?>"></div>
      <div class="form-group"><label>Status</label>
        <select class="form-control" name="status">
          <option value="active" <?= ($editingCoupon['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= ($editingCoupon['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>
    <div class="checkbox-row"><input type="checkbox" id="is_referral" name="is_referral" <?= !empty($editingCoupon['is_referral']) ? 'checked' : '' ?>><label for="is_referral" style="margin:0">Referral Code (unlimited use by anyone, never discounts, triggers loyalty points on the order)</label></div>
    <button type="submit" class="btn btn-primary" style="margin-top:10px"><?= $editingCoupon ? 'Update Coupon' : 'Save Coupon' ?></button>
    <?php if ($editingCoupon): ?><a href="<?= base_url('admin/coupons.php') ?>" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>All Coupons</h2></div>
  <table>
    <thead><tr><th>Code</th><th>Type</th><th>Discount</th><th>Min Order</th><th>Expires</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($coupons)): ?><tr><td colspan="7">No coupons yet.</td></tr><?php endif; ?>
      <?php foreach ($coupons as $c): ?>
      <tr>
        <td><strong><?= e($c['code']) ?></strong></td>
        <td><?= !empty($c['is_referral']) ? '<span class="badge badge-blue">Referral</span>' : '<span class="badge badge-gray">Discount</span>' ?></td>
        <td><?= !empty($c['is_referral']) ? 'Points trigger' : ($c['discount_type'] === 'percent' ? (int)$c['discount_value'] . '%' : money($c['discount_value'])) ?></td>
        <td><?= money($c['min_order_amount']) ?></td>
        <td><?= e($c['expires_at'] ?: '—') ?></td>
        <td><span class="badge <?= $c['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= e(ucfirst($c['status'])) ?></span></td>
        <td>
          <a href="<?= base_url('admin/coupons.php?edit=' . (int)$c['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this coupon?');">
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
