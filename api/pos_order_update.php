<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
$token = preg_replace('/^Bearer\s+/i', '', $authorization);
$token = $token ?: ($_SERVER['HTTP_X_INTEGRATION_TOKEN'] ?? '');
$expected = POS_API_TOKEN;
if ($expected === '' || !hash_equals($expected, $token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid API token']);
    exit;
}

$data = json_decode((string)file_get_contents('php://input'), true);
$orderCode = trim((string)($data['order_code'] ?? ''));
$status = (string)($data['status'] ?? '');
$paymentStatus = (string)($data['payment_status'] ?? '');
$allowedStatuses = ['pending', 'confirmed', 'preparing', 'on_the_way', 'delivered', 'cancelled'];
$allowedPayments = ['pending', 'paid', 'failed', 'refunded'];
if ($orderCode === '' || (!in_array($status, $allowedStatuses, true) && !in_array($paymentStatus, $allowedPayments, true))) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid order update']);
    exit;
}

$updates = [];
$params = [];
if (in_array($status, $allowedStatuses, true)) { $updates[] = 'status = ?'; $params[] = $status; }
if (in_array($paymentStatus, $allowedPayments, true)) { $updates[] = 'payment_status = ?'; $params[] = $paymentStatus; }
$params[] = $orderCode;
$stmt = db()->prepare('UPDATE orders SET ' . implode(', ', $updates) . ' WHERE order_code = ?');
$stmt->execute($params);
echo json_encode(['success' => true, 'updated' => $stmt->rowCount() > 0]);