<?php
// ============================================================
// includes/shop_helpers.php — Hàm dùng chung cho giỏ hàng, mã giảm giá, đánh giá
// Chỉ khai báo hằng số và hàm, KHÔNG in ra gì cả.
// ============================================================

if (!defined('SHIP_FREE_THRESHOLD'))     define('SHIP_FREE_THRESHOLD', 500000); // miễn phí ship từ mức này
if (!defined('SHIP_FEE'))                define('SHIP_FEE', 35000);
// true  = chỉ khách đã nhận hàng (đơn "Đã giao") mới được đánh giá sản phẩm đó
// false = khách nào đã đăng nhập cũng đánh giá được
if (!defined('REVIEW_REQUIRE_PURCHASE')) define('REVIEW_REQUIRE_PURCHASE', true);

function shipping_fee_for($subtotal) {
    return ($subtotal >= SHIP_FREE_THRESHOLD) ? 0 : SHIP_FEE;
}

/**
 * Dựng các dòng giỏ hàng từ danh sách [{id, qty}] — GIÁ LẤY TỪ DATABASE.
 * Trả về ['error' => string|null, 'lines' => [...], 'subtotal' => int]
 */
function build_cart_lines($conn, $raw_items) {
    $out = ['error' => null, 'lines' => [], 'subtotal' => 0];

    if (!is_array($raw_items) || empty($raw_items)) {
        $out['error'] = 'Giỏ hàng trống';
        return $out;
    }
    if (count($raw_items) > 50) {
        $out['error'] = 'Giỏ hàng có quá nhiều sản phẩm';
        return $out;
    }

    $wanted = [];
    foreach ($raw_items as $it) {
        if (!is_array($it)) continue;
        $pid = (int)($it['id'] ?? 0);
        if ($pid <= 0) continue;
        $qty = max(1, min(99, (int)($it['qty'] ?? 1)));
        $wanted[$pid] = min(99, ($wanted[$pid] ?? 0) + $qty);
    }
    if (empty($wanted)) {
        $out['error'] = 'Giỏ hàng không có sản phẩm hợp lệ';
        return $out;
    }

    $ids  = array_keys($wanted);
    $ph   = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("SELECT id, name, price, image_url FROM products WHERE id IN ($ph)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $products = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $products[(int)$r['id']] = $r;
    }

    foreach ($wanted as $pid => $qty) {
        if (!isset($products[$pid]) || (int)round($products[$pid]['price']) <= 0) {
            $out['error'] = 'Một số sản phẩm trong giỏ không còn tồn tại hoặc đã đổi giá. Vui lòng làm mới giỏ hàng.';
            return $out;
        }
        $p          = $products[$pid];
        $unit_price = (int)round($p['price']);
        $line_total = $unit_price * $qty;
        $out['subtotal'] += $line_total;
        $out['lines'][] = [
            'product_id' => $pid,
            'name'       => $p['name'],
            'image'      => (string)($p['image_url'] ?? ''),
            'unit_price' => $unit_price,
            'qty'        => $qty,
            'subtotal'   => $line_total,
        ];
    }
    return $out;
}

/** Nhãn ngắn của mã: "Giảm 10% (tối đa 50.000đ)" hoặc "Giảm 30.000đ" */
function coupon_label($c) {
    if ($c['type'] === 'percent') {
        $label = 'Giảm ' . (int)$c['value'] . '%';
        if (!empty($c['max_discount'])) {
            $label .= ' (tối đa ' . number_format((int)$c['max_discount'], 0, ',', '.') . 'đ)';
        }
        return $label;
    }
    return 'Giảm ' . number_format((int)$c['value'], 0, ',', '.') . 'đ';
}

/**
 * Kiểm tra mã giảm giá và tính số tiền giảm — nguồn sự thật duy nhất, dùng cho cả
 * api/validate_coupon.php và api/place_order.php.
 * Trả về ['ok' => bool, 'message' => string, 'discount' => int, 'coupon' => array|null]
 */
function evaluate_coupon($conn, $code, $subtotal, $shipping_fee) {
    $fail = function ($msg) { return ['ok' => false, 'message' => $msg, 'discount' => 0, 'coupon' => null]; };

    $code = strtoupper(trim((string)$code));
    if ($code === '') return $fail('Vui lòng nhập mã giảm giá.');

    $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? LIMIT 1");
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $c = $stmt->get_result()->fetch_assoc();

    if (!$c || !(int)$c['is_active']) return $fail('Mã không hợp lệ hoặc đã hết hạn.');

    $now = time();
    if (!empty($c['start_date']) && strtotime($c['start_date']) > $now) return $fail('Mã chưa đến thời gian áp dụng.');
    if (!empty($c['end_date'])   && strtotime($c['end_date'])   < $now) return $fail('Mã đã hết hạn.');
    if ($c['usage_limit'] !== null && (int)$c['used_count'] >= (int)$c['usage_limit']) return $fail('Mã đã hết lượt sử dụng.');
    if ($subtotal < (int)$c['min_order']) {
        return $fail('Đơn hàng cần tối thiểu ' . number_format((int)$c['min_order'], 0, ',', '.') . 'đ để dùng mã này.');
    }

    if ($c['type'] === 'percent') {
        $discount = (int)round($subtotal * (int)$c['value'] / 100);
        if (!empty($c['max_discount'])) $discount = min($discount, (int)$c['max_discount']);
    } else {
        $discount = (int)$c['value'];
    }
    $discount = max(0, min($discount, $subtotal + $shipping_fee));

    return ['ok' => true, 'message' => 'OK', 'discount' => $discount, 'coupon' => $c];
}

/** Trả lại 1 lượt dùng khi đơn có mã bị hủy */
function release_coupon($conn, $code) {
    $code = strtoupper(trim((string)$code));
    if ($code === '') return;
    $stmt = $conn->prepare("UPDATE coupons SET used_count = IF(used_count > 0, used_count - 1, 0) WHERE code = ?");
    $stmt->bind_param('s', $code);
    $stmt->execute();
}

/** Khách có được đánh giá sản phẩm này không? */
function user_can_review($conn, $user_id, $product_id) {
    $user_id = (int)$user_id;
    $product_id = (int)$product_id;
    if ($user_id <= 0 || $product_id <= 0) return false;
    if (!REVIEW_REQUIRE_PURCHASE) return true;

    $stmt = $conn->prepare("
        SELECT 1
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'
        LIMIT 1
    ");
    $stmt->bind_param('ii', $user_id, $product_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}