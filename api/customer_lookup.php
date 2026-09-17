<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Invalid session. Please refresh and try again.']);
    exit;
}

if (strcasecmp(trim((string)($_POST['operator_name'] ?? '')), 'ZeeBroast') !== 0) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Customer search is only available for ZeeBroast.']);
    exit;
}

$phone = normalize_phone_number($_POST['phone'] ?? '');
if ($phone === '') {
    echo json_encode(['ok' => false, 'message' => 'Enter a valid phone number.']);
    exit;
}

try {
    $customerStmt = db()->prepare("SELECT id, full_name, phone FROM users WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ? AND status = 'active' LIMIT 1");
    $customerStmt->execute([$phone]);
    $customer = $customerStmt->fetch();

    if (!$customer) {
        echo json_encode(['ok' => false, 'message' => 'No active customer was found for this phone number.']);
        exit;
    }

    $addressStmt = db()->prepare('SELECT id, label, house_no, street, city, instructions FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
    $addressStmt->execute([$customer['id']]);
    $customer['addresses'] = $addressStmt->fetchAll();
    $_SESSION['checkout_selected_customer_id'] = (int)$customer['id'];

    echo json_encode(['ok' => true, 'customer' => $customer]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Customer search is unavailable.']);
}
