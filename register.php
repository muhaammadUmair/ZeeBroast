<?php
$page_title = 'Sign Up';
require_once __DIR__ . '/includes/header.php';

if (is_logged_in()) {
    redirect(base_url('account.php'));
}

$error = null;
$welcomeCode = null;
$whatsappLink = null;
$successMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session, please try again.';
    } else {
        $name = trim($_POST['full_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = normalize_phone_number($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $address === '' || $phone === '' || strlen($password) < 6) {
            $error = 'Please fill in your name, address, WhatsApp number, and a password of at least 6 characters.';
        } else {
            $email = $email !== '' ? strtolower($email) : 'customer+' . $phone . '@local.invalid';
            $check = db()->prepare('SELECT id FROM users WHERE phone = ? OR email = ?');
            $check->execute([$phone, $email]);
            if ($check->fetch()) {
                $error = 'An account with this phone number or email already exists.';
            } else {
                $stmt = db()->prepare('INSERT INTO users (full_name, address, email, phone, password) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$name, $address, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);

                $userId = (int)db()->lastInsertId();
                $welcome = generate_customer_welcome_coupon($userId, $phone);
                $welcomeCode = $welcome['code'];
                $whatsappLink = $welcome['whatsapp_url'];
                $successMessage = 'Account created successfully. Your 10% discount code will expire in two days.';
            }
        }
    }
}
?>

<div class="section">
  <div class="container auth-wrap">
    <div class="flow-card">
      <h2>Create Account</h2>
      <p class="sub">Sign up to start ordering your favourite broast.</p>

      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <?php if ($successMessage): ?><div class="alert alert-success"><?= e($successMessage) ?></div><?php endif; ?>

      <?php if ($welcomeCode): ?>
      <div class="alert alert-success">
        <p style="margin:0 0 8px"><strong>Your 10% discount code:</strong> <span style="font-size:18px;letter-spacing:1px;word-break:break-all"><?= e($welcomeCode) ?></span></p>
        <a class="btn btn-primary btn-sm" href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener">Share Discount Code To ZeeBroast</a>
      </div>
      <?php else: ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label>Full Name</label><input class="form-control" type="text" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required></div>
        <div class="form-group"><label>Address</label><textarea class="form-control" name="address" rows="3" required><?= e($_POST['address'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Email Address (Optional)</label><input class="form-control" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com"></div>
        <div class="form-group"><label>WhatsApp Number</label><input class="form-control" type="tel" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" placeholder="923001234567" required></div>
        <div class="form-group"><label>Password</label><input class="form-control" type="password" name="password" required></div>
        <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
      </form>

      <div class="auth-switch">Already have an account? <a href="<?= base_url('login.php') ?>">Login</a></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
