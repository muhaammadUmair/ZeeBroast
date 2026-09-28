<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Reports';
$active_admin = 'reports';
$pdo = db();

$reportTabs = [
  'daily-sales' => 'Daily Sales',
  'product-sales' => 'Product Sales',
  'deal-sales' => 'Deal Sales',
  'profit' => 'Profit',
  'category-sales' => 'Category Sales',
  'day-of-week' => 'Day-of-Week',
  'deal-vs-individual' => 'Deal vs Individual',
];
$report = $_GET['r'] ?? 'daily-sales';
if (!isset($reportTabs[$report])) { $report = 'daily-sales'; }

$from = trim($_GET['from'] ?? '') ?: date('Y-m-d', strtotime('-6 days'));
$to = trim($_GET['to'] ?? '') ?: date('Y-m-d');
// Guard against a reversed or malformed range.
if (strtotime($to) < strtotime($from)) { $to = $from; }

$dateParams = [$from, $to];

require_once __DIR__ . '/includes/header.php';
?>

<div class="panel" style="margin-bottom:18px">
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
    <?php foreach ($reportTabs as $key => $label): ?>
    <a href="?r=<?= e($key) ?>&from=<?= e($from) ?>&to=<?= e($to) ?>" class="btn <?= $report === $key ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
    <input type="hidden" name="r" value="<?= e($report) ?>">
    <div class="form-group" style="margin-bottom:0">
      <label>From</label>
      <input class="form-control" type="date" name="from" value="<?= e($from) ?>">
    </div>
    <div class="form-group" style="margin-bottom:0">
      <label>To</label>
      <input class="form-control" type="date" name="to" value="<?= e($to) ?>">
    </div>
    <button class="btn btn-outline" type="submit">Apply</button>
  </form>
</div>

