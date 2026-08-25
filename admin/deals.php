<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Deals & Combos';
$active_admin = 'deals';
$pdo = db();
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['form_action'] ?? '') === 'delete') {
    $pdo->prepare('DELETE FROM deals WHERE id = ?')->execute([(int)$_POST['id']]);
    $success = 'Deal deleted.';
}

$deals = $pdo->query('SELECT * FROM deals ORDER BY sort_order')->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head">
    <h2>All Deals</h2>
    <a href="<?= base_url('admin/deal-edit.php') ?>" class="btn btn-primary btn-sm">+ Add Deal</a>
  </div>
  <table>
    <thead><tr><th>Icon</th><th>Title</th><th>Original</th><th>Deal Price</th><th>Discount</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($deals)): ?><tr><td colspan="7">No deals yet.</td></tr><?php endif; ?>
      <?php foreach ($deals as $d): ?>
      <tr>
        <td style="font-size:20px"><?= e($d['icon']) ?></td>
        <td><?= e($d['title']) ?></td>
        <td><?= money($d['original_price']) ?></td>
        <td><?= money($d['deal_price']) ?></td>
        <td><?= (int)$d['discount_percent'] ?>%</td>
        <td><span class="badge <?= $d['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= e(ucfirst($d['status'])) ?></span></td>
        <td style="display:flex;gap:8px">
          <a href="<?= base_url('admin/deal-edit.php?id=' . (int)$d['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this deal?');">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
