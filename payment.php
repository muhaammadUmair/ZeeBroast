<?php
$page_title = 'Payment Method';
require_once __DIR__ . '/includes/header.php';

if (cart_subtotal() <= 0) {
    redirect(base_url('cart.php'));
}
if (empty($_SESSION['checkout'])) {
    redirect(base_url('checkout.php'));
}

$checkout = $_SESSION['checkout'];
$subtotal = cart_subtotal();
$deliveryFee = $checkout['order_type'] === 'takeaway' ? 0.0 : cart_delivery_fee();
$discount = 0.0;
$couponCode = $_SESSION['coupon_code'] ?? null;
$isReferralCode = false;
$referralOwnerId = null;

if ($couponCode) {
    ensure_referral_coupon_schema();
    $c = db()->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND (expires_at IS NULL OR expires_at >= CURDATE())");
    $c->execute([$couponCode]);
    $coupon = $c->fetch();
    if ($coupon && $subtotal >= $coupon['min_order_amount']) {
        $isReferralCode = is_referral_coupon($coupon);
        // Referral codes are a points trigger only — they never discount the order.
        $discount = $isReferralCode ? 0.0 : ($coupon['discount_type'] === 'percent' ? $subtotal * ($coupon['discount_value'] / 100) : (float)$coupon['discount_value']);

        if ($isReferralCode) {
            // user_coupon_codes is the ownership record: it links a code back to the specific
            // customer it was generated for. That owner — not whoever checks out — earns the points.
            $ownerStmt = db()->prepare('SELECT user_id FROM user_coupon_codes WHERE code = ? LIMIT 1');
            $ownerStmt->execute([$couponCode]);
            $ownerId = $ownerStmt->fetchColumn();
            $referralOwnerId = $ownerId !== false ? (int)$ownerId : null;
        }
    }
}

$total = max(0, $subtotal + $deliveryFee - $discount);
$error = null;

$userId = $checkout['customer_id'] ?? ($_SESSION['user_id'] ?? null);
$loyaltyConfig = loyalty_config();
$loyaltyEligible = $userId && user_allows_loyalty((int)$userId);
$loyaltyBalance = $loyaltyEligible ? loyalty_balance((int)$userId) : 0;
$loyaltyMaxPoints = $loyaltyEligible ? loyalty_max_redeemable_points((int)$userId, $total, $loyaltyConfig) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session, please try again.';
    } else {
        $method = in_array($_POST['payment_method'] ?? '', ['cod', 'jazzcash', 'easypaisa', 'card']) ? $_POST['payment_method'] : 'cod';
        $requestedPoints = max(0, (int)($_POST['redeem_points'] ?? 0));

        try {
            $pdo = db();
            $pdo->beginTransaction();

            // Server-side recalculation only — the frontend-submitted point count is just a request,
            // never trusted for the actual discount amount.
            $redemption = ['points' => 0, 'value' => 0.0];
            if ($loyaltyEligible && $requestedPoints > 0) {
                $redemption = loyalty_redeem_points($pdo, (int)$userId, $requestedPoints, $total, $loyaltyConfig);
            }
            $loyaltyDiscount = $redemption['value'];
            $finalTotal = max(0, round($total - $loyaltyDiscount, 2));

            $orderCode = generate_order_code();
            $estMinutes = (int)setting('estimated_delivery_minutes', 30);

            $stmt = $pdo->prepare("INSERT INTO orders
                (order_code, user_id, guest_name, guest_phone, guest_email, order_type, house_no, street, city,
                 delivery_instructions, delivery_time_option, scheduled_time, payment_method, subtotal, delivery_fee,
                 discount, coupon_code, total, estimated_minutes, loyalty_points_redeemed, loyalty_discount, loyalty_referral_triggered, loyalty_referral_owner_id)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $orderCode,
                $userId,
                $checkout['name'], $checkout['phone'], $checkout['email'] ?: null,
                $checkout['order_type'],
                $checkout['house_no'] ?: null, $checkout['street'] ?: null, $checkout['city'] ?: null,
                $checkout['instructions'] ?: null,
                $checkout['time_option'],
                $checkout['time_option'] === 'scheduled' && $checkout['scheduled_time'] ? $checkout['scheduled_time'] : null,
                $method, $subtotal, $deliveryFee, $discount, $couponCode, $finalTotal, $estMinutes,
                $redemption['points'], $loyaltyDiscount, $referralOwnerId ? 1 : 0, $referralOwnerId,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, deal_id, item_name, unit_price, quantity, line_total) VALUES (?,?,?,?,?,?,?)");
            foreach (cart_items() as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['type'] === 'product' ? $item['id'] : null,
                    $item['type'] === 'deal' ? $item['id'] : null,
                    $item['name'], $item['price'], $item['qty'], $item['price'] * $item['qty'],
                ]);
            }

            if ($redemption['points'] > 0) {
                loyalty_add_transaction(
                    $pdo,
                    (int)$userId,
                    $orderId,
                    'REDEEM',
                    -$redemption['points'],
                    -$redemption['value'],
                    'Redeemed on order #' . $orderCode
                );
            }

            if ($couponCode && !empty($userId)) {
                mark_coupon_used((int)$userId, $couponCode);
            }

            $pdo->commit();

            // Sync failures must not roll back the already-committed order.
            try {
                sync_order_to_pos($orderId);
            } catch (Throwable $syncError) {
                error_log('POS sync failed for order ' . $orderCode . ': ' . $syncError->getMessage());
            }

            cart_clear();
            unset($_SESSION['checkout'], $_SESSION['checkout_selected_customer_id'], $_SESSION['coupon_code'], $_SESSION['pending_referral_code']);

            redirect(base_url('order-confirmation.php?code=' . urlencode($orderCode)));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Something went wrong placing your order. Please try again.';
        }
    }
}
?>

