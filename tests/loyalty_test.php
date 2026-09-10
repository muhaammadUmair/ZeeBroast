<?php
/**
 * Lightweight test runner for the loyalty points core calculations.
 * Run with: php tests/loyalty_test.php
 *
 * Pure calculation functions are tested without a database connection.
 * Functions that require the DB (loyalty_redeem_points, award/reverse) are covered by an
 * optional live-DB pass that only runs when the configured database is reachable, so this
 * script also works in environments without MySQL available.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/loyalty.php';

$failures = 0;
$passed = 0;

function assert_equals($expected, $actual, string $label): void
{
    global $failures, $passed;
    if ($expected == $actual && gettype($expected) === gettype($actual)) {
        $passed++;
        echo "PASS: $label\n";
    } else {
        $failures++;
        echo "FAIL: $label — expected " . var_export($expected, true) . ', got ' . var_export($actual, true) . "\n";
    }
}

echo "--- Pure calculation tests ---\n";

// Example A: Rs.100 = 1 point, Rs.1 value -> Rs.1000 order = 10 points = Rs.10
$configA = ['spend_amount_per_point' => 100.0, 'point_value' => 1.0, 'earn_on_delivery_fee' => false, 'earn_after_discount' => true];
$qtyA = loyalty_qualifying_amount(1000, 0, 0, $configA);
assert_equals(1000.0, $qtyA, 'Example A qualifying amount');
$pointsA = loyalty_calculate_points_earned($qtyA, $configA);
assert_equals(10, $pointsA, 'Example A points earned');
assert_equals(10.0, loyalty_points_value($pointsA, $configA), 'Example A points value');

// Example B: Rs.100 = 1 point, Rs.1.5 value -> Rs.1000 order = 10 points = Rs.15
$configB = ['spend_amount_per_point' => 100.0, 'point_value' => 1.5, 'earn_on_delivery_fee' => false, 'earn_after_discount' => true];
$pointsB = loyalty_calculate_points_earned(loyalty_qualifying_amount(1000, 0, 0, $configB), $configB);
assert_equals(10, $pointsB, 'Example B points earned');
assert_equals(15.0, loyalty_points_value($pointsB, $configB), 'Example B points value');

// Example C: Rs.50 = 1 point, Rs.2 value -> Rs.1000 order = 20 points = Rs.40
$configC = ['spend_amount_per_point' => 50.0, 'point_value' => 2.0, 'earn_on_delivery_fee' => false, 'earn_after_discount' => true];
$pointsC = loyalty_calculate_points_earned(loyalty_qualifying_amount(1000, 0, 0, $configC), $configC);
assert_equals(20, $pointsC, 'Example C points earned');
assert_equals(40.0, loyalty_points_value($pointsC, $configC), 'Example C points value');

// From the spec: Rs.450 qualifying, Rs.100 = 1 point -> floor(450/100) = 4 points (no fractional points)
$config450 = ['spend_amount_per_point' => 100.0, 'point_value' => 1.0, 'earn_on_delivery_fee' => false, 'earn_after_discount' => true];
assert_equals(4, loyalty_calculate_points_earned(loyalty_qualifying_amount(450, 0, 0, $config450), $config450), 'Rs.450 order earns 4 points (floor, not rounded)');

// Qualifying amount respects earn_after_discount and earn_on_delivery_fee toggles.
$configDiscount = ['spend_amount_per_point' => 100.0, 'point_value' => 1.0, 'earn_on_delivery_fee' => false, 'earn_after_discount' => true];
assert_equals(900.0, loyalty_qualifying_amount(1000, 100, 100, $configDiscount), 'Discount deducted, delivery fee excluded');

$configDeliveryIncluded = ['spend_amount_per_point' => 100.0, 'point_value' => 1.0, 'earn_on_delivery_fee' => true, 'earn_after_discount' => false];
assert_equals(1100.0, loyalty_qualifying_amount(1000, 100, 100, $configDeliveryIncluded), 'Delivery fee included, discount ignored');

// Qualifying amount never goes negative even if discount exceeds subtotal.
assert_equals(0.0, loyalty_qualifying_amount(50, 0, 100, $configDiscount), 'Qualifying amount floors at zero');

// Zero/invalid configuration must not divide by zero or award points.
$configZeroSpend = ['spend_amount_per_point' => 0.0, 'point_value' => 1.0, 'earn_on_delivery_fee' => false, 'earn_after_discount' => true];
assert_equals(0, loyalty_calculate_points_earned(1000, $configZeroSpend), 'Zero spend_amount_per_point yields 0 points (no division by zero)');

echo "\n--- Live-DB tests (loyalty_redeem_points, max redemption cap) ---\n";
try {
    $pdo = db();
    $pdo->query('SELECT 1');
    ensure_loyalty_schema();

    $pdo->beginTransaction();
    try {
        // Force the global switch on for this test run (setting() caches per-request, so this
        // must happen before the first setting()/loyalty_enabled() call below).
        $pdo->prepare("UPDATE settings SET setting_value = '1' WHERE setting_key = 'loyalty_points_enabled'")->execute();
        // Pin the earning rule so this test is deterministic regardless of live admin configuration.
        $pdo->prepare("UPDATE settings SET setting_value = '100' WHERE setting_key = 'loyalty_spend_amount_per_point'")->execute();
        $pdo->prepare("UPDATE settings SET setting_value = '1' WHERE setting_key = 'loyalty_point_value'")->execute();
        $pdo->prepare("UPDATE settings SET setting_value = 'delivered' WHERE setting_key = 'loyalty_award_status'")->execute();

        $email = 'loyalty_test_' . bin2hex(random_bytes(4)) . '@example.test';
        $pdo->prepare("INSERT INTO users (full_name, email, password, allow_referral_points) VALUES ('Loyalty Test', ?, 'x', 1)")->execute([$email]);
        $testUserId = (int)$pdo->lastInsertId();

        loyalty_add_transaction($pdo, $testUserId, null, 'ADJUSTMENT', 100, 100.0, 'Seed balance for test');
        assert_equals(100, loyalty_balance($testUserId), 'Seeded balance is 100 points');

        $config = ['spend_amount_per_point' => 100.0, 'point_value' => 1.0, 'earn_on_delivery_fee' => false, 'earn_after_discount' => true, 'enabled' => true, 'max_redeem_percent' => 100.0];

        // Order total Rs.500 — customer has 100 points worth Rs.100 (not enough to trigger the
        // "Rs.750 > Rs.500" scenario from the spec, but proves the cap logic works both ways).
        $redemption = loyalty_redeem_points($pdo, $testUserId, 100, 500.0, $config);
        assert_equals(100, $redemption['points'], 'Redeem within balance and order cap succeeds fully');
        assert_equals(100.0, $redemption['value'], 'Redeemed value matches points * point_value');

        // Spec scenario: order total Rs.500, customer effectively has more value in points than the
        // order total — redemption must be capped so the order can never go negative.
        loyalty_add_transaction($pdo, $testUserId, null, 'ADJUSTMENT', 650, 650.0, 'Top up for cap test'); // balance now 750
        $capped = loyalty_redeem_points($pdo, $testUserId, 750, 500.0, $config);
        assert_equals(500, $capped['points'], 'Redemption capped by order payable amount, not just balance');
        assert_equals(500.0, $capped['value'], 'Capped redemption never exceeds order total');

        // Requesting more than the available balance clamps to the balance.
        $pdo->prepare('DELETE FROM loyalty_points_transactions WHERE user_id = ?')->execute([$testUserId]);
        loyalty_add_transaction($pdo, $testUserId, null, 'ADJUSTMENT', 10, 10.0, 'Small balance');
        $overRequest = loyalty_redeem_points($pdo, $testUserId, 9999, 5000.0, $config);
        assert_equals(10, $overRequest['points'], 'Redemption clamped to available balance');

        // Ineligible customer (allow_referral_points = 0) can never redeem, regardless of balance.
        $pdo->prepare('UPDATE users SET allow_referral_points = 0 WHERE id = ?')->execute([$testUserId]);
        $ineligible = loyalty_redeem_points($pdo, $testUserId, 5, 500.0, $config);
        assert_equals(0, $ineligible['points'], 'Ineligible customer cannot redeem points');
    } finally {
        $pdo->rollBack(); // Never persist test data.
    }

    // loyalty_award_points_for_order() manages its own transaction internally, so this case runs
    // outside the block above (no nested transactions) and restores/cleans up explicitly afterward.
    $originalSettings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('loyalty_points_enabled','loyalty_spend_amount_per_point','loyalty_point_value','loyalty_award_status')")->fetchAll(PDO::FETCH_KEY_PAIR);

    $email2 = 'loyalty_test_' . bin2hex(random_bytes(4)) . '@example.test';
    $pdo->prepare("INSERT INTO users (full_name, email, password, allow_referral_points) VALUES ('Loyalty Referral Test', ?, 'x', 0)")->execute([$email2]);
    $referralUserId = (int)$pdo->lastInsertId();
    $referralOrderId = null;
    try {
        $pdo->prepare("UPDATE settings SET setting_value = '1' WHERE setting_key = 'loyalty_points_enabled'")->execute();
        $pdo->prepare("UPDATE settings SET setting_value = '100' WHERE setting_key = 'loyalty_spend_amount_per_point'")->execute();
        $pdo->prepare("UPDATE settings SET setting_value = '1' WHERE setting_key = 'loyalty_point_value'")->execute();
        $pdo->prepare("UPDATE settings SET setting_value = 'delivered' WHERE setting_key = 'loyalty_award_status'")->execute();

        // Simulates the real-world bug report: a GUEST (no user_id) checks out with a referral
        // code owned by $referralUserId. Points must land on the code owner, not the (nonexistent)
        // purchaser account, and despite allow_referral_points=0 on the owner's account.
        $pdo->prepare("INSERT INTO orders (order_code, user_id, order_type, payment_method, subtotal, delivery_fee, discount, total, status, payment_status, loyalty_referral_triggered, loyalty_referral_owner_id) VALUES (?, NULL, 'delivery', 'cod', 1000, 0, 0, 1000, 'delivered', 'paid', 1, ?)")->execute(['LOYTEST' . bin2hex(random_bytes(3)), $referralUserId]);
        $referralOrderId = (int)$pdo->lastInsertId();

        loyalty_award_points_for_order($referralOrderId);
        $refOrder = $pdo->prepare('SELECT loyalty_points_awarded, loyalty_points_earned FROM orders WHERE id = ?');
        $refOrder->execute([$referralOrderId]);
        $refOrderRow = $refOrder->fetch();
        assert_equals(1, (int)$refOrderRow['loyalty_points_awarded'], 'Guest order with a referral code still awards points to the code owner');
        assert_equals(10, (int)$refOrderRow['loyalty_points_earned'], 'Referral-triggered order earns floor(1000/100)=10 points');
        assert_equals(10, loyalty_balance($referralUserId), 'Points were credited to the referral code owner, not the (guest) purchaser');

        // Calling award again must not double-award (idempotency guard).
        loyalty_award_points_for_order($referralOrderId);
        $earnCount = $pdo->prepare("SELECT COUNT(*) FROM loyalty_points_transactions WHERE order_id = ? AND transaction_type = 'EARN'");
        $earnCount->execute([$referralOrderId]);
        assert_equals(1, (int)$earnCount->fetchColumn(), 'Re-running award for the same order never creates a duplicate EARN transaction');
    } finally {
        // Manual cleanup since these rows were committed outside a rolled-back transaction.
        if ($referralOrderId) {
            $pdo->prepare('DELETE FROM loyalty_points_transactions WHERE order_id = ?')->execute([$referralOrderId]);
            $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$referralOrderId]);
        }
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$referralUserId]);
        $restore = $pdo->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?');
        foreach ($originalSettings as $key => $value) {
            $restore->execute([$value, $key]);
        }
    }
} catch (Throwable $e) {
    echo "SKIPPED (no database available): " . $e->getMessage() . "\n";
}

echo "\n$passed passed, $failures failed.\n";
exit($failures > 0 ? 1 : 0);
