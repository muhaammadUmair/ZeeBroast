<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$deal = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM deals WHERE id = ?');
    $stmt->execute([$id]);
    $deal = $stmt->fetch();
    if (!$deal) redirect(base_url('admin/deals.php'));
}

$admin_page_title = $deal ? 'Edit Deal' : 'Add Deal';
$active_admin = 'deals';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session, please try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? '🍗');
        $original_price = (float)($_POST['original_price'] ?? 0);
        $deal_price = (float)($_POST['deal_price'] ?? 0);
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        if ($title === '' || $original_price <= 0 || $deal_price <= 0) {
            $error = 'Please provide a title and valid prices.';
        } else {
            $discount = $original_price > 0 ? (int)round((($original_price - $deal_price) / $original_price) * 100) : 0;
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-');

            try {
                $imagePath = handle_image_upload('image', 'deals');
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }

            if (!$error) {
                if ($deal) {
                    $sql = 'UPDATE deals SET title=?, slug=?, description=?, icon=?, original_price=?, deal_price=?, discount_percent=?, status=?, sort_order=?' .
                           ($imagePath ? ', image=?' : '') . ' WHERE id=?';
                    $params = [$title, $slug, $description, $icon, $original_price, $deal_price, $discount, $status, $sort_order];
                    if ($imagePath) $params[] = $imagePath;
                    $params[] = $deal['id'];
                    $pdo->prepare($sql)->execute($params);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO deals (title, slug, description, icon, image, original_price, deal_price, discount_percent, status, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([$title, $slug, $description, $icon, $imagePath, $original_price, $deal_price, $discount, $status, $sort_order]);
                }
                redirect(base_url('admin/deals.php'));
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="form-group"><label>Deal Title</label><input class="form-control" name="title" value="<?= e($deal['title'] ?? '') ?>" required></div>
      <div class="form-group"><label>Icon (emoji)</label><input class="form-control" name="icon" value="<?= e($deal['icon'] ?? '🍗') ?>"></div>
      <div class="form-group"><label>Original Price (Rs.)</label><input class="form-control" type="number" step="0.01" name="original_price" value="<?= e($deal['original_price'] ?? '') ?>" required></div>
      <div class="form-group"><label>Deal Price (Rs.)</label><input class="form-control" type="number" step="0.01" name="deal_price" value="<?= e($deal['deal_price'] ?? '') ?>" required></div>
      <div class="form-group"><label>Sort Order</label><input class="form-control" type="number" name="sort_order" value="<?= (int)($deal['sort_order'] ?? 0) ?>"></div>
      <div class="form-group"><label>Status</label>
        <select class="form-control" name="status">
          <option value="active" <?= ($deal['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= ($deal['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>
    <div class="form-group"><label>Description (e.g. "4 Pcs Broast + 2 Burgers + Fries")</label><textarea class="form-control" name="description" rows="2"><?= e($deal['description'] ?? '') ?></textarea></div>

    <div class="form-group">
      <label>Deal Image</label>
      <?php $u = media_url($deal['image'] ?? null); if ($u): ?><div class="thumb-preview" style="width:80px;height:80px;margin-bottom:10px"><img src="<?= e($u) ?>"></div><?php endif; ?>
      <input class="form-control" type="file" name="image" accept="image/*">
    </div>

    <button type="submit" class="btn btn-primary"><?= $deal ? 'Update Deal' : 'Add Deal' ?></button>
    <a href="<?= base_url('admin/deals.php') ?>" class="btn btn-outline">Cancel</a>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
