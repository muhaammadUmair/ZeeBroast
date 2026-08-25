<?php
$active_nav = 'categories';
require_once __DIR__ . '/includes/header.php';

$slug = $_GET['slug'] ?? '';

if ($slug === '') {
    // ---------------- Category overview grid ----------------
    $page_title = 'Categories';
    $categories = db()->query("SELECT * FROM categories WHERE status='active' ORDER BY sort_order")->fetchAll();
    ?>
    <div class="page-header">
      <div class="container">
        <h1>Categories</h1>
        <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / Categories</div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="card-grid">
          <?php foreach ($categories as $cat):
              $count = db()->prepare("SELECT COUNT(*) FROM products WHERE category_id = ? AND status='active'");
              $count->execute([$cat['id']]);
          ?>
          <a href="<?= base_url('category.php?slug=' . urlencode($cat['slug'])) ?>" class="food-card">
            <?= food_thumb($cat['image'], $cat['icon']) ?>
            <div class="food-body">
              <h3><?= e($cat['name']) ?></h3>
              <p class="food-desc"><?= (int)$count->fetchColumn() ?> Items</p>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php
} else {
    // ---------------- Single category product listing ----------------
    $catStmt = db()->prepare("SELECT * FROM categories WHERE slug = ? AND status='active'");
    $catStmt->execute([$slug]);
    $category = $catStmt->fetch();

    if (!$category) {
        http_response_code(404);
        echo '<div class="section container"><div class="empty-state"><div class="e-icon">😕</div><p>Category not found.</p></div></div>';
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }

    $page_title = $category['name'];
    $sort = $_GET['sort'] ?? 'popularity';
    $orderBy = 'is_popular DESC, sort_order ASC';
    if ($sort === 'price_low') $orderBy = 'price ASC';
    if ($sort === 'price_high') $orderBy = 'price DESC';

    $prodStmt = db()->prepare("SELECT * FROM products WHERE category_id = ? AND status='active' ORDER BY $orderBy");
    $prodStmt->execute([$category['id']]);
    $products = $prodStmt->fetchAll();
    ?>
    <div class="page-header">
      <div class="container">
        <h1><?= e($category['name']) ?></h1>
        <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / <a href="<?= base_url('category.php') ?>">Categories</a> / <?= e($category['name']) ?></div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="sort-bar">
          <form method="get">
            <input type="hidden" name="slug" value="<?= e($slug) ?>">
            <select name="sort" onchange="this.form.submit()">
              <option value="popularity" <?= $sort === 'popularity' ? 'selected' : '' ?>>Sort By: Popularity</option>
              <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
              <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
            </select>
          </form>
        </div>

        <?php if (empty($products)): ?>
          <div class="empty-state"><div class="e-icon">🍽️</div><p>No items in this category yet.</p></div>
        <?php else: ?>
        <div class="card-grid">
          <?php foreach ($products as $item): ?>
          <div class="food-card">
            <a href="<?= base_url('product.php?slug=' . urlencode($item['slug'])) ?>">
              <?= food_thumb($item['image'], $item['icon']) ?>
            </a>
            <div class="food-body">
              <a href="<?= base_url('product.php?slug=' . urlencode($item['slug'])) ?>"><h3><?= e($item['name']) ?></h3></a>
              <div class="food-price-row">
                <span class="food-price"><?= money($item['sale_price'] ?: $item['price']) ?></span>
                <button class="add-cart-btn js-add-cart" data-type="product" data-id="<?= (int)$item['id'] ?>">+ Add to Cart</button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php
}
require_once __DIR__ . '/includes/footer.php';
