<?php
$page_title = 'Our Menu';
$active_nav = 'menu';
require_once __DIR__ . '/includes/header.php';

$categories = db()->query("SELECT * FROM categories WHERE status='active' ORDER BY sort_order")->fetchAll();

$cat_slug   = $_GET['category'] ?? '';
$min_price  = isset($_GET['min']) ? (float)$_GET['min'] : 0;
$max_price  = isset($_GET['max']) ? (float)$_GET['max'] : 2000;
$sort       = $_GET['sort'] ?? 'popularity';
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 8;

$where = ["p.status = 'active'"];
$params = [];

if ($cat_slug !== '') {
    $where[] = 'c.slug = ?';
    $params[] = $cat_slug;
}
$where[] = 'p.price BETWEEN ? AND ?';
$params[] = $min_price;
$params[] = $max_price;

$orderBy = 'p.is_popular DESC, p.sort_order ASC';
if ($sort === 'price_low') $orderBy = 'p.price ASC';
if ($sort === 'price_high') $orderBy = 'p.price DESC';
if ($sort === 'newest') $orderBy = 'p.created_at DESC';

$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $per_page));
$page = min($page, $totalPages);
$offset = ($page - 1) * $per_page;

$sql = "SELECT p.*, c.slug AS cat_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE $whereSql ORDER BY $orderBy LIMIT $per_page OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

function menu_query_with(array $overrides): string {
    $q = array_merge($_GET, $overrides);
    return '?' . http_build_query($q);
}
?>

<div class="page-header">
  <div class="container">
    <h1>Our Menu</h1>
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / Menu</div>
  </div>
</div>

<div class="section">
  <div class="container">

    <div class="category-pills">
      <a href="<?= base_url('menu.php') ?>" class="<?= $cat_slug === '' ? 'active' : '' ?>">All Items</a>
      <?php foreach ($categories as $cat): ?>
      <a href="<?= base_url('menu.php' . menu_query_with(['category' => $cat['slug'], 'page' => 1])) ?>" class="<?= $cat_slug === $cat['slug'] ? 'active' : '' ?>"><?= e($cat['name']) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="menu-layout">
      <aside class="filter-box">
        <form method="get" action="<?= base_url('menu.php') ?>">
          <?php if ($cat_slug !== ''): ?><input type="hidden" name="category" value="<?= e($cat_slug) ?>"><?php endif; ?>
          <h4>Filter By</h4>

          <div class="filter-group">
            <label class="opt" style="color:#fff;font-weight:600">Price</label>
            <input type="range" name="max" min="100" max="2000" step="50" value="<?= e($max_price) ?>" oninput="this.nextElementSibling.querySelector('.maxval').textContent=this.value">
            <div class="price-range-label"><span>Rs. 100</span><span class="maxval"><?= (int)$max_price ?></span></div>
          </div>

          <div class="filter-group">
            <label class="opt" style="color:#fff;font-weight:600">Category</label>
            <?php foreach ($categories as $cat): ?>
            <label class="opt"><input type="radio" name="category" value="<?= e($cat['slug']) ?>" <?= $cat_slug === $cat['slug'] ? 'checked' : '' ?>> <?= e($cat['name']) ?></label>
            <?php endforeach; ?>
          </div>

          <div class="filter-group">
            <label class="opt" style="color:#fff;font-weight:600">Sort By</label>
            <label class="opt"><input type="radio" name="sort" value="popularity" <?= $sort === 'popularity' ? 'checked' : '' ?>> Popularity</label>
            <label class="opt"><input type="radio" name="sort" value="price_low" <?= $sort === 'price_low' ? 'checked' : '' ?>> Price: Low to High</label>
            <label class="opt"><input type="radio" name="sort" value="price_high" <?= $sort === 'price_high' ? 'checked' : '' ?>> Price: High to Low</label>
            <label class="opt"><input type="radio" name="sort" value="newest" <?= $sort === 'newest' ? 'checked' : '' ?>> Newest First</label>
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-sm">Apply Filters</button>
        </form>
      </aside>

      <div class="menu-results">
        <?php if (empty($products)): ?>
          <div class="empty-state">
            <div class="e-icon">🍽️</div>
            <p>No items match your filters. Try widening your search.</p>
          </div>
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

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="<?= base_url('menu.php' . menu_query_with(['page' => $i])) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <div class="combo-banner">
          <div class="content">
            <span class="eyebrow" style="color:#fff">Make It A Meal</span>
            <h3>Add Drinks &amp; Sides</h3>
            <p>Complete your meal and save more!</p>
            <a href="<?= base_url('deals.php') ?>" class="btn btn-primary">Add Combo</a>
          </div>
          <div class="emoji-row">🍗🍟🥤</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
