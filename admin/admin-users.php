<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Admin Users';
$active_admin = 'admin-users';
$pdo = db();
$currentAdmin = current_admin();
$error = null;
$success = null;

if ($currentAdmin['role'] !== 'super_admin') {
    echo '<div class="alert alert-error">Only super admins can manage admin users.</div>';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['manager', 'staff', 'super_admin']) ? $_POST['role'] : 'staff';

        if ($name === '' || $email === '' || strlen($password) < 6) {
            $error = 'Please fill all fields. Password must be at least 6 characters.';
        } else {
            $check = $pdo->prepare('SELECT id FROM admin_users WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'An admin with this email already exists.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO admin_users (full_name, email, password, role) VALUES (?,?,?,?)');
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                $success = 'Admin user added.';
            }
        }
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        if ($id !== (int)$currentAdmin['id']) {
            $stmt = $pdo->prepare('SELECT status FROM admin_users WHERE id = ?');
            $stmt->execute([$id]);
            $new = $stmt->fetchColumn() === 'active' ? 'inactive' : 'active';
            $pdo->prepare('UPDATE admin_users SET status = ? WHERE id = ?')->execute([$new, $id]);
            $success = 'Admin status updated.';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id !== (int)$currentAdmin['id']) {
            $pdo->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);
            $success = 'Admin user deleted.';
        }
    }
}

$admins = $pdo->query('SELECT * FROM admin_users ORDER BY id')->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<?php if ($currentAdmin['role'] === 'super_admin'): ?>
<div class="panel">
  <div class="panel-head"><h2>Add Admin User</h2></div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="form_action" value="add">
    <div class="form-grid">
      <div class="form-group"><label>Full Name</label><input class="form-control" name="full_name" required></div>
      <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required></div>
      <div class="form-group"><label>Password</label><input class="form-control" type="password" name="password" required></div>
      <div class="form-group"><label>Role</label>
        <select class="form-control" name="role">
          <option value="staff">Staff</option>
          <option value="manager">Manager</option>
          <option value="super_admin">Super Admin</option>
        </select>
      </div>
    </div>
    <button type="submit" class="btn btn-primary">Add Admin</button>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>All Admin Users</h2></div>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last Login</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($admins as $a): ?>
      <tr>
        <td><?= e($a['full_name']) ?></td>
        <td><?= e($a['email']) ?></td>
        <td><span class="badge badge-blue"><?= e(str_replace('_', ' ', $a['role'])) ?></span></td>
        <td><?= $a['last_login'] ? e(date('d M, h:i A', strtotime($a['last_login']))) : 'Never' ?></td>
        <td><span class="badge <?= $a['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= e(ucfirst($a['status'])) ?></span></td>
        <td style="display:flex;gap:8px">
          <?php if ((int)$a['id'] !== (int)$currentAdmin['id']): ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="toggle">
            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
            <button type="submit" class="btn btn-outline btn-sm"><?= $a['status'] === 'active' ? 'Disable' : 'Enable' ?></button>
          </form>
          <form method="post" onsubmit="return confirm('Delete this admin user?');">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
          </form>
          <?php else: ?>
          <span style="color:var(--muted);font-size:12px">You</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
