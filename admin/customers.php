<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Customers';
$active_admin = 'customers';
$pdo = db();
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['form_action'] ?? '') === 'toggle') {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare('SELECT status FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $current = $stmt->fetchColumn();
    $new = $current === 'active' ? 'blocked' : 'active';
    $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$new, $id]);
    $success = 'Customer status updated.';
}

$customers = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count, (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.user_id = u.id AND o.status != 'cancelled') AS total_spent FROM users u ORDER BY u.created_at DESC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head"><h2>All Customers</h2></div>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($customers)): ?><tr><td colspan="7">No customers registered yet.</td></tr><?php endif; ?>
      <?php foreach ($customers as $c): ?>
      <tr>
        <td><?= e($c['full_name']) ?></td>
        <td><?= e($c['email']) ?></td>
        <td><?= e($c['phone'] ?: '—') ?></td>
        <td><?= (int)$c['order_count'] ?></td>
        <td><?= money($c['total_spent']) ?></td>
        <td><span class="badge <?= $c['status'] === 'active' ? 'badge-green' : 'badge-red' ?>"><?= e(ucfirst($c['status'])) ?></span></td>
        <td>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="toggle">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button type="submit" class="btn btn-outline btn-sm"><?= $c['status'] === 'active' ? 'Block' : 'Unblock' ?></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
