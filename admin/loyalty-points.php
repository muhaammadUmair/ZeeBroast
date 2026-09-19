<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
ensure_loyalty_schema();

$pdo = db();
$userId = (int)($_GET['user_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$customer = $stmt->fetch();
if (!$customer) {
    redirect(base_url('admin/customers.php'));
}

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'toggle_eligibility') {
        $pdo->prepare('UPDATE users SET allow_referral_points = 1 - allow_referral_points WHERE id = ?')->execute([$userId]);
        $success = 'Loyalty points eligibility updated.';

        $nowEnabled = $pdo->prepare('SELECT allow_referral_points FROM users WHERE id = ?');
        $nowEnabled->execute([$userId]);
        if ((int)$nowEnabled->fetchColumn() === 1) {
            $referral = ensure_customer_referral_code_active($userId);
            $success .= ' Referral code ' . $referral['code'] . ' is now a 0%-discount, non-expiring points trigger.';
        }
    } elseif ($formAction === 'adjust') {
        $direction = ($_POST['direction'] ?? 'add') === 'deduct' ? -1 : 1;
        $points = abs((int)($_POST['points'] ?? 0));
        $reason = trim($_POST['reason'] ?? '');

        if ($points <= 0) {
            $error = 'Enter a valid number of points.';
        } elseif ($reason === '') {
            $error = 'Please provide an adjustment reason.';
        } else {
            $signedPoints = $points * $direction;
            $config = loyalty_config();
            loyalty_add_transaction(
                $pdo,
                $userId,
                null,
                'ADJUSTMENT',
                $signedPoints,
                loyalty_points_value($signedPoints, $config),
                $reason,
                (int)($_SESSION['admin_id'] ?? 0)
            );
            $success = 'Points adjustment recorded.';
        }
    }

    // Re-fetch after any change.
    $stmt->execute([$userId]);
    $customer = $stmt->fetch();
}

$balance = loyalty_balance($userId);
$config = loyalty_config();

$referralCodeStmt = $pdo->prepare('SELECT uc.code FROM user_coupon_codes uc INNER JOIN coupons c ON c.code = uc.code WHERE uc.user_id = ? AND c.is_referral = 1 ORDER BY uc.id DESC LIMIT 1');
$referralCodeStmt->execute([$userId]);
$referralCode = $referralCodeStmt->fetchColumn() ?: null;
$referralShareUrl = $referralCode ? base_url('index.php?ref=' . urlencode($referralCode)) : null;
$referralQrUrl = $referralShareUrl ? 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($referralShareUrl) : null;

$txStmt = $pdo->prepare('SELECT * FROM loyalty_points_transactions WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 100');
$txStmt->execute([$userId]);
$transactions = $txStmt->fetchAll();

$admin_page_title = 'Loyalty Points — ' . $customer['full_name'];
$active_admin = 'customers';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="form-grid">
  <div>
    <div class="panel">
      <div class="panel-head"><h2>Customer</h2></div>
      <p style="color:var(--muted);font-size:12px">Name</p><p style="margin-bottom:10px"><?= e($customer['full_name']) ?></p>
      <p style="color:var(--muted);font-size:12px">Email</p><p style="margin-bottom:10px"><?= e($customer['email']) ?></p>
      <p style="color:var(--muted);font-size:12px">Available Points</p><p style="margin-bottom:10px;font-size:20px;font-weight:700"><?= (int)$balance ?> <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= money_precise(loyalty_points_value($balance, $config)) ?>)</span></p>

      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="toggle_eligibility">
        <div class="checkbox-row"><input type="checkbox" disabled <?= !empty($customer['allow_referral_points']) ? 'checked' : '' ?>><label style="margin:0">Allow Referral/Loyalty Points</label></div>
        <button type="submit" class="btn btn-outline btn-block" style="margin-top:10px"><?= !empty($customer['allow_referral_points']) ? 'Disable for this customer' : 'Enable for this customer' ?></button>
      </form>
    </div>

    <?php if ($referralCode): ?>
    <div class="panel">
      <div class="panel-head"><h2>Referral QR Code</h2></div>
      <p style="color:var(--muted);font-size:12px">Code</p><p style="margin-bottom:10px"><strong><?= e($referralCode) ?></strong></p>
      <img src="<?= e($referralQrUrl) ?>" alt="Referral QR code" style="border-radius:8px;margin-bottom:10px" width="580" height="580">
      <p style="color:var(--muted);font-size:12px">Share Link</p>
      <p style="word-break:break-all;font-size:12.5px"><?= e($referralShareUrl) ?></p>
      <p style="color:var(--muted);font-size:11.5px;margin-top:8px">Scanning this QR opens the site with the code pre-applied — it's auto-filled at checkout.</p>
    </div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-head"><h2>Manual Adjustment</h2></div>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="adjust">
        <div class="form-group"><label>Direction</label>
          <select class="form-control" name="direction">
            <option value="add">Add Points</option>
            <option value="deduct">Deduct Points</option>
          </select>
        </div>
        <div class="form-group"><label>Points</label><input class="form-control" type="number" min="1" step="1" name="points" required></div>
        <div class="form-group"><label>Reason</label><input class="form-control" type="text" name="reason" placeholder="E.g. Customer compensation" required></div>
        <button type="submit" class="btn btn-primary btn-block">Save Adjustment</button>
      </form>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Transaction History</h2></div>
    <table>
      <thead><tr><th>Date</th><th>Type</th><th>Order</th><th>Points</th><th>Value</th><th>Description</th></tr></thead>
      <tbody>
        <?php if (empty($transactions)): ?><tr><td colspan="6">No loyalty transactions yet.</td></tr><?php endif; ?>
        <?php foreach ($transactions as $t): ?>
        <tr>
          <td><?= e(date('d M Y, h:i A', strtotime($t['created_at']))) ?></td>
          <td><?= e($t['transaction_type']) ?></td>
          <td><?= $t['order_id'] ? '#' . (int)$t['order_id'] : '—' ?></td>
          <td><?= $t['points'] > 0 ? '+' . (int)$t['points'] : (int)$t['points'] ?></td>
          <td><?= money_precise($t['monetary_value']) ?></td>
          <td><?= e($t['description'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
