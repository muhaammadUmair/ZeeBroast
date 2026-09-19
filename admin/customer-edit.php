<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
ensure_customer_registration_schema();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) {
    redirect(base_url('admin/customers.php'));
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session, please try again.';
    } else {
        $customer['full_name'] = trim($_POST['full_name'] ?? '');
        $customer['email'] = strtolower(trim($_POST['email'] ?? ''));
        $customer['phone'] = normalize_phone_number($_POST['phone'] ?? '');
        $customer['address'] = trim($_POST['address'] ?? '');

        if ($customer['full_name'] === '' || $customer['email'] === '' || !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid name and email address.';
        } else {
            $duplicate = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $duplicate->execute([$customer['email'], $id]);
            if ($duplicate->fetch()) {
                $error = 'Another customer already uses this email address.';
            } else {
                $update = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?');
                $update->execute([$customer['full_name'], $customer['email'], $customer['phone'], $customer['address'], $id]);
                redirect(base_url('admin/customers.php'));
            }
        }
    }
}

$admin_page_title = 'Edit Customer';
$active_admin = 'customers';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head"><h2><?= e($customer['full_name']) ?></h2></div>
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="form-group"><label>Full Name</label><input class="form-control" name="full_name" value="<?= e($customer['full_name']) ?>" required></div>
      <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" value="<?= e($customer['email']) ?>" required></div>
      <div class="form-group"><label>WhatsApp / Phone</label><input class="form-control" type="tel" name="phone" value="<?= e($customer['phone'] ?? '') ?>"></div>
      <div class="form-group"><label>Address</label><textarea class="form-control" name="address" rows="3"><?= e($customer['address'] ?? '') ?></textarea></div>
    </div>
    <button type="submit" class="btn btn-primary">Update Customer</button>
    <a href="<?= base_url('admin/customers.php') ?>" class="btn btn-outline">Cancel</a>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>