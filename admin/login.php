<?php
require_once __DIR__ . '/../config/config.php';

if (is_admin_logged_in()) {
    redirect(base_url('admin/index.php'));
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session, please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = db()->prepare('SELECT * FROM admin_users WHERE email = ?');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password'])) {
            $error = 'Invalid email or password.';
        } elseif ($admin['status'] !== 'active') {
            $error = 'This admin account is disabled.';
        } else {
            $_SESSION['admin_id'] = $admin['id'];
            db()->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);
            redirect(base_url('admin/index.php'));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — ZeeBroast</title>
<link rel="stylesheet" href="<?= base_url('admin/assets/css/admin.css') ?>">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <h2>ZeeBroast Admin</h2>
    <p>Sign in to manage your restaurant</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required autofocus></div>
      <div class="form-group"><label>Password</label><input class="form-control" type="password" name="password" required></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Login</button>
    </form>
  </div>
</div>
</body>
</html>
