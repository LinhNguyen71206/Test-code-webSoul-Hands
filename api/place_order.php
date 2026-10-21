<?php
// ============================================================
// API: Đặt hàng — api/place_order.php
// ============================================================
ob_start();
session_start();

// === SỬA ĐƯỜNG DẪN Ở ĐÂY ===
require_once __DIR__ . '/../config/database.php';   // Cách an toàn nhất

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Đọc JSON
$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu JSON không hợp lệ']);
    exit;
}

// ====================== VALIDATE ======================
$receiver_name   = trim($body['receiver_name'] ?? '');
$receiver_phone  = trim($body['receiver_phone'] ?? '');
$receiver_email  = trim($body['receiver_email'] ?? '');
$province        = trim($body['province'] ?? '');
$district        = trim($body['district'] ?? '');
$ward            = trim($body['ward'] ?? '');
$address         = trim($body['address'] ?? '');
$note            = trim($body['note'] ?? '');
$payment_method  = $body['payment_method'] ?? 'cod';
$coupon_code     = trim($body['coupon_code'] ?? '');

$items           = $body['items'] ?? [];

$subtotal        = (int)($body['subtotal'] ?? 0);
$shipping_fee    = (int)($body['shipping_fee'] ?? 0);
$discount        = (int)($body['discount'] ?? 0);
$total           = (int)($body['total'] ?? 0);

$errors = [];
if (empty($receiver_name))   $errors[] = 'Thiếu họ tên người nhận';
if (empty($receiver_phone) || !preg_match('/^[0-9]{9,11}$/', $receiver_phone)) 
    $errors[] = 'Số điện thoại không hợp lệ';
if (empty($province)) $errors[] = 'Thiếu tỉnh/thành phố';
if (empty($district)) $errors[] = 'Thiếu quận/huyện';
if (empty($ward))     $errors[] = 'Thiếu phường/xã';
if (empty($address))  $errors[] = 'Thiếu địa chỉ cụ thể';
if (empty($items))    $errors[] = 'Giỏ hàng trống';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// ====================== TẠO MÃ ĐƠN ======================
$order_code = 'SH' . date('ymd') . rand(1000, 9999);

$full_address = "$address, $ward, $district, $province";
$user_id = $_SESSION['user_id'] ?? null;

// ====================== TRANSACTION ======================
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("
        INSERT INTO orders 
        (order_code, user_id, receiver_name, receiver_phone, receiver_email,
         province, district, ward, address, full_address,
         subtotal, shipping_fee, discount, total, coupon_code, 
         payment_method, payment_status, status, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unpaid', 'pending', ?)
    ");

    $stmt->bind_param(
        'sissssssssiiiiiss',
        $order_code, $user_id,
        $receiver_name, $receiver_phone, $receiver_email,
        $province, $district, $ward, $address, $full_address,
        $subtotal, $shipping_fee, $discount, $total,
        $coupon_code, $payment_method, $note
    );

    $stmt->execute();
    $order_id = $conn->insert_id;

    // Insert order items
    $item_stmt = $conn->prepare("
        INSERT INTO order_items 
        (order_id, product_id, product_name, product_image, unit_price, quantity, subtotal)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $product_id    = (int)($item['id'] ?? 0);
        $product_name  = trim($item['name'] ?? '');
        $product_image = trim($item['imageUrl'] ?? '');
        $unit_price    = (int)($item['price'] ?? 0);
        $qty           = max(1, (int)($item['qty'] ?? 1));
        $item_subtotal = $unit_price * $qty;

        if ($unit_price <= 0 || empty($product_name)) continue;

        $item_stmt->bind_param('iissiii', 
            $order_id, $product_id, $product_name, $product_image, 
            $unit_price, $qty, $item_subtotal);
        $item_stmt->execute();
    }

    $conn->commit();

    echo json_encode([
        'success'    => true,
        'order_code' => $order_code,
        'message'    => 'Đặt hàng thành công!'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server: ' . $e->getMessage()
    ]);
}