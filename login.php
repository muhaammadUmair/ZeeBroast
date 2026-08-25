<?php
$page_title = 'Login';
require_once __DIR__ . '/includes/header.php';

if (is_logged_in()) {
    redirect(base_url('account.php'));
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session, please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $error = 'Your account has been blocked. Please contact support.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            redirect(base_url($_GET['redirect'] ?? 'account.php'));
        }
    }
}
?>

<div class="section">
  <div class="container auth-wrap">
    <div class="flow-card">
      <h2>Welcome Back</h2>
      <p class="sub">Login to track orders and checkout faster.</p>

      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required></div>
        <div class="form-group"><label>Password</label><input class="form-control" type="password" name="password" required></div>
        <button type="submit" class="btn btn-primary btn-block">Login</button>
      </form>

      <div class="auth-switch">Don't have an account? <a href="<?= base_url('register.php') ?>">Sign Up</a></div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
