<?php
// ============================================================
// API: Đặt hàng — api/place_order.php
// Giá, phí ship, mã giảm giá và tổng tiền đều được TÍNH LẠI Ở SERVER.
// Trình duyệt chỉ gửi: id sản phẩm + số lượng + mã giảm giá + thông tin nhận hàng.
// ============================================================
ob_start();
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/shop_helpers.php';

// Bắt buộc mysqli ném exception khi lỗi để transaction rollback đúng (mọi phiên bản PHP)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: application/json; charset=utf-8');

function fail($http_code, $message) {
    ob_clean();
    http_response_code($http_code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}
function field($body, $key, $max) {
    return mb_substr(trim((string)($body[$key] ?? '')), 0, $max, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'Method not allowed');
}
// Chỉ khách đã đăng nhập mới được đặt hàng (chặn cả khi ai đó gọi API trực tiếp)
if (empty($_SESSION['user_id'])) {
    fail(401, 'Vui lòng đăng nhập để đặt hàng.');
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    fail(400, 'Dữ liệu JSON không hợp lệ');
}

// ====================== THÔNG TIN NHẬN HÀNG ======================
$receiver_name  = field($body, 'receiver_name', 100);
$receiver_phone = field($body, 'receiver_phone', 20);
$receiver_email = field($body, 'receiver_email', 150);
$province       = field($body, 'province', 100);
$district       = field($body, 'district', 100);
$ward           = field($body, 'ward', 100);
$address        = field($body, 'address', 255);
$note           = field($body, 'note', 1000);

$payment_method = $body['payment_method'] ?? 'cod';
if (!in_array($payment_method, ['cod', 'bank', 'momo'], true)) {
    $payment_method = 'cod';
}

$errors = [];
if ($receiver_name === '') $errors[] = 'Thiếu họ tên người nhận';
if (!preg_match('/^[0-9]{9,11}$/', $receiver_phone)) $errors[] = 'Số điện thoại không hợp lệ';
if ($receiver_email !== '' && !filter_var($receiver_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
if ($province === '') $errors[] = 'Thiếu tỉnh/thành phố';
if ($district === '') $errors[] = 'Thiếu quận/huyện';
if ($ward === '')     $errors[] = 'Thiếu phường/xã';
if ($address === '')  $errors[] = 'Thiếu địa chỉ cụ thể';

if (!empty($errors)) {
    fail(422, implode(', ', $errors));
}

try {
    // ====================== LẤY GIÁ THẬT TỪ DATABASE ======================
    $cart = build_cart_lines($conn, $body['items'] ?? []);
    if ($cart['error']) {
        fail(422, $cart['error']);
    }
    $lines    = $cart['lines'];
    $subtotal = $cart['subtotal'];

    // ====================== PHÍ SHIP, MÃ GIẢM GIÁ, TỔNG TIỀN ======================
    $shipping_fee = shipping_fee_for($subtotal);

    $coupon_code = strtoupper(field($body, 'coupon_code', 50));
    $coupon      = null;
    $discount    = 0;
    if ($coupon_code !== '') {
        $ev = evaluate_coupon($conn, $coupon_code, $subtotal, $shipping_fee);
        if (!$ev['ok']) {
            fail(422, $ev['message']);
        }
        $coupon   = $ev['coupon'];
        $discount = $ev['discount'];
    }

    $total = max(0, $subtotal + $shipping_fee - $discount);

    // ====================== TẠO MÃ ĐƠN (không trùng) ======================
    $order_code = null;
    $chk = $conn->prepare("SELECT 1 FROM orders WHERE order_code = ? LIMIT 1");
    for ($i = 0; $i < 10; $i++) {
        $candidate = 'SH' . date('ymd') . random_int(1000, 9999);
        $chk->bind_param('s', $candidate);
        $chk->execute();
        if ($chk->get_result()->num_rows === 0) { $order_code = $candidate; break; }
    }
    if ($order_code === null) {
        throw new RuntimeException('Không tạo được mã đơn');
    }

    $full_address = "$address, $ward, $district, $province";
    $user_id      = (int)$_SESSION['user_id'];

    // ====================== TRANSACTION ======================
    $conn->begin_transaction();

    $stmt = $conn->prepare("
        INSERT INTO orders 
        (order_code, user_id, receiver_name, receiver_phone, receiver_email,
         province, district, ward, address, full_address,
         subtotal, shipping_fee, discount, total, coupon_code, 
         payment_method, payment_status, status, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unpaid', 'pending', ?)
    ");
    $final_coupon_code = $coupon ? $coupon['code'] : '';
    // 17 tham số: s i + 8 chuỗi + 4 số nguyên + 3 chuỗi (coupon_code, payment_method, note)
    $stmt->bind_param(
        'sissssssssiiiisss',
        $order_code, $user_id,
        $receiver_name, $receiver_phone, $receiver_email,
        $province, $district, $ward, $address, $full_address,
        $subtotal, $shipping_fee, $discount, $total,
        $final_coupon_code, $payment_method, $note
    );
    $stmt->execute();
    $order_id = $conn->insert_id;

    $item_stmt = $conn->prepare("
        INSERT INTO order_items 
        (order_id, product_id, product_name, product_image, unit_price, quantity, subtotal)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($lines as $l) {
        $item_stmt->bind_param('iissiii',
            $order_id, $l['product_id'], $l['name'], $l['image'],
            $l['unit_price'], $l['qty'], $l['subtotal']
        );
        $item_stmt->execute();
    }

    // Tăng lượt dùng mã — điều kiện nằm trong WHERE nên 2 người cùng dùng lượt cuối thì chỉ 1 người được
    if ($coupon) {
        $u = $conn->prepare("
            UPDATE coupons SET used_count = used_count + 1
            WHERE id = ? AND (usage_limit IS NULL OR used_count < usage_limit)
        ");
        $u->bind_param('i', $coupon['id']);
        $u->execute();
        if ($u->affected_rows !== 1) {
            throw new RuntimeException('coupon_exhausted');
        }
    }

    $conn->commit();

    ob_clean();
    echo json_encode([
        'success'    => true,
        'order_code' => $order_code,
        'total'      => $total,
        'message'    => 'Đặt hàng thành công!'
    ]);

} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    if ($e->getMessage() === 'coupon_exhausted') {
        fail(409, 'Mã giảm giá vừa hết lượt sử dụng. Vui lòng bỏ mã và đặt lại.');
    }
    error_log('place_order error: ' . $e->getMessage()); // ghi log, không lộ chi tiết cho người dùng
    fail(500, 'Có lỗi xảy ra khi đặt hàng, vui lòng thử lại.');
}