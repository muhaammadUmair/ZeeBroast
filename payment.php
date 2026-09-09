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

if ($couponCode) {
    $c = db()->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND (expires_at IS NULL OR expires_at >= CURDATE())");
    $c->execute([$couponCode]);
    $coupon = $c->fetch();
    if ($coupon && $subtotal >= $coupon['min_order_amount']) {
        $discount = $coupon['discount_type'] === 'percent' ? $subtotal * ($coupon['discount_value'] / 100) : (float)$coupon['discount_value'];
    }
}

$total = max(0, $subtotal + $deliveryFee - $discount);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session, please try again.';
    } else {
        $method = in_array($_POST['payment_method'] ?? '', ['cod', 'jazzcash', 'easypaisa', 'card']) ? $_POST['payment_method'] : 'cod';

        try {
            $pdo = db();
            $pdo->beginTransaction();

            $orderCode = generate_order_code();
            $estMinutes = (int)setting('estimated_delivery_minutes', 30);

            $stmt = $pdo->prepare("INSERT INTO orders
                (order_code, user_id, guest_name, guest_phone, guest_email, order_type, house_no, street, city,
                 delivery_instructions, delivery_time_option, scheduled_time, payment_method, subtotal, delivery_fee,
                 discount, coupon_code, total, estimated_minutes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $orderCode,
                $_SESSION['user_id'] ?? null,
                $checkout['name'], $checkout['phone'], $checkout['email'] ?: null,
                $checkout['order_type'],
                $checkout['house_no'] ?: null, $checkout['street'] ?: null, $checkout['city'] ?: null,
                $checkout['instructions'] ?: null,
                $checkout['time_option'],
                $checkout['time_option'] === 'scheduled' && $checkout['scheduled_time'] ? $checkout['scheduled_time'] : null,
                $method, $subtotal, $deliveryFee, $discount, $couponCode, $total, $estMinutes,
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

            if ($couponCode && !empty($_SESSION['user_id'])) {
                mark_coupon_used((int)$_SESSION['user_id'], $couponCode);
            }

            $pdo->commit();

            cart_clear();
            unset($_SESSION['checkout'], $_SESSION['coupon_code']);

            redirect(base_url('order-confirmation.php?code=' . urlencode($orderCode)));
        } catch (Throwable $e) {
            $pdo->rollBack();
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
        <label class="pay-option"><input type="radio" name="payment_method" value="jazzcash"><span class="p-icon">📱</span> JazzCash</label>
        <label class="pay-option"><input type="radio" name="payment_method" value="easypaisa"><span class="p-icon">📱</span> EasyPaisa</label>
        <label class="pay-option"><input type="radio" name="payment_method" value="card"><span class="p-icon">💳</span> Debit / Credit Card</label>

        <div style="margin-top:20px">
          <div class="summary-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
          <div class="summary-row"><span>Delivery Fee</span><span><?= $deliveryFee > 0 ? money($deliveryFee) : 'Free' ?></span></div>
          <?php if ($discount > 0): ?><div class="summary-row"><span>Discount</span><span>-<?= money($discount) ?></span></div><?php endif; ?>
          <div class="summary-row total"><span>Total</span><span><?= money($total) ?></span></div>
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
