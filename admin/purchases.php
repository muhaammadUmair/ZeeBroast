<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
ensure_inventory_items_schema();
ensure_purchases_schema();

$admin_page_title = 'Purchases';
$active_admin = 'purchases';
$pdo = db();
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_item') {
    header('Content-Type: application/json');
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    if (!verify_csrf()) {
        $response = ['ok' => false, 'message' => 'Invalid session, please reload the page.'];
    } elseif ($name === '' || $unit === '') {
        $response = ['ok' => false, 'message' => 'Please provide a name and unit.'];
    } else {
        $dupe = $pdo->prepare('SELECT id FROM inventory_items WHERE name = ?');
        $dupe->execute([$name]);
        if ($dupe->fetch()) {
            $response = ['ok' => false, 'message' => 'An item with this name already exists.'];
        } else {
            $pdo->prepare('INSERT INTO inventory_items (name, category, unit) VALUES (?,?,?)')->execute([$name, $category ?: null, $unit]);
            $response = ['ok' => true, 'item' => ['id' => (int)$pdo->lastInsertId(), 'name' => $name, 'category' => $category, 'unit' => $unit]];
        }
    }
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save') {
        $editId = (int)($_POST['id'] ?? 0);
        $purchase_date = trim($_POST['purchase_date'] ?? '');
        $item_id = (int)($_POST['item_id'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        $total_cost = (float)($_POST['total_cost'] ?? 0);
        $supplier_name = trim($_POST['supplier_name'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $item = null;
        if ($item_id > 0) {
            $itemStmt = $pdo->prepare('SELECT * FROM inventory_items WHERE id = ?');
            $itemStmt->execute([$item_id]);
            $item = $itemStmt->fetch() ?: null;
        }

        if ($purchase_date === '' || strtotime($purchase_date) === false) {
            $error = 'Please provide a valid purchase date.';
        } elseif (!$item) {
            $error = 'Please select an item.';
        } elseif ($quantity <= 0 || $total_cost < 0) {
          $error = 'Please provide a valid quantity and total cost.';
        } else {
            // Name/category/unit are snapshotted from the item so past purchases stay accurate if the item is later edited or removed.
          $total_cost = round($total_cost, 2);
          $unit_cost = round($total_cost / $quantity, 2);
            if ($editId > 0) {
                $stmt = $pdo->prepare('UPDATE purchases SET purchase_date=?, item_id=?, item_name=?, category=?, quantity=?, unit=?, unit_cost=?, total_cost=?, supplier_name=?, notes=? WHERE id=?');
                $stmt->execute([$purchase_date, $item['id'], $item['name'], $item['category'], $quantity, $item['unit'], $unit_cost, $total_cost, $supplier_name ?: null, $notes ?: null, $editId]);
                $success = 'Purchase updated.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO purchases (purchase_date, item_id, item_name, category, quantity, unit, unit_cost, total_cost, supplier_name, notes, created_by_admin_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$purchase_date, $item['id'], $item['name'], $item['category'], $quantity, $item['unit'], $unit_cost, $total_cost, $supplier_name ?: null, $notes ?: null, current_admin()['id'] ?? null]);
                $success = 'Purchase recorded.';
            }
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM purchases WHERE id = ?')->execute([(int)$_POST['id']]);
        $success = 'Purchase deleted.';
    }
}

$editingPurchase = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editingPurchase = $stmt->fetch() ?: null;
}

$inventoryItems = $pdo->query("SELECT * FROM inventory_items WHERE status = 'active' ORDER BY category, name")->fetchAll();

$from = trim($_GET['from'] ?? '') ?: date('Y-m-d', strtotime('-29 days'));
$to = trim($_GET['to'] ?? '') ?: date('Y-m-d');
if (strtotime($to) < strtotime($from)) { $to = $from; }
$search = trim($_GET['q'] ?? '');

$where = ['purchase_date BETWEEN ? AND ?'];
$params = [$from, $to];
if ($search !== '') { $where[] = '(item_name LIKE ? OR supplier_name LIKE ? OR category LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT * FROM purchases $whereSql ORDER BY purchase_date DESC, id DESC");
$stmt->execute($params);
$purchases = $stmt->fetchAll();

$totalSpend = 0.0;
foreach ($purchases as $p) { $totalSpend += (float)$p['total_cost']; }
$purchaseCount = count($purchases);

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head"><h2><?= $editingPurchase ? 'Edit Purchase' : 'Record Purchase' ?></h2></div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="form_action" value="save">
    <input type="hidden" name="id" value="<?= (int)($editingPurchase['id'] ?? 0) ?>">
    <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <div class="form-group" style="margin-bottom:0;flex:0 0 150px"><label>Date</label><input class="form-control" type="date" name="purchase_date" value="<?= e($editingPurchase['purchase_date'] ?? date('Y-m-d')) ?>" required></div>
      <div class="form-group" style="margin-bottom:0;flex:2 1 240px;position:relative"><label>Item</label>
        <input class="form-control" type="text" id="itemSearch" autocomplete="off" placeholder="Search item...">
        <input type="hidden" name="item_id" id="itemId" value="<?= (int)($editingPurchase['item_id'] ?? 0) ?>">
        <div id="itemList" style="display:none;position:absolute;left:0;right:0;top:100%;z-index:20;max-height:240px;overflow-y:auto;background:var(--panel);border:1px solid var(--border);border-radius:8px;margin-top:4px"></div>
      </div>
      <div class="form-group" style="margin-bottom:0;flex:0 0 110px"><label>Quantity</label><input class="form-control" type="number" step="0.001" min="0.001" name="quantity" id="purchaseQuantity" value="<?= e($editingPurchase['quantity'] ?? '') ?>" required oninput="updateUnitCostPreview()"></div>
      <div class="form-group" style="margin-bottom:0;flex:0 0 140px"><label>Total Cost (Rs.)</label><input class="form-control" type="number" step="0.01" min="0" name="total_cost" id="purchaseTotalCost" value="<?= e($editingPurchase['total_cost'] ?? '') ?>" required oninput="updateUnitCostPreview()"></div>
      <div class="form-group" style="margin-bottom:0;flex:0 0 160px"><label>Unit Cost (auto)</label><input class="form-control" id="purchaseUnitCostPreview" value="<?= $editingPurchase ? e(money_precise($editingPurchase['unit_cost'])) : '' ?>" readonly></div>
      <div class="form-group" style="margin-bottom:0;flex:1 1 160px"><label>Supplier (optional)</label><input class="form-control" name="supplier_name" value="<?= e($editingPurchase['supplier_name'] ?? '') ?>"></div>
      <div style="display:flex;gap:8px">
        <button type="submit" class="btn btn-primary"><?= $editingPurchase ? 'Update' : 'Save' ?></button>
        <?php if ($editingPurchase): ?><a href="<?= base_url('admin/purchases.php') ?>" class="btn btn-outline">Cancel</a><?php endif; ?>
      </div>
    </div>
    <div class="form-group" style="margin:12px 0 0"><label>Notes (optional)</label><input class="form-control" name="notes" value="<?= e($editingPurchase['notes'] ?? '') ?>"></div>
  </form>
</div>

<script>
const purchaseCurrencySymbol = <?= json_encode(setting('currency_symbol', 'Rs.')) ?>;
function updateUnitCostPreview() {
  const quantity = parseFloat(document.getElementById('purchaseQuantity').value);
  const totalCost = parseFloat(document.getElementById('purchaseTotalCost').value);
  const preview = document.getElementById('purchaseUnitCostPreview');
  preview.value = quantity > 0 && Number.isFinite(totalCost)
    ? purchaseCurrencySymbol + ' ' + (totalCost / quantity).toFixed(2)
    : '';
}
updateUnitCostPreview();
</script>

<div id="addItemModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:100;align-items:center;justify-content:center">
  <div class="panel" style="width:100%;max-width:420px;margin:0">
    <div class="panel-head"><h2>Add Item</h2></div>
    <div id="addItemError" class="alert alert-error" style="display:none"></div>
    <div class="form-group"><label>Name</label><input class="form-control" id="newItemName"></div>
    <div class="form-group"><label>Category (optional)</label><input class="form-control" id="newItemCategory" placeholder="Meat, Vegetables, Packaging..."></div>
    <div class="form-group"><label>Unit</label><input class="form-control" id="newItemUnit" placeholder="kg, litre, pcs, dozen..."></div>
    <div style="display:flex;gap:8px">
      <button type="button" class="btn btn-primary" id="saveNewItem">Add Item</button>
      <button type="button" class="btn btn-outline" id="cancelNewItem">Cancel</button>
    </div>
  </div>
</div>

<script>
(function () {
  const items = <?= json_encode(array_map(static fn($it) => ['id' => (int)$it['id'], 'name' => $it['name'], 'category' => $it['category'] ?? '', 'unit' => $it['unit']], $inventoryItems), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const csrfToken = <?= json_encode(csrf_token()) ?>;
  const search = document.getElementById('itemSearch');
  const hidden = document.getElementById('itemId');
  const list = document.getElementById('itemList');
  const modal = document.getElementById('addItemModal');
  const modalError = document.getElementById('addItemError');

  const label = (it) => it.name + ' (' + it.unit + ')' + (it.category ? ' — ' + it.category : '');

  <?php if ($editingPurchase): ?>
  search.value = <?= json_encode($editingPurchase['item_name'] . ' (' . $editingPurchase['unit'] . ')') ?>;
  <?php endif; ?>

  function renderList() {
    const q = search.value.trim().toLowerCase();
    list.innerHTML = '';
    items.filter((it) => !q || label(it).toLowerCase().includes(q)).forEach((it) => {
      const row = document.createElement('div');
      row.textContent = label(it);
      row.style.cssText = 'padding:9px 12px;cursor:pointer;font-size:13px';
      row.addEventListener('mouseenter', () => { row.style.background = 'var(--card)'; });
      row.addEventListener('mouseleave', () => { row.style.background = ''; });
      row.addEventListener('mousedown', (e) => { e.preventDefault(); pick(it); });
      list.appendChild(row);
    });
    const add = document.createElement('div');
    add.textContent = '+ Add new item' + (search.value.trim() ? ' "' + search.value.trim() + '"' : '');
    add.style.cssText = 'padding:9px 12px;cursor:pointer;font-size:13px;color:var(--red);border-top:1px solid var(--border)';
    add.addEventListener('mousedown', (e) => { e.preventDefault(); openModal(search.value.trim()); });
    list.appendChild(add);
    list.style.display = 'block';
  }

  function pick(it) {
    hidden.value = it.id;
    search.value = label(it);
    list.style.display = 'none';
  }

  function openModal(name) {
    document.getElementById('newItemName').value = name;
    document.getElementById('newItemCategory').value = '';
    document.getElementById('newItemUnit').value = '';
    modalError.style.display = 'none';
    list.style.display = 'none';
    modal.style.display = 'flex';
    document.getElementById(name ? 'newItemUnit' : 'newItemName').focus();
  }

  search.addEventListener('focus', renderList);
  search.addEventListener('input', () => { hidden.value = ''; renderList(); });
  search.addEventListener('blur', () => { list.style.display = 'none'; });
  document.getElementById('cancelNewItem').addEventListener('click', () => { modal.style.display = 'none'; });

  document.getElementById('saveNewItem').addEventListener('click', async () => {
    const body = new FormData();
    body.append('form_action', 'add_item');
    body.append('csrf_token', csrfToken);
    body.append('name', document.getElementById('newItemName').value);
    body.append('category', document.getElementById('newItemCategory').value);
    body.append('unit', document.getElementById('newItemUnit').value);
    try {
      const res = await fetch(window.location.pathname, { method: 'POST', body });
      const data = await res.json();
      if (!data.ok) {
        modalError.textContent = data.message || 'Could not add item.';
        modalError.style.display = 'block';
        return;
      }
      items.push(data.item);
      pick(data.item);
      modal.style.display = 'none';
    } catch (err) {
      modalError.textContent = 'Network error, please try again.';
      modalError.style.display = 'block';
    }
  });

  search.form.addEventListener('submit', (e) => {
    if (!hidden.value || hidden.value === '0') {
      e.preventDefault();
      search.focus();
      renderList();
    }
  });
})();
</script>

<div class="panel">
  <div class="panel-head"><h2>Purchase History</h2></div>

  <form method="get" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:end">
    <div class="form-group" style="margin-bottom:0"><label>From</label><input class="form-control" type="date" name="from" value="<?= e($from) ?>"></div>
    <div class="form-group" style="margin-bottom:0"><label>To</label><input class="form-control" type="date" name="to" value="<?= e($to) ?>"></div>
    <div class="form-group" style="margin-bottom:0"><label>Search</label><input class="form-control" type="text" name="q" placeholder="Item, supplier, category" value="<?= e($search) ?>" style="max-width:220px"></div>
    <button class="btn btn-outline" type="submit">Filter</button>
  </form>

  <div class="stat-grid" style="margin-bottom:18px">
    <div class="stat-card"><div class="label">Total Spend</div><div class="value"><?= money($totalSpend) ?></div></div>
    <div class="stat-card"><div class="label">Purchase Entries</div><div class="value"><?= (int)$purchaseCount ?></div></div>
  </div>

  <table>
    <thead><tr><th>Date</th><th>Item</th><th>Category</th><th>Qty</th><th>Unit Cost</th><th>Total Cost</th><th>Supplier</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($purchases)): ?><tr><td colspan="8">No purchases recorded in this range.</td></tr><?php endif; ?>
      <?php foreach ($purchases as $p): ?>
      <tr>
        <td><?= e(date('d M Y', strtotime($p['purchase_date']))) ?></td>
        <td><?= e($p['item_name']) ?><?php if ($p['notes']): ?><br><span style="color:var(--muted);font-size:11.5px"><?= e($p['notes']) ?></span><?php endif; ?></td>
        <td><?= e($p['category'] ?: '—') ?></td>
        <td><?= rtrim(rtrim(number_format((float)$p['quantity'], 3), '0'), '.') ?> <?= e($p['unit']) ?></td>
        <td><?= money($p['unit_cost']) ?></td>
        <td><?= money($p['total_cost']) ?></td>
        <td><?= e($p['supplier_name'] ?: '—') ?></td>
        <td style="display:flex;gap:8px">
          <a href="<?= base_url('admin/purchases.php?edit=' . (int)$p['id'] . '&from=' . urlencode($from) . '&to=' . urlencode($to)) ?>" class="btn btn-outline btn-sm">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this purchase?');">
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
