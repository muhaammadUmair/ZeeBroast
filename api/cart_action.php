<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {
    if ($action === 'add') {
        $type = $_POST['type'] === 'deal' ? 'deal' : 'product';
        $id = (int)($_POST['id'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));

        if ($type === 'product') {
            $stmt = db()->prepare("SELECT id, name, price, sale_price FROM products WHERE id = ? AND status = 'active'");
        } else {
            $stmt = db()->prepare("SELECT id, title AS name, deal_price AS price, NULL AS sale_price FROM deals WHERE id = ? AND status = 'active'");
        }
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if (!$item) {
            echo json_encode(['ok' => false, 'message' => 'Item not found']);
            exit;
        }

        $price = !empty($item['sale_price']) ? (float)$item['sale_price'] : (float)$item['price'];
        cart_add($type, (int)$item['id'], $item['name'], $price, $qty);

        echo json_encode(['ok' => true, 'count' => cart_count(), 'subtotal' => cart_subtotal(), 'name' => $item['name']]);
        exit;
    }

    if ($action === 'update') {
        $key = $_POST['key'] ?? '';
        $qty = (int)($_POST['qty'] ?? 0);
        cart_update($key, $qty);
        echo json_encode(['ok' => true, 'count' => cart_count(), 'subtotal' => cart_subtotal()]);
        exit;
    }

    if ($action === 'remove') {
        cart_remove($_POST['key'] ?? '');
        echo json_encode(['ok' => true, 'count' => cart_count(), 'subtotal' => cart_subtotal()]);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Unknown action']);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => 'Server error']);
}