<?php if ($report === 'daily-sales'): ?>
  <?php
  $stmt = $pdo->prepare("SELECT DATE(created_at) AS d, COUNT(*) AS orders_count,
      COALESCE(SUM(subtotal),0) AS gross_sales,
      COALESCE(SUM(discount + loyalty_discount),0) AS discounts,
      COALESCE(SUM(total),0) AS net_sales
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelled'
    GROUP BY DATE(created_at) ORDER BY d DESC");
  $stmt->execute($dateParams);
  $rows = $stmt->fetchAll();

  $totalOrders = 0; $totalGross = 0; $totalDiscount = 0; $totalNet = 0;
  foreach ($rows as $r) { $totalOrders += (int)$r['orders_count']; $totalGross += $r['gross_sales']; $totalDiscount += $r['discounts']; $totalNet += $r['net_sales']; }
  $overallAov = $totalOrders > 0 ? $totalNet / $totalOrders : 0;
  ?>
  <div class="stat-grid">
    <div class="stat-card"><div class="label">Orders</div><div class="value"><?= $totalOrders ?></div></div>
    <div class="stat-card"><div class="label">Avg Order Value</div><div class="value"><?= money($overallAov) ?></div></div>
    <div class="stat-card"><div class="label">Discounts</div><div class="value"><?= money($totalDiscount) ?></div></div>
    <div class="stat-card"><div class="label">Net Sale</div><div class="value"><?= money($totalNet) ?></div></div>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Daily Sales (<?= e($from) ?> to <?= e($to) ?>)</h2></div>
    <table>
      <thead><tr><th>Date</th><th>Orders</th><th>Sales</th><th>Avg Order Value</th><th>Discounts</th><th>Net Sale</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="6">No orders in this range.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): $aov = $r['orders_count'] > 0 ? $r['net_sales'] / $r['orders_count'] : 0; ?>
        <tr>
          <td><?= e(date('d M Y', strtotime($r['d']))) ?></td>
          <td><?= (int)$r['orders_count'] ?></td>
          <td><?= money($r['gross_sales']) ?></td>
          <td><?= money($aov) ?></td>
          <td><?= money($r['discounts']) ?></td>
          <td><?= money($r['net_sales']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php elseif ($report === 'product-sales'): ?>
  <?php
  $stmt = $pdo->prepare("SELECT p.id, COALESCE(p.name, oi.item_name) AS name, c.name AS category_name,
      COALESCE(SUM(oi.quantity),0) AS qty, COALESCE(SUM(oi.line_total),0) AS revenue
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    LEFT JOIN products p ON p.id = oi.product_id
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE oi.product_id IS NOT NULL AND DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'
    GROUP BY p.id, name, c.name
    ORDER BY revenue DESC");
  $stmt->execute($dateParams);
  $rows = $stmt->fetchAll();
  $totalQty = array_sum(array_column($rows, 'qty'));
  $totalRevenue = array_sum(array_column($rows, 'revenue'));
  ?>
  <div class="stat-grid">
    <div class="stat-card"><div class="label">Products Sold</div><div class="value"><?= (int)$totalQty ?></div></div>
    <div class="stat-card"><div class="label">Total Revenue</div><div class="value"><?= money($totalRevenue) ?></div></div>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Product Sales (<?= e($from) ?> to <?= e($to) ?>)</h2></div>
    <table>
      <thead><tr><th>#</th><th>Product</th><th>Category</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="5">No product sales in this range.</td></tr><?php endif; ?>
        <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= e($r['name']) ?></td>
          <td><?= e($r['category_name'] ?? '—') ?></td>
          <td><?= (int)$r['qty'] ?></td>
          <td><?= money($r['revenue']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php elseif ($report === 'category-sales'): ?>
  <?php
  $stmt = $pdo->prepare("SELECT c.id, c.name, COALESCE(SUM(oi.quantity),0) AS qty, COALESCE(SUM(oi.line_total),0) AS revenue
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN products p ON p.id = oi.product_id
    JOIN categories c ON c.id = p.category_id
    WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'
    GROUP BY c.id, c.name
    ORDER BY revenue DESC");
  $stmt->execute($dateParams);
  $rows = $stmt->fetchAll();
  $totalRevenue = array_sum(array_column($rows, 'revenue'));
  ?>
  <div class="panel">
    <div class="panel-head"><h2>Category Sales (<?= e($from) ?> to <?= e($to) ?>)</h2></div>
    <table>
      <thead><tr><th>Category</th><th>Qty Sold</th><th>Revenue</th><th>Share</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="4">No category sales in this range.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): $share = $totalRevenue > 0 ? ($r['revenue'] / $totalRevenue) * 100 : 0; ?>
        <tr>
          <td><?= e($r['name']) ?></td>
          <td><?= (int)$r['qty'] ?></td>
          <td><?= money($r['revenue']) ?></td>
          <td><?= number_format($share, 1) ?>%</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php elseif ($report === 'profit'): ?>
  <?php
  ensure_product_cost_schema();
  ensure_deal_cost_schema();
  ensure_order_item_cost_schema();
  $stmt = $pdo->prepare("SELECT
      CASE WHEN oi.deal_id IS NOT NULL THEN 'Deal' ELSE 'Product' END AS item_type,
      oi.item_name AS name,
      COALESCE(SUM(oi.quantity),0) AS qty,
      COALESCE(SUM(oi.line_total),0) AS revenue,
      COALESCE(SUM(CASE WHEN COALESCE(oi.cost_price_snapshot, p.cost_price, d.cost_price) IS NOT NULL
        THEN oi.quantity * COALESCE(oi.cost_price_snapshot, p.cost_price, d.cost_price) ELSE 0 END),0) AS known_cost,
      COALESCE(SUM(CASE WHEN COALESCE(oi.cost_price_snapshot, p.cost_price, d.cost_price) IS NULL
        THEN oi.quantity ELSE 0 END),0) AS missing_cost_qty,
      COALESCE(SUM(CASE WHEN COALESCE(oi.cost_price_snapshot, p.cost_price, d.cost_price) IS NOT NULL
        THEN oi.line_total - (oi.quantity * COALESCE(oi.cost_price_snapshot, p.cost_price, d.cost_price)) ELSE 0 END),0) AS known_profit
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    LEFT JOIN products p ON p.id = oi.product_id
    LEFT JOIN deals d ON d.id = oi.deal_id
    WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'
    GROUP BY item_type, oi.product_id, oi.deal_id, oi.item_name
    ORDER BY known_profit DESC, revenue DESC");
  $stmt->execute($dateParams);
  $rows = $stmt->fetchAll();
  $totalRevenue = 0.0;
  $knownCost = 0.0;
  $knownProfit = 0.0;
  $missingCostQty = 0;
  foreach ($rows as $row) {
    $totalRevenue += (float)$row['revenue'];
    $knownCost += (float)$row['known_cost'];
    $knownProfit += (float)$row['known_profit'];
    $missingCostQty += (int)$row['missing_cost_qty'];
  }
  ?>
  <div class="stat-grid">
    <div class="stat-card"><div class="label">Item Revenue</div><div class="value"><?= money($totalRevenue) ?></div></div>
    <div class="stat-card"><div class="label">Known Cost of Goods</div><div class="value"><?= money($knownCost) ?></div></div>
    <div class="stat-card"><div class="label">Gross Profit (Known Costs)</div><div class="value"><?= money($knownProfit) ?></div></div>
    <div class="stat-card"><div class="label">Units Missing Cost</div><div class="value"><?= (int)$missingCostQty ?></div></div>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Item Profit (<?= e($from) ?> to <?= e($to) ?>)</h2></div>
    <p style="color:var(--muted);font-size:12px;margin-bottom:12px">Gross profit is item revenue minus item cost; it excludes delivery fees, operating expenses, and order-level discounts. Enter cost prices for products and deals to complete the report. New orders save a cost snapshot; older orders without a snapshot use the current catalog cost when available.</p>
    <table>
      <thead><tr><th>Type</th><th>Item</th><th>Qty Sold</th><th>Revenue</th><th>Cost</th><th>Gross Profit</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="6">No item sales in this range.</td></tr><?php endif; ?>
        <?php foreach ($rows as $row): $hasUnknownCost = (int)$row['missing_cost_qty'] > 0; ?>
        <tr>
          <td><?= e($row['item_type']) ?></td>
          <td><?= e($row['name']) ?></td>
          <td><?= (int)$row['qty'] ?><?= $hasUnknownCost ? ' (' . (int)$row['missing_cost_qty'] . ' missing cost)' : '' ?></td>
          <td><?= money($row['revenue']) ?></td>
          <td><?= $hasUnknownCost ? 'Incomplete · ' . money($row['known_cost']) . ' known' : money($row['known_cost']) ?></td>
          <td><?= $hasUnknownCost ? 'Incomplete · ' . money($row['known_profit']) . ' known' : money($row['known_profit']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php elseif ($report === 'deal-sales'): ?>
  <?php
  $stmt = $pdo->prepare("SELECT oi.deal_id, COALESCE(d.title, oi.item_name) AS name,
      COALESCE(SUM(oi.quantity),0) AS qty,
      COALESCE(SUM(oi.line_total),0) AS revenue,
      COUNT(DISTINCT oi.order_id) AS orders_touched
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    LEFT JOIN deals d ON d.id = oi.deal_id
    WHERE oi.deal_id IS NOT NULL AND DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'
    GROUP BY oi.deal_id, name
    ORDER BY qty DESC, revenue DESC, name ASC");
  $stmt->execute($dateParams);
  $rows = $stmt->fetchAll();
  $totalQty = array_sum(array_map(static fn($row) => (int)$row['qty'], $rows));
  $totalRevenue = array_sum(array_map(static fn($row) => (float)$row['revenue'], $rows));
  $ordersStmt = $pdo->prepare("SELECT COUNT(DISTINCT oi.order_id)
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE oi.deal_id IS NOT NULL AND DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'");
  $ordersStmt->execute($dateParams);
  $totalOrders = (int)$ordersStmt->fetchColumn();
  $topDeal = $rows[0] ?? null;
  ?>
  <div class="stat-grid">
    <div class="stat-card"><div class="label">Deal Packs Sold</div><div class="value"><?= (int)$totalQty ?></div></div>
    <div class="stat-card"><div class="label">Deal Revenue</div><div class="value"><?= money($totalRevenue) ?></div></div>
    <div class="stat-card"><div class="label">Orders With Deals</div><div class="value"><?= (int)$totalOrders ?></div></div>
    <div class="stat-card"><div class="label">Best-Selling Deal</div><div class="value" style="font-size:18px"><?= e($topDeal['name'] ?? '—') ?></div><div class="sub"><?= $topDeal ? (int)$topDeal['qty'] . ' sold' : 'No deal sales' ?></div></div>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Deal Sales (<?= e($from) ?> to <?= e($to) ?>)</h2></div>
    <p style="color:var(--muted);font-size:12px;margin-bottom:12px">Ranked by deal packs sold. Quantities count deal purchases, not the individual products included in each deal. Cancelled orders are excluded.</p>
    <table>
      <thead><tr><th>#</th><th>Deal</th><th>Deal Packs Sold</th><th>Orders Containing</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="5">No deal sales in this range.</td></tr><?php endif; ?>
        <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= e($r['name']) ?></td>
          <td><?= (int)$r['qty'] ?></td>
          <td><?= (int)$r['orders_touched'] ?></td>
          <td><?= money($r['revenue']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php elseif ($report === 'day-of-week'): ?>
  <?php
  $stmt = $pdo->prepare("SELECT DAYOFWEEK(created_at) AS dow, COUNT(*) AS orders_count, COALESCE(SUM(total),0) AS revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelled'
    GROUP BY DAYOFWEEK(created_at)");
  $stmt->execute($dateParams);
  $byDow = [];
  foreach ($stmt->fetchAll() as $r) { $byDow[(int)$r['dow']] = $r; }

  // MySQL DAYOFWEEK: 1=Sunday ... 7=Saturday. Display Monday-first, weekend last.
  $order = [2 => 'Monday', 3 => 'Tuesday', 4 => 'Wednesday', 5 => 'Thursday', 6 => 'Friday', 7 => 'Saturday', 1 => 'Sunday'];
  $weekendTotal = 0; $weekdayTotal = 0; $weekendOrders = 0; $weekdayOrders = 0;
  $rows = [];
  foreach ($order as $dow => $label) {
    $r = $byDow[$dow] ?? ['orders_count' => 0, 'revenue' => 0];
    $isWeekend = in_array($dow, [1, 7], true);
    $rows[] = ['label' => $label, 'orders_count' => (int)$r['orders_count'], 'revenue' => (float)$r['revenue'], 'is_weekend' => $isWeekend];
    if ($isWeekend) { $weekendTotal += $r['revenue']; $weekendOrders += $r['orders_count']; }
    else { $weekdayTotal += $r['revenue']; $weekdayOrders += $r['orders_count']; }
  }
  ?>
  <div class="stat-grid">
    <div class="stat-card"><div class="label">Weekday Revenue</div><div class="value"><?= money($weekdayTotal) ?></div><div class="sub"><?= $weekdayOrders ?> orders</div></div>
    <div class="stat-card"><div class="label">Weekend Revenue</div><div class="value"><?= money($weekendTotal) ?></div><div class="sub"><?= $weekendOrders ?> orders</div></div>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Day-of-Week Performance (<?= e($from) ?> to <?= e($to) ?>)</h2></div>
    <table>
      <thead><tr><th>Day</th><th>Orders</th><th>Revenue</th><th>Avg Order Value</th><th>Type</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $aov = $r['orders_count'] > 0 ? $r['revenue'] / $r['orders_count'] : 0; ?>
        <tr>
          <td><?= e($r['label']) ?></td>
          <td><?= $r['orders_count'] ?></td>
          <td><?= money($r['revenue']) ?></td>
          <td><?= money($aov) ?></td>
          <td><span class="badge <?= $r['is_weekend'] ? 'badge-blue' : 'badge-gray' ?>"><?= $r['is_weekend'] ? 'Weekend' : 'Weekday' ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php elseif ($report === 'deal-vs-individual'): ?>
  <?php
  $stmt = $pdo->prepare("SELECT
      CASE WHEN oi.deal_id IS NOT NULL THEN 'deal' ELSE 'individual' END AS item_type,
      COALESCE(SUM(oi.quantity),0) AS qty,
      COALESCE(SUM(oi.line_total),0) AS revenue,
      COUNT(DISTINCT oi.order_id) AS orders_touched
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'
    GROUP BY item_type");
  $stmt->execute($dateParams);
  $byType = ['deal' => ['qty' => 0, 'revenue' => 0, 'orders_touched' => 0], 'individual' => ['qty' => 0, 'revenue' => 0, 'orders_touched' => 0]];
  foreach ($stmt->fetchAll() as $r) { $byType[$r['item_type']] = $r; }
  $totalRevenue = (float)$byType['deal']['revenue'] + (float)$byType['individual']['revenue'];
  $totalQty = (int)$byType['deal']['qty'] + (int)$byType['individual']['qty'];
  ?>
  <div class="stat-grid">
    <div class="stat-card"><div class="label">Deal Revenue</div><div class="value"><?= money($byType['deal']['revenue']) ?></div><div class="sub"><?= $totalRevenue > 0 ? number_format(($byType['deal']['revenue'] / $totalRevenue) * 100, 1) : 0 ?>% of sales</div></div>
    <div class="stat-card"><div class="label">Individual Item Revenue</div><div class="value"><?= money($byType['individual']['revenue']) ?></div><div class="sub"><?= $totalRevenue > 0 ? number_format(($byType['individual']['revenue'] / $totalRevenue) * 100, 1) : 0 ?>% of sales</div></div>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Deal vs Individual Item Sales (<?= e($from) ?> to <?= e($to) ?>)</h2></div>
    <table>
      <thead><tr><th>Type</th><th>Qty Sold</th><th>Revenue</th><th>Share of Revenue</th><th>Orders Containing</th><th>Avg Revenue / Unit</th></tr></thead>
      <tbody>
        <?php foreach (['deal' => 'Deals', 'individual' => 'Individual Items'] as $key => $label): $r = $byType[$key]; $share = $totalRevenue > 0 ? ($r['revenue'] / $totalRevenue) * 100 : 0; $perUnit = $r['qty'] > 0 ? $r['revenue'] / $r['qty'] : 0; ?>
        <tr>
          <td><?= e($label) ?></td>
          <td><?= (int)$r['qty'] ?></td>
          <td><?= money($r['revenue']) ?></td>
          <td><?= number_format($share, 1) ?>%</td>
          <td><?= (int)$r['orders_touched'] ?></td>
          <td><?= money($perUnit) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
