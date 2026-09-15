<?php
/**
 * Shared helper functions used across the storefront and admin panel.
 */

function setting(string $key, $default = '')
{
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        $stmt = db()->query('SELECT setting_key, setting_value FROM settings');
        foreach ($stmt->fetchAll() as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }

    return $cache[$key] ?? $default;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

function normalize_phone_number(?string $phone): string
{
    return preg_replace('/\D+/', '', (string)($phone ?? ''));
}

function ensure_customer_registration_schema(): void
{
    $pdo = db();

    $columns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll();
    $hasAddressColumn = false;
    foreach ($columns as $column) {
        if (($column['Field'] ?? '') === 'address') {
            $hasAddressColumn = true;
            break;
        }
    }

    if (!$hasAddressColumn) {
        $pdo->exec('ALTER TABLE users ADD COLUMN address TEXT NULL AFTER phone');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_coupon_codes (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        code VARCHAR(50) NOT NULL,
        discount_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00,
        status ENUM('unused','used','expired') NOT NULL DEFAULT 'unused',
        expires_at DATE NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_user_coupon_code (code),
        KEY fk_user_coupon_user (user_id),
        CONSTRAINT fk_user_coupon_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Referral codes are coupons flagged is_referral=1: unlimited use by anyone, never discount, and act as the loyalty-points trigger for the order they're applied to. */
function ensure_referral_coupon_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo = db();
    $exists = $pdo->query("SHOW COLUMNS FROM coupons LIKE 'is_referral'")->fetchAll();
    if (empty($exists)) {
        $pdo->exec('ALTER TABLE coupons ADD COLUMN is_referral TINYINT(1) NOT NULL DEFAULT 0 AFTER discount_value');
    }
}

function is_referral_coupon(array $coupon): bool
{
    return !empty($coupon['is_referral']);
}

function generate_unique_coupon_code(string $prefix = 'ZB'): string
{
    do {
        $suffix = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
        $code = $prefix . $suffix;
        $stmt = db()->prepare('SELECT id FROM coupons WHERE code = ? LIMIT 1');
        $stmt->execute([$code]);
    } while ($stmt->fetch() !== false);

    return $code;
}

function customer_whatsapp_link(string $phone, string $couponCode): string
{
    //$digits = normalize_phone_number('03035636080');
    $digits = normalize_phone_number('03334418803');
    if ($digits === '') {
        return '';
    }

    $message = 'Hello! I would like to use my 10% discount code ' . $couponCode . ' on my next order.';
    return 'https://wa.me/' . $digits . '?text=' . urlencode($message);
}

function generate_customer_welcome_coupon(int $userId, string $phone): array
{
    ensure_customer_registration_schema();

    $phoneDigits = normalize_phone_number($phone);
    $expiresAt = date('Y-m-d', strtotime('+2 days'));
    $code = generate_unique_coupon_code('ZB' . substr($phoneDigits, -4) . 'W');

    $couponStmt = db()->prepare('INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, expires_at, status) VALUES (?, ?, ?, ?, ?, ?)');
    $couponStmt->execute([$code, 'percent', 10.00, 0.00, $expiresAt, 'active']);

    $userCouponStmt = db()->prepare('INSERT INTO user_coupon_codes (user_id, code, discount_percent, status, expires_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status)');
    $userCouponStmt->execute([$userId, $code, 10.00, 'unused', $expiresAt]);

    return [
        'code' => $code,
        'expires_at' => $expiresAt,
        'whatsapp_url' => customer_whatsapp_link($phoneDigits, $code),
    ];
}

/**
 * Generates a referral/loyalty-points code for a customer: 0% discount (points trigger only),
 * unlimited use, and never expires — distinct from the one-time 10% welcome coupon above.
 */
function generate_customer_referral_code(int $userId, string $phone): array
{
    ensure_customer_registration_schema();
    ensure_referral_coupon_schema();

    $phoneDigits = normalize_phone_number($phone);
    $code = generate_unique_coupon_code('ZB' . substr($phoneDigits, -4) . 'R');

    $couponStmt = db()->prepare('INSERT INTO coupons (code, discount_type, discount_value, is_referral, min_order_amount, expires_at, status) VALUES (?, ?, ?, ?, ?, NULL, ?)');
    $couponStmt->execute([$code, 'percent', 0.00, 1, 0.00, 'active']);

    $userCouponStmt = db()->prepare('INSERT INTO user_coupon_codes (user_id, code, discount_percent, status, expires_at) VALUES (?, ?, 0.00, ?, NULL) ON DUPLICATE KEY UPDATE status = VALUES(status)');
    $userCouponStmt->execute([$userId, $code, 'unused']);

    return [
        'code' => $code,
        'expires_at' => null,
        'whatsapp_url' => customer_whatsapp_link($phoneDigits, $code),
    ];
}

/**
 * Captures a referral code from a QR/share link (?ref=CODE), validating it's a real, active
 * referral coupon before storing it in the session. Once captured it's auto-applied at checkout
 * without the customer needing to type anything, until the order is placed or they replace it.
 * Returns the code if newly captured this request (used to show a one-time confirmation banner).
 */
function capture_referral_code_from_request(): ?string
{
    if (empty($_GET['ref'])) {
        return null;
    }
    $code = strtoupper(trim((string)$_GET['ref']));
    if ($code === '') {
        return null;
    }

    ensure_referral_coupon_schema();
    $stmt = db()->prepare("SELECT id FROM coupons WHERE code = ? AND status = 'active' AND is_referral = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) LIMIT 1");
    $stmt->execute([$code]);
    if (!$stmt->fetch()) {
        return null;
    }

    $isNew = ($_SESSION['pending_referral_code'] ?? null) !== $code;
    $_SESSION['pending_referral_code'] = $code;
    return $isNew ? $code : null;
}

/**
 * Ensures a customer has an active referral/points-trigger code once their eligibility is
 * enabled: converts their existing code to 0% discount + never-expiring + is_referral, or
 * generates a fresh one if they don't have one yet.
 */
function ensure_customer_referral_code_active(int $userId): array
{
    ensure_customer_registration_schema();
    ensure_referral_coupon_schema();

    $codeStmt = db()->prepare('SELECT code FROM user_coupon_codes WHERE user_id = ? ORDER BY id DESC LIMIT 1');
    $codeStmt->execute([$userId]);
    $existingCode = $codeStmt->fetchColumn();

    if ($existingCode !== false) {
        $update = db()->prepare("UPDATE coupons SET discount_value = 0.00, is_referral = 1, expires_at = NULL, status = 'active' WHERE code = ?");
        $update->execute([$existingCode]);
        return ['code' => $existingCode, 'created' => false];
    }

    $phoneStmt = db()->prepare('SELECT phone FROM users WHERE id = ?');
    $phoneStmt->execute([$userId]);
    $phone = $phoneStmt->fetchColumn() ?: '';
    $generated = generate_customer_referral_code($userId, (string)$phone);
    return ['code' => $generated['code'], 'created' => true];
}

function resolve_coupon_code(string $code, ?int $userId = null): ?array
{
    $code = trim(strtoupper((string)$code));
    if ($code === '') {
        return null;
    }

    ensure_referral_coupon_schema();
    $stmt = db()->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND (expires_at IS NULL OR expires_at >= CURDATE()) LIMIT 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    if (!$coupon) {
        return null;
    }

    // Referral codes are unlimited-use for everyone (including the original owner) — no per-user usage check.
    if ($userId !== null && !is_referral_coupon($coupon)) {
        $usageStmt = db()->prepare('SELECT * FROM user_coupon_codes WHERE user_id = ? AND code = ? LIMIT 1');
        $usageStmt->execute([$userId, $code]);
        $usage = $usageStmt->fetch();
        if ($usage && $usage['status'] === 'used') {
            return null;
        }
    }

    return $coupon;
}

function mark_coupon_used(int $userId, string $code): void
{
    $code = trim(strtoupper((string)$code));
    if ($code === '' || $userId <= 0) {
        return;
    }

    ensure_referral_coupon_schema();
    $couponStmt = db()->prepare('SELECT is_referral FROM coupons WHERE code = ? LIMIT 1');
    $couponStmt->execute([$code]);
    $coupon = $couponStmt->fetch();
    if ($coupon && is_referral_coupon($coupon)) {
        return; // Referral codes are never marked "used" — they stay reusable indefinitely.
    }

    $stmt = db()->prepare('SELECT id FROM user_coupon_codes WHERE user_id = ? AND code = ? LIMIT 1');
    $stmt->execute([$userId, $code]);
    if ($stmt->fetch()) {
        $update = db()->prepare("UPDATE user_coupon_codes SET status = 'used', used_at = NOW() WHERE user_id = ? AND code = ? AND status != 'used'");
        $update->execute([$userId, $code]);
        return;
    }

    $insert = db()->prepare('INSERT INTO user_coupon_codes (user_id, code, discount_percent, status, expires_at, used_at) VALUES (?, ?, 10.00, ?, DATE_ADD(CURDATE(), INTERVAL 2 DAY), NOW())');
    $insert->execute([$userId, $code, 'used']);
}

function money($amount): string
{
    return setting('currency_symbol', 'Rs.') . ' ' . number_format((float)$amount);
}

/** Same as money() but keeps up to 2 decimal places (trimmed) — use for loyalty point values, which are often fractional (e.g. Rs. 1.5/point). */
function money_precise($amount): string
{
    $formatted = number_format((float)$amount, 2, '.', ',');
    $formatted = rtrim(rtrim($formatted, '0'), '.');
    return setting('currency_symbol', 'Rs.') . ' ' . $formatted;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function base_url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Returns an uploaded product/category/deal image URL, or null if none set. */
function media_url(?string $path): ?string
{
    if (empty($path)) {
        return null;
    }
    return base_url('uploads/' . ltrim($path, '/'));
}

function cart_item_image(array $item): ?string
{
    if (!empty($item['image'])) {
        return (string)$item['image'];
    }

    $type = $item['type'] ?? '';
    $id = (int)($item['id'] ?? 0);
    if ($id <= 0) {
        return null;
    }

    if ($type === 'deal') {
        $stmt = db()->prepare('SELECT image FROM deals WHERE id = ? AND status = "active" LIMIT 1');
    } elseif ($type === 'product') {
        $stmt = db()->prepare('SELECT image FROM products WHERE id = ? AND status = "active" LIMIT 1');
    } else {
        return null;
    }

    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return !empty($row['image']) ? (string)$row['image'] : null;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT id, full_name, email, phone, allow_referral_points FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        redirect(base_url('admin/login.php'));
    }
}

function current_admin(): ?array
{
    if (!is_admin_logged_in()) {
        return null;
    }
    static $admin = null;
    if ($admin === null) {
        $stmt = db()->prepare('SELECT id, full_name, email, role FROM admin_users WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch() ?: null;
    }
    return $admin;
}

function generate_order_code(): string
{
    return setting('order_id_prefix', 'ZB') . strtoupper(substr(uniqid(), -6));
}

/* ---------------------------------------------------------------------
 * Cart (stored in session as [product_id/deal_id => item array])
 * ------------------------------------------------------------------- */

function cart_init(): void
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function cart_add(string $type, int $id, string $name, float $price, int $qty = 1, ?string $image = null): void
{
    cart_init();
    $key = $type . '_' . $id;
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$key] = [
            'type'  => $type, // 'product' | 'deal'
            'id'    => $id,
            'name'  => $name,
            'price' => $price,
            'qty'   => $qty,
            'image' => $image,
        ];
    }
}

function cart_update(string $key, int $qty): void
{
    cart_init();
    if (!isset($_SESSION['cart'][$key])) {
        return;
    }
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
    } else {
        $_SESSION['cart'][$key]['qty'] = $qty;
    }
}

function cart_remove(string $key): void
{
    cart_init();
    unset($_SESSION['cart'][$key]);
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

function cart_items(): array
{
    cart_init();
    return $_SESSION['cart'];
}

function cart_count(): int
{
    $count = 0;
    foreach (cart_items() as $item) {
        $count += $item['qty'];
    }
    return $count;
}

function cart_subtotal(): float
{
    $total = 0.0;
    foreach (cart_items() as $item) {
        $total += $item['price'] * $item['qty'];
    }
    return $total;
}

function cart_delivery_fee(): float
{
    if (cart_subtotal() <= 0) {
        return 0.0;
    }
    $free_threshold = (float)setting('free_delivery_threshold', 2000);
    if ($free_threshold > 0 && cart_subtotal() >= $free_threshold) {
        return 0.0;
    }
    return (float)setting('delivery_fee', 100);
}

/**
 * Handles an uploaded image file, storing it under uploads/{subdir}/.
 * Returns the relative path (to store in DB) on success, or null if no file was uploaded.
 * Throws RuntimeException on validation failure.
 */
function handle_image_upload(string $fieldName, string $subdir): ?string
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, WEBP or GIF images are allowed.');
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        throw new RuntimeException('Image must be smaller than 4MB.');
    }

    $dir = BASE_PATH . '/uploads/' . trim($subdir, '/');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        throw new RuntimeException('Could not save uploaded image.');
    }

    return trim($subdir, '/') . '/' . $filename;
}