<div class="page-header">
  <div class="container">
    <h1>Payment Method</h1>
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / <a href="<?= base_url('checkout.php') ?>">Checkout</a> / Payment</div>
  </div>
</div>

<div class="section">
  <div class="container flow-wrap">
    <div class="flow-card">
      <div class="flow-title"><a href="<?= base_url('checkout.php') ?>">←</a> Payment Method</div>

      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <form method="post">
        <?= csrf_field() ?>

        <label class="pay-option selected"><input type="radio" name="payment_method" value="cod" checked><span class="p-icon">💵</span> Cash on Delivery</label>
        <!-- <label class="pay-option"><input type="radio" name="payment_method" value="jazzcash"><span class="p-icon">📱</span> JazzCash</label>
        <label class="pay-option"><input type="radio" name="payment_method" value="easypaisa"><span class="p-icon">📱</span> EasyPaisa</label>
        <label class="pay-option"><input type="radio" name="payment_method" value="card"><span class="p-icon">💳</span> Debit / Credit Card</label> -->

        <?php if ($loyaltyEligible && $loyaltyMaxPoints > 0): ?>
        <div class="form-group" style="margin-top:18px">
          <label>Loyalty Points</label>
          <p class="muted" style="font-size:12.5px;margin-bottom:8px">Available Points: <strong><?= (int)$loyaltyBalance ?></strong> · Point Value: <?= money_precise($loyaltyConfig['point_value']) ?> · Max usable now: <?= (int)$loyaltyMaxPoints ?> (<?= money_precise(loyalty_points_value($loyaltyMaxPoints, $loyaltyConfig)) ?>)</p>
          <input class="form-control" type="number" min="0" max="<?= (int)$loyaltyMaxPoints ?>" step="1" name="redeem_points" id="redeemPoints" value="0" oninput="updateLoyaltyPreview()">
          <p class="muted" style="font-size:12.5px;margin-top:6px">Discount from points: <strong id="loyaltyDiscountPreview"><?= money_precise(0) ?></strong></p>
        </div>
        <?php endif; ?>

        <div style="margin-top:20px">
          <div class="summary-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
          <div class="summary-row"><span>Delivery Fee</span><span><?= $deliveryFee > 0 ? money($deliveryFee) : 'Free' ?></span></div>
          <?php if ($discount > 0): ?><div class="summary-row"><span>Discount</span><span>-<?= money($discount) ?></span></div><?php endif; ?>
          <?php if ($loyaltyEligible && $loyaltyMaxPoints > 0): ?><div class="summary-row"><span>Loyalty Discount</span><span id="loyaltyDiscountRow">-<?= money_precise(0) ?></span></div><?php endif; ?>
          <div class="summary-row total"><span>Total</span><span id="orderTotal"><?= money($total) ?></span></div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top:20px">Place Order</button>
      </form>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.pay-option input').forEach(r => r.addEventListener('change', () => {
  document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('selected'));
  r.closest('.pay-option').classList.add('selected');
}));

// Client-side preview only — the server always recalculates and validates the real discount.
const loyaltyPointValue = <?= json_encode($loyaltyConfig['point_value']) ?>;
const loyaltyOrderTotal = <?= json_encode($total) ?>;
const currencySymbol = <?= json_encode(setting('currency_symbol', 'Rs.')) ?>;

function formatMoney(amount) {
  // Mirrors money_precise(): show up to 2 decimals, trimmed, so fractional point values display correctly.
  const rounded = Math.round(amount * 100) / 100;
  return currencySymbol + ' ' + rounded.toLocaleString(undefined, { maximumFractionDigits: 2 });
}

function updateLoyaltyPreview() {
  const input = document.getElementById('redeemPoints');
  if (!input) return;
  const points = Math.max(0, parseInt(input.value || '0', 10));
  const discount = Math.min(points * loyaltyPointValue, loyaltyOrderTotal);
  const previewEl = document.getElementById('loyaltyDiscountPreview');
  const rowEl = document.getElementById('loyaltyDiscountRow');
  const totalEl = document.getElementById('orderTotal');
  if (previewEl) previewEl.textContent = formatMoney(discount);
  if (rowEl) rowEl.textContent = '-' + formatMoney(discount);
  if (totalEl) totalEl.textContent = formatMoney(Math.max(0, loyaltyOrderTotal - discount));
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
