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

function cart_add(string $type, int $id, string $name, float $price, int $qty = 1): void
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
