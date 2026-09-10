<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Site Settings';
$active_admin = 'settings';
$pdo = db();
$success = null;

ensure_loyalty_schema();

$fields = [
    'site_name', 'site_tagline', 'phone', 'email', 'address',
    'fcon_image', 'logo_image',
    'facebook_url', 'instagram_url', 'twitter_url',
    'delivery_fee', 'free_delivery_threshold', 'min_order_amount', 'estimated_delivery_minutes',
    'currency_symbol', 'primary_color', 'dark_bg',
    'hero_title_line1', 'hero_title_line2', 'hero_title_line3', 'hero_subtitle',
    'order_id_prefix', 'halal_badge',
];
$checkboxFields = ['halal_badge'];
$loyaltyFields = [
    'loyalty_points_enabled', 'loyalty_spend_amount_per_point', 'loyalty_point_value',
    'loyalty_earn_on_delivery_fee', 'loyalty_earn_after_discount', 'loyalty_award_status',
    'loyalty_max_redeem_percent',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($fields as $key) {
        $stmt->execute([$key, in_array($key, $checkboxFields, true) ? (isset($_POST[$key]) ? '1' : '0') : trim($_POST[$key] ?? '')]);
    }
    foreach ($loyaltyFields as $key) {
        if (in_array($key, ['loyalty_spend_amount_per_point', 'loyalty_point_value', 'loyalty_max_redeem_percent'], true)) {
            $stmt->execute([$key, (string)max(0, (float)($_POST[$key] ?? 0))]);
        } elseif ($key === 'loyalty_award_status') {
            $status = $_POST[$key] ?? 'delivered';
            $stmt->execute([$key, in_array($status, ['pending','confirmed','preparing','on_the_way','delivered'], true) ? $status : 'delivered']);
        } else {
            $stmt->execute([$key, isset($_POST[$key]) ? '1' : '0']);
        }
    }
    $success = 'Settings saved successfully.';
}

