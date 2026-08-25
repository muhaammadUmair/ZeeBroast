<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Categories';
$active_admin = 'categories';
$pdo = db();
$error = null;
$success = null;

function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        if ($name === '') {
            $error = 'Category name is required.';
        } else {
            try {
                $imagePath = handle_image_upload('image', 'categories');
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }

            if (!$error) {
                $slug = slugify($name);
                if ($id > 0) {
                    $sql = 'UPDATE categories SET name=?, slug=?, icon=?, sort_order=?, status=?' . ($imagePath ? ', image=?' : '') . ' WHERE id=?';
                    $params = [$name, $slug, $icon, $sort, $status];
                    if ($imagePath) $params[] = $imagePath;
                    $params[] = $id;
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $success = 'Category updated.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO categories (name, slug, icon, image, sort_order, status) VALUES (?,?,?,?,?,?)');
                    $stmt->execute([$name, $slug, $icon, $imagePath, $sort, $status]);
                    $success = 'Category added.';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        $success = 'Category deleted.';
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}

$categories = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count FROM categories c ORDER BY sort_order')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head"><h2><?= $editing ? 'Edit Category' : 'Add Category' ?></h2></div>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="form_action" value="save">
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <div class="form-grid">
      <div class="form-group"><label>Name</label><input class="form-control" name="name" value="<?= e($editing['name'] ?? '') ?>" required></div>
      <div class="form-group"><label>Icon (emoji)</label><input class="form-control" name="icon" value="<?= e($editing['icon'] ?? '🍗') ?>"></div>
      <div class="form-group"><label>Sort Order</label><input class="form-control" type="number" name="sort_order" value="<?= (int)($editing['sort_order'] ?? 0) ?>"></div>
      <div class="form-group"><label>Status</label>
        <select class="form-control" name="status">
          <option value="active" <?= ($editing['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= ($editing['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Category Image (optional)</label>
      <?php $u = media_url($editing['image'] ?? null); if ($u): ?><div class="thumb-preview" style="width:70px;height:70px;margin-bottom:10px"><img src="<?= e($u) ?>"></div><?php endif; ?>
      <input class="form-control" type="file" name="image" accept="image/*">
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update Category' : 'Add Category' ?></button>
    <?php if ($editing): ?><a href="<?= base_url('admin/categories.php') ?>" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>All Categories</h2></div>
  <table>
    <thead><tr><th>Icon</th><th>Name</th><th>Slug</th><th>Products</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($categories as $cat): ?>
      <tr>
        <td style="font-size:20px"><?= e($cat['icon']) ?></td>
        <td><?= e($cat['name']) ?></td>
        <td><?= e($cat['slug']) ?></td>
        <td><?= (int)$cat['product_count'] ?></td>
        <td><span class="badge <?= $cat['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= e(ucfirst($cat['status'])) ?></span></td>
        <td style="display:flex;gap:8px">
          <a href="<?= base_url('admin/categories.php?edit=' . (int)$cat['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this category and all its products?');">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
