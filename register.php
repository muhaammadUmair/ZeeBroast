<?php
$page_title = 'Sign Up';
require_once __DIR__ . '/includes/header.php';

if (is_logged_in()) {
    redirect(base_url('account.php'));
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session, please try again.';
    } else {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || strlen($password) < 6) {
            $error = 'Please fill all fields. Password must be at least 6 characters.';
        } else {
            $check = db()->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                $stmt = db()->prepare('INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
                $_SESSION['user_id'] = (int)db()->lastInsertId();
                redirect(base_url('account.php'));
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

      <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label>Full Name</label><input class="form-control" type="text" name="full_name" required></div>
        <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required></div>
        <div class="form-group"><label>Phone</label><input class="form-control" type="text" name="phone"></div>
        <div class="form-group"><label>Password</label><input class="form-control" type="password" name="password" required></div>
        <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
      </form>

      <div class="auth-switch">Already have an account? <a href="<?= base_url('login.php') ?>">Login</a></div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