/** Renders a food thumbnail: an uploaded image if present, otherwise an emoji placeholder tile. */
function food_thumb(?string $image, ?string $icon, string $extraClass = ''): string
{
    $icon = $icon ?: '🍗';
    $url = media_url($image);
    if ($url) {
        return '<div class="food-thumb ' . e($extraClass) . '"><img src="' . e($url) . '" alt="" loading="lazy"></div>';
    }
    return '<div class="food-thumb ' . e($extraClass) . '">' . $icon . '</div>';
}

function format_minutes_range($minutes): string
{
    $minutes = (int)$minutes;
    return $minutes . '-' . ($minutes + 10) . ' mins';
}

function ensure_pos_sync_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    db()->exec("CREATE TABLE IF NOT EXISTS pos_order_sync (
        order_id INT UNSIGNED NOT NULL,
        pos_order_id BIGINT UNSIGNED NULL,
        sync_status ENUM('pending','synced','failed') NOT NULL DEFAULT 'pending',
        last_error VARCHAR(500) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (order_id), KEY idx_pos_order_id (pos_order_id),
        CONSTRAINT fk_pos_sync_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function pos_api_request(string $method, string $path, array $payload): array
{
    if (POS_API_URL === '' || POS_API_TOKEN === '') return ['success' => false, 'message' => 'POS integration is not configured'];
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => "Authorization: Bearer " . POS_API_TOKEN . "\r\nContent-Type: application/json\r\nAccept: application/json\r\n",
        'content' => json_encode($payload), 'timeout' => 10, 'ignore_errors' => true,
    ]]);
    $body = @file_get_contents(POS_API_URL . $path, false, $context);
    $status = 0;
    foreach (($http_response_header ?? []) as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $match)) $status = (int)$match[1];
    }
    $response = json_decode((string)$body, true);
    return is_array($response) && $status >= 200 && $status < 300 ? $response : ['success' => false, 'message' => $response['message'] ?? ('POS API HTTP ' . $status)];
}

