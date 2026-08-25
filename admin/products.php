<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Products';
$active_admin = 'products';
$pdo = db();
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['form_action'] ?? '') === 'delete') {
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([(int)$_POST['id']]);
    $success = 'Product deleted.';
}

$catFilter = $_GET['category'] ?? '';
$search = trim($_GET['q'] ?? '');
$where = [];
$params = [];
if ($catFilter !== '') { $where[] = 'p.category_id = ?'; $params[] = (int)$catFilter; }
if ($search !== '') { $where[] = 'p.name LIKE ?'; $params[] = '%' . $search . '%'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("SELECT p.*, c.name AS cat_name FROM products p JOIN categories c ON c.id = p.category_id $whereSql ORDER BY p.id DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();
$categories = $pdo->query('SELECT * FROM categories ORDER BY sort_order')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head">
    <h2>All Products</h2>
    <a href="<?= base_url('admin/product-edit.php') ?>" class="btn btn-primary btn-sm">+ Add Product</a>
  </div>

  <form method="get" style="display:flex;gap:10px;margin-bottom:18px">
    <input class="form-control" type="text" name="q" placeholder="Search products..." value="<?= e($search) ?>" style="max-width:260px">
    <select class="form-control" name="category" style="max-width:200px">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
      <option value="<?= (int)$cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline" type="submit">Filter</button>
  </form>

  <table>
    <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Featured</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($products)): ?><tr><td colspan="7">No products found.</td></tr><?php endif; ?>
      <?php foreach ($products as $p): ?>
      <tr>
        <td><div class="thumb-preview"><?php $u = media_url($p['image']); if ($u): ?><img src="<?= e($u) ?>" alt=""><?php else: echo e($p['icon'] ?: '🍗'); endif; ?></div></td>
        <td><?= e($p['name']) ?></td>
        <td><?= e($p['cat_name']) ?></td>
        <td><?= money($p['sale_price'] ?: $p['price']) ?> <?= $p['sale_price'] ? '<span style="text-decoration:line-through;color:var(--muted);font-size:11px">' . money($p['price']) . '</span>' : '' ?></td>
        <td><?= $p['is_featured'] ? '⭐' : '' ?></td>
        <td><span class="badge <?= $p['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= e(ucfirst($p['status'])) ?></span></td>
        <td style="display:flex;gap:8px">
          <a href="<?= base_url('admin/product-edit.php?id=' . (int)$p['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this product?');">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