$current = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
    $current[$row['setting_key']] = $row['setting_value'];
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<form method="post">
  <?= csrf_field() ?>

  <div class="panel">
    <div class="panel-head"><h2>General</h2></div>
    <div class="form-grid">
      <div class="form-group"><label>Site Name</label><input class="form-control" name="site_name" value="<?= e($current['site_name'] ?? '') ?>"></div>
      <div class="form-group"><label>Tagline</label><input class="form-control" name="site_tagline" value="<?= e($current['site_tagline'] ?? '') ?>"></div>
      <div class="form-group"><label>Favicon Image</label><input class="form-control" name="fcon_image" value="<?= e($current['fcon_image'] ?? '') ?>" placeholder="favicon.png or uploads/favicon.png"></div>
      <div class="form-group"><label>Logo Image</label><input class="form-control" name="logo_image" value="<?= e($current['logo_image'] ?? '') ?>" placeholder="logo.png or uploads/logo.png"></div>
      <div class="form-group"><label>Currency Symbol</label><input class="form-control" name="currency_symbol" value="<?= e($current['currency_symbol'] ?? '') ?>"></div>
      <div class="form-group"><label>Order ID Prefix</label><input class="form-control" name="order_id_prefix" value="<?= e($current['order_id_prefix'] ?? '') ?>"></div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Hero Section</h2></div>
    <div class="form-grid">
      <div class="form-group"><label>Hero Line 1</label><input class="form-control" name="hero_title_line1" value="<?= e($current['hero_title_line1'] ?? '') ?>"></div>
      <div class="form-group"><label>Hero Line 2</label><input class="form-control" name="hero_title_line2" value="<?= e($current['hero_title_line2'] ?? '') ?>"></div>
      <div class="form-group"><label>Hero Line 3</label><input class="form-control" name="hero_title_line3" value="<?= e($current['hero_title_line3'] ?? '') ?>"></div>
      <div class="form-group"><label>Hero Subtitle</label><input class="form-control" name="hero_subtitle" value="<?= e($current['hero_subtitle'] ?? '') ?>"></div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Contact &amp; Social</h2></div>
    <div class="form-grid">
      <div class="form-group"><label>Phone</label><input class="form-control" name="phone" value="<?= e($current['phone'] ?? '') ?>"></div>
      <div class="form-group"><label>Email</label><input class="form-control" name="email" value="<?= e($current['email'] ?? '') ?>"></div>
      <div class="form-group"><label>Address</label><input class="form-control" name="address" value="<?= e($current['address'] ?? '') ?>"></div>
      <div class="form-group"><label>Facebook URL</label><input class="form-control" name="facebook_url" value="<?= e($current['facebook_url'] ?? '') ?>"></div>
      <div class="form-group"><label>Instagram URL</label><input class="form-control" name="instagram_url" value="<?= e($current['instagram_url'] ?? '') ?>"></div>
      <div class="form-group"><label>Twitter / X URL</label><input class="form-control" name="twitter_url" value="<?= e($current['twitter_url'] ?? '') ?>"></div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Delivery &amp; Ordering</h2></div>
    <div class="form-grid">
      <div class="form-group"><label>Delivery Fee (Rs.)</label><input class="form-control" type="number" step="0.01" name="delivery_fee" value="<?= e($current['delivery_fee'] ?? '') ?>"></div>
      <div class="form-group"><label>Free Delivery Above (Rs.)</label><input class="form-control" type="number" step="0.01" name="free_delivery_threshold" value="<?= e($current['free_delivery_threshold'] ?? '') ?>"></div>
      <div class="form-group"><label>Minimum Order Amount (Rs.)</label><input class="form-control" type="number" step="0.01" name="min_order_amount" value="<?= e($current['min_order_amount'] ?? '') ?>"></div>
      <div class="form-group"><label>Estimated Delivery (minutes)</label><input class="form-control" type="number" name="estimated_delivery_minutes" value="<?= e($current['estimated_delivery_minutes'] ?? '') ?>"></div>
    </div>
    <div class="checkbox-row"><input type="checkbox" id="halal" name="halal_badge" <?= !empty($current['halal_badge']) && $current['halal_badge'] !== '0' ? 'checked' : '' ?>><label for="halal" style="margin:0">Show "100% Halal Certified" badge</label></div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Brand Colors</h2></div>
    <div class="form-grid">
      <div class="form-group"><label>Primary / Accent Color</label><input class="form-control" type="text" name="primary_color" value="<?= e($current['primary_color'] ?? '#E31C25') ?>"></div>
      <div class="form-group"><label>Background Color</label><input class="form-control" type="text" name="dark_bg" value="<?= e($current['dark_bg'] ?? '#0D0D0D') ?>"></div>
    </div>
    <p style="color:var(--muted);font-size:12px">Note: colors are stored for reference and future theming; update <code>assets/css/style.css</code> variables to apply a new palette site-wide.</p>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Loyalty / Referral Points</h2></div>
    <div class="checkbox-row"><input type="checkbox" id="loyalty_points_enabled" name="loyalty_points_enabled" <?= ($current['loyalty_points_enabled'] ?? '0') === '1' ? 'checked' : '' ?>><label for="loyalty_points_enabled" style="margin:0">Enable loyalty/referral points program</label></div>
    <div class="form-grid" style="margin-top:12px">
      <div class="form-group"><label>Spend Amount for 1 Point (Rs.)</label><input class="form-control" type="number" step="0.01" min="0.01" name="loyalty_spend_amount_per_point" value="<?= e($current['loyalty_spend_amount_per_point'] ?? '100') ?>"></div>
      <div class="form-group"><label>Value per Point (Rs.)</label><input class="form-control" type="number" step="0.01" min="0" name="loyalty_point_value" value="<?= e($current['loyalty_point_value'] ?? '1') ?>"></div>
      <div class="form-group"><label>Award Points When Order Status Is</label>
        <select class="form-control" name="loyalty_award_status">
          <?php foreach (['pending','confirmed','preparing','on_the_way','delivered'] as $s): ?>
          <option value="<?= $s ?>" <?= ($current['loyalty_award_status'] ?? 'delivered') === $s ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_',' ',$s))) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Maximum Redemption (% of order payable amount)</label><input class="form-control" type="number" step="1" min="0" max="100" name="loyalty_max_redeem_percent" value="<?= e($current['loyalty_max_redeem_percent'] ?? '100') ?>"></div>
    </div>
    <div class="checkbox-row"><input type="checkbox" id="loyalty_earn_on_delivery_fee" name="loyalty_earn_on_delivery_fee" <?= ($current['loyalty_earn_on_delivery_fee'] ?? '0') === '1' ? 'checked' : '' ?>><label for="loyalty_earn_on_delivery_fee" style="margin:0">Earn points on delivery fee</label></div>
    <div class="checkbox-row"><input type="checkbox" id="loyalty_earn_after_discount" name="loyalty_earn_after_discount" <?= ($current['loyalty_earn_after_discount'] ?? '1') === '1' ? 'checked' : '' ?>><label for="loyalty_earn_after_discount" style="margin:0">Deduct coupon/discount before calculating earned points</label></div>
    <p style="color:var(--muted);font-size:12px;margin-top:8px">Example: Spend Rs. <?= e($current['loyalty_spend_amount_per_point'] ?? '100') ?> = 1 point, 1 point = Rs. <?= e($current['loyalty_point_value'] ?? '1') ?>. A qualifying Rs. 1,000 order would earn <?= (int)floor(1000 / max(0.01, (float)($current['loyalty_spend_amount_per_point'] ?? 100))) ?> points worth Rs. <?= e(number_format((int)floor(1000 / max(0.01, (float)($current['loyalty_spend_amount_per_point'] ?? 100))) * (float)($current['loyalty_point_value'] ?? 1), 2)) ?>.</p>
  </div>

  <button type="submit" class="btn btn-primary">Save Settings</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
