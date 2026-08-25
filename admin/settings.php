<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Site Settings';
$active_admin = 'settings';
$pdo = db();
$success = null;

$fields = [
    'site_name', 'site_tagline', 'phone', 'email', 'address',
    'facebook_url', 'instagram_url', 'twitter_url',
    'delivery_fee', 'free_delivery_threshold', 'min_order_amount', 'estimated_delivery_minutes',
    'currency_symbol', 'primary_color', 'dark_bg',
    'hero_title_line1', 'hero_title_line2', 'hero_title_line3', 'hero_subtitle',
    'order_id_prefix', 'halal_badge',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($fields as $key) {
        if ($key === 'halal_badge') {
            $stmt->execute([$key, isset($_POST[$key]) ? '1' : '0']);
        } else {
            $stmt->execute([$key, trim($_POST[$key] ?? '')]);
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

  <button type="submit" class="btn btn-primary">Save Settings</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
