<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$product = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) redirect(base_url('admin/products.php'));
}

$admin_page_title = $product ? 'Edit Product' : 'Add Product';
$active_admin = 'products';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session, please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $sale_price = $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : null;
        $icon = trim($_POST['icon'] ?? '🍗');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_popular = isset($_POST['is_popular']) ? 1 : 0;
        $stock_status = $_POST['stock_status'] === 'out_of_stock' ? 'out_of_stock' : 'in_stock';
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        if ($name === '' || $category_id <= 0 || $price <= 0) {
            $error = 'Please provide name, category and a valid price.';
        } else {
            try {
                $imagePath = handle_image_upload('image', 'products');
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }

            if (!$error) {
                $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
                if ($product) {
                    $sql = 'UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?, sale_price=?, icon=?, is_featured=?, is_popular=?, stock_status=?, status=?, sort_order=?' .
                           ($imagePath ? ', image=?' : '') . ' WHERE id=?';
                    $params = [$category_id, $name, $slug, $description, $price, $sale_price, $icon, $is_featured, $is_popular, $stock_status, $status, $sort_order];
                    if ($imagePath) $params[] = $imagePath;
                    $params[] = $product['id'];
                    $pdo->prepare($sql)->execute($params);
                    redirect(base_url('admin/products.php'));
                } else {
                    $stmt = $pdo->prepare('INSERT INTO products (category_id, name, slug, description, price, sale_price, image, icon, is_featured, is_popular, stock_status, status, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([$category_id, $name, $slug, $description, $price, $sale_price, $imagePath, $icon, $is_featured, $is_popular, $stock_status, $status, $sort_order]);
                    redirect(base_url('admin/products.php'));
                }
            }
        }
    }
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="form-group"><label>Product Name</label><input class="form-control" name="name" value="<?= e($product['name'] ?? '') ?>" required></div>
      <div class="form-group"><label>Category</label>
        <select class="form-control" name="category_id" required>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= (int)$cat['id'] ?>" <?= ($product['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Price (Rs.)</label><input class="form-control" type="number" step="0.01" name="price" value="<?= e($product['price'] ?? '') ?>" required></div>
      <div class="form-group"><label>Sale Price (optional)</label><input class="form-control" type="number" step="0.01" name="sale_price" value="<?= e($product['sale_price'] ?? '') ?>"></div>
      <div class="form-group"><label>Icon (emoji placeholder)</label><input class="form-control" name="icon" value="<?= e($product['icon'] ?? '🍗') ?>"></div>
      <div class="form-group"><label>Sort Order</label><input class="form-control" type="number" name="sort_order" value="<?= (int)($product['sort_order'] ?? 0) ?>"></div>
      <div class="form-group"><label>Stock Status</label>
        <select class="form-control" name="stock_status">
          <option value="in_stock" <?= ($product['stock_status'] ?? 'in_stock') === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
          <option value="out_of_stock" <?= ($product['stock_status'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
        </select>
      </div>
      <div class="form-group"><label>Status</label>
        <select class="form-control" name="status">
          <option value="active" <?= ($product['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>

    <div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="3"><?= e($product['description'] ?? '') ?></textarea></div>

    <div class="checkbox-row" style="margin-bottom:14px"><input type="checkbox" name="is_featured" id="feat" <?= !empty($product['is_featured']) ? 'checked' : '' ?>><label for="feat" style="margin:0">Featured on Homepage</label></div>
    <div class="checkbox-row" style="margin-bottom:18px"><input type="checkbox" name="is_popular" id="pop" <?= !empty($product['is_popular']) ? 'checked' : '' ?>><label for="pop" style="margin:0">Mark as Popular</label></div>

    <div class="form-group">
      <label>Product Image</label>
      <?php $u = media_url($product['image'] ?? null); if ($u): ?><div class="thumb-preview" style="width:80px;height:80px;margin-bottom:10px"><img src="<?= e($u) ?>"></div><?php endif; ?>
      <input class="form-control" type="file" name="image" accept="image/*">
      <p style="color:var(--muted);font-size:12px;margin-top:6px">Optional — leave empty to keep using the emoji placeholder.</p>
    </div>

    <button type="submit" class="btn btn-primary"><?= $product ? 'Update Product' : 'Add Product' ?></button>
    <a href="<?= base_url('admin/products.php') ?>" class="btn btn-outline">Cancel</a>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
