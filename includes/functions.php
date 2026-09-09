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

function resolve_coupon_code(string $code, ?int $userId = null): ?array
{
    $code = trim(strtoupper((string)$code));
    if ($code === '') {
        return null;
    }

    $stmt = db()->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND (expires_at IS NULL OR expires_at >= CURDATE()) LIMIT 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    if (!$coupon) {
        return null;
    }

    if ($userId !== null) {
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
        $stmt = db()->prepare('SELECT id, full_name, email, phone FROM users WHERE id = ?');
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
