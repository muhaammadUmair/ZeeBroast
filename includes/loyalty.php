<?php
/**
 * Configurable customer loyalty/referral points system.
 *
 * Qualifying amount for EARNING points = subtotal
 *   + delivery fee (only if 'loyalty_earn_on_delivery_fee' setting is enabled)
 *   - discount/coupon amount (only if 'loyalty_earn_after_discount' setting is enabled).
 * This keeps the calculation consistent and documented in one place (see loyalty_qualifying_amount()).
 *
 * Points are always whole numbers (floor()). Monetary values use DECIMAL(10,2) columns
 * and are rounded with round($x, 2) at calculation boundaries to avoid float drift.
 *
 * The whole feature is modular: every entry point checks loyalty_enabled() first, so it can be
 * switched off from Admin > Settings without touching any other module.
 */

/** Ensures loyalty-related tables/columns exist. Cheap no-op after the first run of a request. */
function ensure_loyalty_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo = db();

    $userCols = $pdo->query("SHOW COLUMNS FROM users LIKE 'allow_referral_points'")->fetchAll();
    if (empty($userCols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN allow_referral_points TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
    }

    $orderColumns = [
        'loyalty_points_earned'      => "ALTER TABLE orders ADD COLUMN loyalty_points_earned INT UNSIGNED NOT NULL DEFAULT 0",
        'loyalty_points_awarded'     => "ALTER TABLE orders ADD COLUMN loyalty_points_awarded TINYINT(1) NOT NULL DEFAULT 0",
        'loyalty_points_reversed'    => "ALTER TABLE orders ADD COLUMN loyalty_points_reversed TINYINT(1) NOT NULL DEFAULT 0",
        'loyalty_points_redeemed'    => "ALTER TABLE orders ADD COLUMN loyalty_points_redeemed INT UNSIGNED NOT NULL DEFAULT 0",
        'loyalty_discount'           => "ALTER TABLE orders ADD COLUMN loyalty_discount DECIMAL(10,2) NOT NULL DEFAULT 0",
        'loyalty_referral_triggered' => "ALTER TABLE orders ADD COLUMN loyalty_referral_triggered TINYINT(1) NOT NULL DEFAULT 0",
        'loyalty_referral_owner_id'  => "ALTER TABLE orders ADD COLUMN loyalty_referral_owner_id INT UNSIGNED NULL",
    ];
    foreach ($orderColumns as $column => $alterSql) {
        $exists = $pdo->query("SHOW COLUMNS FROM orders LIKE '" . $column . "'")->fetchAll();
        if (empty($exists)) {
            $pdo->exec($alterSql);
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_points_transactions (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        order_id INT UNSIGNED NULL,
        transaction_type ENUM('EARN','REDEEM','ADJUSTMENT','REFUND','EXPIRATION') NOT NULL,
        points INT NOT NULL,
        monetary_value DECIMAL(10,2) NOT NULL DEFAULT 0,
        description VARCHAR(255) NULL,
        created_by_admin_id INT UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY fk_loyalty_tx_user (user_id),
        KEY fk_loyalty_tx_order (order_id),
        CONSTRAINT fk_loyalty_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_loyalty_tx_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Table may already exist (e.g. from an earlier partial setup) without every column — patch it up.
    $ledgerExists = $pdo->query("SHOW TABLES LIKE 'loyalty_points_transactions'")->fetchAll();
    if (!empty($ledgerExists)) {
        $adminCol = $pdo->query("SHOW COLUMNS FROM loyalty_points_transactions LIKE 'created_by_admin_id'")->fetchAll();
        if (empty($adminCol)) {
            $pdo->exec('ALTER TABLE loyalty_points_transactions ADD COLUMN created_by_admin_id INT UNSIGNED NULL AFTER description');
        }
    }

    $defaults = [
        'loyalty_points_enabled'          => '0',
        'loyalty_spend_amount_per_point'  => '100',
        'loyalty_point_value'             => '1',
        'loyalty_earn_on_delivery_fee'    => '0',
        'loyalty_earn_after_discount'     => '1',
        'loyalty_award_status'            => 'delivered',
        'loyalty_max_redeem_percent'      => '100',
    ];
    $insert = $pdo->prepare('INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)');
    foreach ($defaults as $key => $value) {
        $insert->execute([$key, $value]);
    }
}

/** Whether the loyalty program is switched on at all (Admin > Settings). */
function loyalty_enabled(): bool
{
    ensure_loyalty_schema();
    return setting('loyalty_points_enabled', '0') === '1';
}

/** Central, admin-configurable loyalty settings. Never hard-code these values elsewhere. */
function loyalty_config(): array
{
    ensure_loyalty_schema();
    return [
        'enabled'                 => setting('loyalty_points_enabled', '0') === '1',
        'spend_amount_per_point'  => (float)setting('loyalty_spend_amount_per_point', '100'),
        'point_value'             => (float)setting('loyalty_point_value', '1'),
        'earn_on_delivery_fee'    => setting('loyalty_earn_on_delivery_fee', '0') === '1',
        'earn_after_discount'     => setting('loyalty_earn_after_discount', '1') === '1',
        'award_status'            => setting('loyalty_award_status', 'delivered'),
        'max_redeem_percent'      => max(0, min(100, (float)setting('loyalty_max_redeem_percent', '100'))),
    ];
}

/** Whether a specific customer is eligible to earn/redeem points (admin-controlled flag + global switch). */
function user_allows_loyalty(int $userId): bool
{
    if ($userId <= 0 || !loyalty_enabled()) {
        return false;
    }
    $stmt = db()->prepare('SELECT allow_referral_points FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return ((int)$stmt->fetchColumn()) === 1;
}

/** Qualifying amount used to calculate EARNED points. See file header for the documented rule. */
function loyalty_qualifying_amount(float $subtotal, float $deliveryFee, float $discount, ?array $config = null): float
{
    $config = $config ?? loyalty_config();
    $amount = $subtotal;
    if ($config['earn_on_delivery_fee']) {
        $amount += $deliveryFee;
    }
    if ($config['earn_after_discount']) {
        $amount -= $discount;
    }
    return round(max(0, $amount), 2);
}

/** points_earned = floor(qualifying_amount / spend_amount_per_point). Always a whole number. */
function loyalty_calculate_points_earned(float $qualifyingAmount, ?array $config = null): int
{
    $config = $config ?? loyalty_config();
    $spendAmount = $config['spend_amount_per_point'];
    if ($spendAmount <= 0 || $qualifyingAmount <= 0) {
        return 0;
    }
    return (int)floor($qualifyingAmount / $spendAmount);
}

/** Monetary value of a given number of points, rounded to 2 decimals. */
function loyalty_points_value(int $points, ?array $config = null): float
{
    $config = $config ?? loyalty_config();
    return round($points * $config['point_value'], 2);
}

/** Current available balance, calculated safely from the ledger (never a cached counter). */
function loyalty_balance(int $userId): int
{
    ensure_loyalty_schema();
    $stmt = db()->prepare('SELECT COALESCE(SUM(points), 0) FROM loyalty_points_transactions WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/** Same as loyalty_balance() but locks the user row so concurrent redemptions serialize safely. */
function loyalty_balance_for_update(PDO $pdo, int $userId): int
{
    ensure_loyalty_schema();
    // Locks the user's row for the duration of the caller's transaction (SELECT ... FOR UPDATE).
    $lock = $pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
    $lock->execute([$userId]);

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(points), 0) FROM loyalty_points_transactions WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function loyalty_add_transaction(PDO $pdo, int $userId, ?int $orderId, string $type, int $points, float $monetaryValue, string $description, ?int $adminId = null): int
{
    ensure_loyalty_schema();
    $stmt = $pdo->prepare('INSERT INTO loyalty_points_transactions (user_id, order_id, transaction_type, points, monetary_value, description, created_by_admin_id) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$userId, $orderId, $type, $points, round($monetaryValue, 2), $description, $adminId]);
    return (int)$pdo->lastInsertId();
}

/** Maximum number of points a customer may redeem against a given payable order amount right now. */
function loyalty_max_redeemable_points(int $userId, float $payableAmount, ?array $config = null): int
{
    $config = $config ?? loyalty_config();
    if (!$config['enabled'] || $config['point_value'] <= 0) {
        return 0;
    }
    $balance = loyalty_balance($userId);
    if ($balance <= 0 || $payableAmount <= 0) {
        return 0;
    }
    $maxValue = round($payableAmount * ($config['max_redeem_percent'] / 100), 2);
    $maxPointsByValue = (int)floor($maxValue / $config['point_value']);
    return max(0, min($balance, $maxPointsByValue));
}

/**
 * Server-side validated redemption. Recalculates everything from the database — never trusts
 * frontend-submitted balances/values. Must be called inside the caller's DB transaction so the
 * row lock from loyalty_balance_for_update() covers the whole order-placement operation
 * (prevents double-redemption from concurrent requests/tabs).
 *
 * Returns ['points' => int, 'value' => float] for the points actually applied (may be less than requested).
 */
function loyalty_redeem_points(PDO $pdo, int $userId, int $requestedPoints, float $payableAmount, ?array $config = null): array
{
    $config = $config ?? loyalty_config();
    if ($requestedPoints <= 0 || !$config['enabled'] || !user_allows_loyalty($userId) || $payableAmount <= 0) {
        return ['points' => 0, 'value' => 0.0];
    }

    $balance = loyalty_balance_for_update($pdo, $userId);
    $maxValue = round($payableAmount * ($config['max_redeem_percent'] / 100), 2);
    $maxPointsByValue = $config['point_value'] > 0 ? (int)floor($maxValue / $config['point_value']) : 0;

    $points = max(0, min($requestedPoints, $balance, $maxPointsByValue));
    $value = loyalty_points_value($points, $config);

    return ['points' => $points, 'value' => $value];
}

/**
 * Who should receive loyalty points for this order: the referral code's owner if one was applied
 * (even for a guest/other-user order), otherwise the order's own account holder (if any).
 */
function loyalty_points_recipient(array $order): ?int
{
    if (!empty($order['loyalty_referral_owner_id'])) {
        return (int)$order['loyalty_referral_owner_id'];
    }
    return !empty($order['user_id']) ? (int)$order['user_id'] : null;
}

/**
 * Awards points for a qualifying order exactly once. Uses an atomic UPDATE ... WHERE
 * loyalty_points_awarded = 0 guard so concurrent/duplicate calls (e.g. repeated status saves)
 * can never award the same order twice.
 */
function loyalty_award_points_for_order(int $orderId): void
{
    ensure_loyalty_schema();
    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order || (int)$order['loyalty_points_awarded'] === 1) {
        return;
    }

    $config = loyalty_config();
    if (!$config['enabled'] || $order['status'] !== $config['award_status'] || $order['payment_status'] === 'failed') {
        return;
    }

    $recipientId = loyalty_points_recipient($order);
    if ($recipientId === null) {
        return; // Guest order with no linked referral code owner — no one to credit.
    }

    // A referral code (linked to its owner via user_coupon_codes) is itself the trigger, bypassing
    // the per-customer checkbox. Without a code, normal earning still requires the checkbox.
    $referralTriggered = (int)($order['loyalty_referral_triggered'] ?? 0) === 1 && !empty($order['loyalty_referral_owner_id']);
    if (!$referralTriggered && !user_allows_loyalty($recipientId)) {
        return;
    }

    $qualifyingAmount = loyalty_qualifying_amount((float)$order['subtotal'], (float)$order['delivery_fee'], (float)$order['discount'], $config);
    $pointsEarned = loyalty_calculate_points_earned($qualifyingAmount, $config);
    if ($pointsEarned <= 0) {
        return;
    }

    $pdo->beginTransaction();
    try {
        // Atomic guard: only one concurrent request can flip awarded 0 -> 1 for this order.
        $guard = $pdo->prepare('UPDATE orders SET loyalty_points_earned = ?, loyalty_points_awarded = 1 WHERE id = ? AND loyalty_points_awarded = 0');
        $guard->execute([$pointsEarned, $orderId]);

        if ($guard->rowCount() === 1) {
            loyalty_add_transaction(
                $pdo,
                $recipientId,
                $orderId,
                'EARN',
                $pointsEarned,
                loyalty_points_value($pointsEarned, $config),
                'Order #' . $order['order_code']
            );
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Reverses previously-earned points when an order is cancelled/refunded. Never deletes the
 * original EARN transaction; instead records a REFUND transaction of the opposite sign.
 * Guarded so the reversal can only ever happen once per order.
 *
 * Note: if the customer already redeemed those points elsewhere, the reversal can drive the
 * ledger balance negative. This is intentional (per business rule) to keep the ledger accurate;
 * the balance is a derived SUM() and simply reflects that the customer now owes those points back.
 */
function loyalty_reverse_points_for_order(int $orderId): void
{
    ensure_loyalty_schema();
    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        return;
    }
    $recipientId = loyalty_points_recipient($order);
    if ($recipientId === null) {
        return;
    }
    if ((int)$order['loyalty_points_awarded'] !== 1 || (int)$order['loyalty_points_reversed'] === 1) {
        return;
    }
    $pointsEarned = (int)$order['loyalty_points_earned'];
    if ($pointsEarned <= 0) {
        return;
    }

    $config = loyalty_config();

    $pdo->beginTransaction();
    try {
        $guard = $pdo->prepare('UPDATE orders SET loyalty_points_reversed = 1 WHERE id = ? AND loyalty_points_reversed = 0');
        $guard->execute([$orderId]);

        if ($guard->rowCount() === 1) {
            loyalty_add_transaction(
                $pdo,
                $recipientId,
                $orderId,
                'REFUND',
                -$pointsEarned,
                -loyalty_points_value($pointsEarned, $config),
                'Reversal for cancelled/refunded order #' . $order['order_code']
            );
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Called by admin/order-view.php after saving status changes to trigger award/reversal side effects. */
function loyalty_handle_order_status_change(int $orderId, string $newStatus, string $newPaymentStatus): void
{
    if (!loyalty_enabled()) {
        return;
    }
    $config = loyalty_config();
    if ($newStatus === $config['award_status']) {
        loyalty_award_points_for_order($orderId);
    }
    if ($newStatus === 'cancelled' || $newPaymentStatus === 'refunded') {
        loyalty_reverse_points_for_order($orderId);
    }
}
