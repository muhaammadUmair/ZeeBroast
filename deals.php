<?php
$page_title = 'Deals';
$active_nav = 'deals';
require_once __DIR__ . '/includes/header.php';

$deals = db()->query("SELECT * FROM deals WHERE status='active' ORDER BY sort_order")->fetchAll();
$dealItemsStmt = db()->prepare("SELECT item_label, quantity FROM deal_items WHERE deal_id = ?");
?>

<div class="page-header">
  <div class="container">
    <h1>Deals &amp; Combos</h1>
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / Deals</div>
  </div>
</div>

<div class="section">
  <div class="container">
    <?php if (empty($deals)): ?>
      <div class="empty-state"><div class="e-icon">🎉</div><p>No active deals right now. Check back soon!</p></div>
    <?php else: ?>
    <div class="deal-grid">
      <?php foreach ($deals as $deal): $dealItemsStmt->execute([$deal['id']]); $items = $dealItemsStmt->fetchAll(); ?>
      <?php $dealImageUrl = media_url($deal['image'] ?? null); ?>
      <div class="deal-card">
        <?php if ($deal['discount_percent'] > 0): ?><span class="deal-save">Save <?= (int)$deal['discount_percent'] ?>%</span><?php endif; ?>
        <div class="deal-thumb">
          <?php if ($dealImageUrl): ?>
            <img src="<?= e($dealImageUrl) ?>" alt="<?= e($deal['title']) ?>" loading="lazy">
          <?php else: ?>
            <?= e($deal['icon'] ?: '🍗') ?>
          <?php endif; ?>
        </div>
        <div class="deal-body">
          <h3><?= e($deal['title']) ?></h3>
          <p><?= e($deal['description']) ?></p>
          <?php if ($items): ?>
          <ul style="margin-bottom:14px">
            <?php foreach ($items as $it): ?><li style="font-size:12.5px;color:var(--text-muted)">• <?= (int)$it['quantity'] ?>x <?= e($it['item_label']) ?></li><?php endforeach; ?>
          </ul>
          <?php endif; ?>
          <div class="deal-price-row">
            <span class="old"><?= money($deal['original_price']) ?></span>
            <span class="new"><?= money($deal['deal_price']) ?></span>
          </div>
          <button class="btn btn-primary btn-block js-add-cart" data-type="deal" data-id="<?= (int)$deal['id'] ?>">Add to Cart</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
