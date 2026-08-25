<?php
require_once __DIR__ . '/includes/header.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.status='active'");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    echo '<div class="section container"><div class="empty-state"><div class="e-icon">😕</div><p>Product not found.</p></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$related = db()->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND status='active' LIMIT 4");
$related->execute([$product['category_id'], $product['id']]);
$related = $related->fetchAll();
?>

<div class="page-header">
  <div class="container">
    <h1><?= e($product['name']) ?></h1>
    <div class="breadcrumb">
      <a href="<?= base_url('index.php') ?>">Home</a> / <a href="<?= base_url('menu.php') ?>">Menu</a> / <a href="<?= base_url('category.php?slug=' . urlencode($product['cat_slug'])) ?>"><?= e($product['cat_name']) ?></a> / <?= e($product['name']) ?>
    </div>
  </div>
</div>

<div class="section">
  <div class="container">
    <div class="product-detail">
      <?= food_thumb($product['image'], $product['icon']) ?>
      <div>
        <span class="eyebrow"><?= e($product['cat_name']) ?></span>
        <h1><?= e($product['name']) ?></h1>
        <p class="muted"><?= e($product['description']) ?></p>
        <div class="price"><?= money($product['sale_price'] ?: $product['price']) ?></div>

        <div class="qty-box">
          <button type="button" onclick="var q=document.getElementById('pqty');q.value=Math.max(1,parseInt(q.value)-1)">−</button>
          <input type="number" id="pqty" value="1" min="1" style="width:56px;text-align:center;background:var(--card-bg);border:1px solid var(--border);border-radius:8px;color:#fff;padding:8px;">
          <button type="button" onclick="var q=document.getElementById('pqty');q.value=parseInt(q.value)+1">+</button>
        </div>

        <button class="btn btn-primary" id="addBtn" data-id="<?= (int)$product['id'] ?>">Add to Cart</button>
      </div>
    </div>

    <?php if ($related): ?>
    <div class="section-head" style="margin-top:60px">
      <span class="eyebrow">You May Also Like</span>
      <h2>More From <?= e($product['cat_name']) ?></h2>
    </div>
    <div class="card-grid">
      <?php foreach ($related as $item): ?>
      <a href="<?= base_url('product.php?slug=' . urlencode($item['slug'])) ?>" class="food-card">
        <?= food_thumb($item['image'], $item['icon']) ?>
        <div class="food-body">
          <h3><?= e($item['name']) ?></h3>
          <div class="food-price-row"><span class="food-price"><?= money($item['sale_price'] ?: $item['price']) ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
document.getElementById('addBtn').addEventListener('click', function () {
  const qty = document.getElementById('pqty').value;
  fetch(window.ZB_BASE_URL + '/api/cart_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=add&type=product&id=' + this.dataset.id + '&qty=' + qty
  }).then(r => r.json()).then(data => {
    if (data.ok) { window.location.href = window.ZB_BASE_URL + '/cart.php'; }
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