function sync_order_to_pos(int $orderId): void
{
    ensure_pos_sync_schema();
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) return;
    $items = $pdo->prepare('SELECT item_name AS name, quantity, unit_price FROM order_items WHERE order_id = ? ORDER BY id');
    $items->execute([$orderId]);
    $payload = [
        'order_code' => $order['order_code'],
        'customer' => ['name' => $order['guest_name'], 'phone' => $order['guest_phone'], 'email' => $order['guest_email']],
        'delivery' => ['type' => $order['order_type'], 'house_no' => $order['house_no'], 'street' => $order['street'], 'city' => $order['city'], 'instructions' => $order['delivery_instructions'], 'scheduled_time' => $order['scheduled_time']],
        'payment' => ['method' => $order['payment_method'], 'status' => $order['payment_status']],
        'subtotal' => $order['subtotal'], 'delivery_fee' => $order['delivery_fee'], 'discount' => $order['discount'], 'discount_code' => $order['coupon_code'], 'total' => $order['total'],
        'items' => $items->fetchAll(),
    ];
    $pdo->prepare("INSERT INTO pos_order_sync (order_id, sync_status) VALUES (?, 'pending') ON DUPLICATE KEY UPDATE sync_status = 'pending'")->execute([$orderId]);
    $result = pos_api_request('POST', '/api/integrations/zeebroast/orders', $payload);
    if (!empty($result['success'])) {
        $pdo->prepare("UPDATE pos_order_sync SET pos_order_id = ?, sync_status = 'synced', last_error = NULL WHERE order_id = ?")->execute([(int)($result['pos_order_id'] ?? 0), $orderId]);
    } else {
        $pdo->prepare("UPDATE pos_order_sync SET sync_status = 'failed', last_error = ? WHERE order_id = ?")->execute([substr((string)($result['message'] ?? 'Unknown error'), 0, 500), $orderId]);
    }
}

function sync_order_status_to_pos(string $orderCode, ?string $status = null, ?string $paymentStatus = null): void
{
    pos_api_request('PATCH', '/api/integrations/zeebroast/orders', ['order_code' => $orderCode, 'status' => $status, 'payment_status' => $paymentStatus]);
}
