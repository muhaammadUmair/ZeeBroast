<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Customers';
$active_admin = 'customers';
$pdo = db();
$success = null;
$error = null;
ensure_loyalty_schema();
ensure_customer_registration_schema();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['form_action'] ?? '') === 'toggle') {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare('SELECT status FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $current = $stmt->fetchColumn();
    $new = $current === 'active' ? 'blocked' : 'active';
    $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$new, $id]);
    $success = 'Customer status updated.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['form_action'] ?? '') === 'generate_coupon') {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare('SELECT phone FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $phone = $stmt->fetchColumn();
    if ($phone === false) {
        $error = 'Customer not found.';
    } else {
        $welcome = generate_customer_referral_code($id, (string)$phone);
        $success = 'Referral code generated: ' . $welcome['code'] . ' (never expires, 0% discount)';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['form_action'] ?? '') === 'toggle_loyalty') {
    // Only admins can flip this flag — there is no customer-facing control for it.
    $id = (int)$_POST['id'];
    $pdo->prepare('UPDATE users SET allow_referral_points = 1 - allow_referral_points WHERE id = ?')->execute([$id]);
    $success = 'Loyalty points eligibility updated.';

    $nowEnabled = $pdo->prepare('SELECT allow_referral_points FROM users WHERE id = ?');
    $nowEnabled->execute([$id]);
    if ((int)$nowEnabled->fetchColumn() === 1) {
        $referral = ensure_customer_referral_code_active($id);
        $success .= ' Referral code ' . $referral['code'] . ' is now a 0%-discount, non-expiring points trigger.';
    }
}

$customers = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count, (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.user_id = u.id AND o.status != 'cancelled') AS total_spent, (SELECT COALESCE(SUM(points),0) FROM loyalty_points_transactions t WHERE t.user_id = u.id) AS points_balance, (SELECT uc.code FROM user_coupon_codes uc WHERE uc.user_id = u.id ORDER BY uc.id DESC LIMIT 1) AS referral_code, (SELECT uc.status FROM user_coupon_codes uc WHERE uc.user_id = u.id ORDER BY uc.id DESC LIMIT 1) AS referral_status FROM users u ORDER BY u.created_at DESC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head"><h2>All Customers</h2></div>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Status</th><th>Loyalty Points</th><th>Allow Points</th><th>Referral Code</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($customers)): ?><tr><td colspan="10">No customers registered yet.</td></tr><?php endif; ?>
      <?php foreach ($customers as $c): ?>
      <tr>
        <td><?= e($c['full_name']) ?></td>
        <td><?= e($c['email']) ?></td>
        <td><?= e($c['phone'] ?: '—') ?></td>
        <td><?= (int)$c['order_count'] ?></td>
        <td><?= money($c['total_spent']) ?></td>
        <td><span class="badge <?= $c['status'] === 'active' ? 'badge-green' : 'badge-red' ?>"><?= e(ucfirst($c['status'])) ?></span></td>
        <td><?= (int)$c['points_balance'] ?></td>
        <td>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="toggle_loyalty">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button type="submit" class="btn btn-outline btn-sm"><?= !empty($c['allow_referral_points']) ? '✅ Enabled' : 'Enable' ?></button>
          </form>
        </td>
        <td>
          <?php if ($c['referral_code']): ?>
            <strong><?= e($c['referral_code']) ?></strong><br><span class="badge badge-gray" style="font-size:10px"><?= e(ucfirst($c['referral_status'])) ?></span>
          <?php else: ?>
            <span class="muted">None</span>
          <?php endif; ?>
          <form method="post" style="margin-top:6px">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="generate_coupon">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Generate a new referral code for this customer?');"><?= $c['referral_code'] ? 'Regenerate' : 'Generate' ?></button>
          </form>
        </td>
        <td>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="toggle">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button type="submit" class="btn btn-outline btn-sm"><?= $c['status'] === 'active' ? 'Block' : 'Unblock' ?></button>
          </form>
          <a href="<?= base_url('admin/loyalty-points.php?user_id=' . (int)$c['id']) ?>" class="btn btn-outline btn-sm">Points</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
