<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
ensure_inventory_items_schema();

$admin_page_title = 'Items';
$active_admin = 'items';
$pdo = db();
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save') {
        $editId = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        if ($name === '' || $unit === '') {
            $error = 'Please provide a name and unit.';
        } else {
            $dupe = $pdo->prepare('SELECT id FROM inventory_items WHERE name = ? AND id != ?');
            $dupe->execute([$name, $editId]);
            if ($dupe->fetch()) {
                $error = 'An item with this name already exists.';
            } elseif ($editId > 0) {
                $stmt = $pdo->prepare('UPDATE inventory_items SET name=?, category=?, unit=?, status=? WHERE id=?');
                $stmt->execute([$name, $category ?: null, $unit, $status, $editId]);
                $success = 'Item updated.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO inventory_items (name, category, unit, status) VALUES (?,?,?,?)');
                $stmt->execute([$name, $category ?: null, $unit, $status]);
                $success = 'Item added.';
            }
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM inventory_items WHERE id = ?')->execute([(int)$_POST['id']]);
        $success = 'Item deleted.';
    }
}

$editingItem = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM inventory_items WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editingItem = $stmt->fetch() ?: null;
}

$items = $pdo->query('SELECT * FROM inventory_items ORDER BY category, name')->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head"><h2><?= $editingItem ? 'Edit Item' : 'Add Item' ?></h2></div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="form_action" value="save">
    <input type="hidden" name="id" value="<?= (int)($editingItem['id'] ?? 0) ?>">
    <div class="form-grid">
      <div class="form-group"><label>Name</label><input class="form-control" name="name" value="<?= e($editingItem['name'] ?? '') ?>" placeholder="Chicken, Cooking Oil, Boxes..." required></div>
      <div class="form-group"><label>Category (optional)</label><input class="form-control" name="category" value="<?= e($editingItem['category'] ?? '') ?>" placeholder="Meat, Vegetables, Packaging..."></div>
      <div class="form-group"><label>Unit</label><input class="form-control" name="unit" value="<?= e($editingItem['unit'] ?? '') ?>" placeholder="kg, litre, pcs, dozen..." required></div>
      <div class="form-group"><label>Status</label>
        <select class="form-control" name="status">
          <option value="active" <?= ($editingItem['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= ($editingItem['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editingItem ? 'Update Item' : 'Add Item' ?></button>
    <?php if ($editingItem): ?><a href="<?= base_url('admin/items.php') ?>" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>All Items</h2></div>
  <table>
    <thead><tr><th>Name</th><th>Category</th><th>Unit</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($items)): ?><tr><td colspan="5">No items yet. Add raw material items above to use them in Purchases.</td></tr><?php endif; ?>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><?= e($it['name']) ?></td>
        <td><?= e($it['category'] ?: '—') ?></td>
        <td><?= e($it['unit']) ?></td>
        <td><span class="badge <?= $it['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= e(ucfirst($it['status'])) ?></span></td>
        <td style="display:flex;gap:8px">
          <a href="<?= base_url('admin/items.php?edit=' . (int)$it['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this item?');">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
