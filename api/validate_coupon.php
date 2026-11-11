<?php
// ============================================================
// API: Kiểm tra mã giảm giá — api/validate_coupon.php
// Nhận {code, items:[{id, qty}]}, tính lại tạm tính từ DB rồi trả số tiền giảm.
// ============================================================
ob_start();
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/shop_helpers.php';

header('Content-Type: application/json; charset=utf-8');

function reply($data, $http_code = 200) {
    ob_clean();
    http_response_code($http_code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reply(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Chỉ khách đã đăng nhập mới dò mã (tránh người lạ thử đoán mã giảm giá)
if (empty($_SESSION['user_id'])) {
    reply(['success' => false, 'message' => 'Vui lòng đăng nhập để dùng mã giảm giá.'], 401);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    reply(['success' => false, 'message' => 'Dữ liệu không hợp lệ'], 400);
}

try {
    $cart = build_cart_lines($conn, $body['items'] ?? []);
    if ($cart['error']) {
        reply(['success' => false, 'message' => $cart['error']], 422);
    }

    $subtotal = $cart['subtotal'];
    $shipping = shipping_fee_for($subtotal);
    $ev       = evaluate_coupon($conn, $body['code'] ?? '', $subtotal, $shipping);

    if (!$ev['ok']) {
        reply(['success' => false, 'message' => $ev['message']], 422);
    }

    reply([
        'success'      => true,
        'message'      => 'OK',
        'label'        => coupon_label($ev['coupon']),
        'discount'     => $ev['discount'],
        'subtotal'     => $subtotal,
        'shipping_fee' => $shipping,
        'total'        => max(0, $subtotal + $shipping - $ev['discount']),
    ]);
} catch (Throwable $e) {
    error_log('validate_coupon error: ' . $e->getMessage());
    reply(['success' => false, 'message' => 'Không kiểm tra được mã, vui lòng thử lại.'], 500);
}