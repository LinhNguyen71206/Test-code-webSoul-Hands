<?php
// ============================================================
// admin/admin_ajax.php — Xử lý CRUD cho admin panel (đơn hàng, sản phẩm)
// ============================================================
ob_start();
session_start();
require_once '../config/database.php';
require_once '../includes/shop_helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Bảo vệ — chỉ admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Đọc JSON body
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

// ============================================================
function resp($success, $message = '', $extra = []) {
    ob_clean(); // bỏ mọi output rác (warning/notice) trước khi trả JSON
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}
// Lưu dữ liệu thô (bỏ thẻ HTML, cắt khoảng trắng).
// KHÔNG dùng htmlspecialchars ở đây — các trang đã escape khi hiển thị.
function clean($v) { return strip_tags(trim((string)($v ?? ''))); }
// ============================================================

// Chuyển 'YYYY-MM-DD' thành DATETIME. '' → null, sai định dạng → false
function coupon_date($s, $end_of_day) {
    $s = trim((string)$s);
    if ($s === '') return null;
    $d = DateTime::createFromFormat('Y-m-d', $s);
    if (!$d || $d->format('Y-m-d') !== $s) return false;
    return $s . ($end_of_day ? ' 23:59:59' : ' 00:00:00');
}

switch ($action) {

    // ====================
    // ĐƠN HÀNG
    // ====================

    case 'get_order': {
        $id   = (int)($body['id'] ?? 0);
        $stmt = $GLOBALS['conn']->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        if (!$order) resp(false, 'Không tìm thấy đơn hàng');

        $i_stmt = $GLOBALS['conn']->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $i_stmt->bind_param('i', $id);
        $i_stmt->execute();
        $items = $i_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        resp(true, '', ['order' => $order, 'items' => $items]);
    }

    case 'update_order_status': {
        $id      = (int)($body['id'] ?? 0);
        $status  = $body['status'] ?? '';
        $allowed = ['pending','confirmed','shipping','delivered','cancelled'];
        if (!in_array($status, $allowed, true)) resp(false, 'Trạng thái không hợp lệ');

        // Đơn đã giao thành công hoặc đã hủy là trạng thái cuối — không cho đổi nữa
        $cur_stmt = $GLOBALS['conn']->prepare("SELECT status, coupon_code FROM orders WHERE id = ? LIMIT 1");
        $cur_stmt->bind_param('i', $id);
        $cur_stmt->execute();
        $cur = $cur_stmt->get_result()->fetch_assoc();
        if (!$cur) resp(false, 'Không tìm thấy đơn hàng');
        if (in_array($cur['status'], ['delivered', 'cancelled'], true)) {
            resp(false, 'Đơn hàng đã ' . ($cur['status'] === 'delivered' ? 'giao thành công' : 'bị hủy') . ', không thể cập nhật trạng thái nữa');
        }

        // Điều kiện status trong WHERE chặn trường hợp đơn vừa được đổi sang trạng thái cuối ở nơi khác
        $stmt = $GLOBALS['conn']->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND status NOT IN ('delivered','cancelled')");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0 && $status === 'cancelled' && !empty($cur['coupon_code'])) {
            release_coupon($GLOBALS['conn'], $cur['coupon_code']); // trả lại 1 lượt dùng mã
        }
        resp(true, 'Cập nhật trạng thái thành công');
    }

    case 'update_payment_status': {
        $id     = (int)($body['id'] ?? 0);
        $status = $body['payment_status'] ?? '';
        if (!in_array($status, ['unpaid','paid'], true)) resp(false, 'Không hợp lệ');

        $stmt = $GLOBALS['conn']->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        resp(true, 'Cập nhật thanh toán thành công');
    }

    case 'delete_order': {
        $id   = (int)($body['id'] ?? 0);
        $stmt = $GLOBALS['conn']->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        resp(true, 'Đã xóa đơn hàng');
    }

    // ====================
    // SẢN PHẨM
    // ====================

    case 'save_product': {
        $conn        = $GLOBALS['conn'];
        $id          = (int)($body['id'] ?? 0);
        $name        = clean($body['name'] ?? '');
        $cat_id      = (int)($body['category_id'] ?? 1);
        $price       = (float)($body['price'] ?? 0);
        $old_raw     = $body['old_price'] ?? '';
        $old_price   = ($old_raw !== '' && $old_raw !== null) ? (float)$old_raw : null;
        $badge       = clean($body['badge'] ?? '');
        $image_url   = clean($body['image_url'] ?? '');
        $description = clean($body['description'] ?? '');
        $style       = clean($body['style'] ?? '');
        $color       = clean($body['color'] ?? '');
        $sub_type    = clean($body['sub_type'] ?? '');
        $image_class = clean($body['image_class'] ?? '');

        if (empty($name)) resp(false, 'Tên sản phẩm không được để trống');
        if ($price <= 0)  resp(false, 'Giá phải lớn hơn 0');
        if ($cat_id < 1 || $cat_id > 4) resp(false, 'Danh mục không hợp lệ');
        if ($old_price !== null && $old_price <= $price) resp(false, 'Giá cũ phải lớn hơn giá bán (hoặc để trống nếu không giảm giá)');

        if ($id > 0) {
            // UPDATE
            $stmt = $conn->prepare("
                UPDATE products SET 
                    name=?, category_id=?, price=?, old_price=?, badge=?,
                    image_url=?, description=?, style=?, color=?, sub_type=?, image_class=?
                WHERE id=?
            ");
            $stmt->bind_param('siidsssssssi',
                $name, $cat_id, $price, $old_price, $badge,
                $image_url, $description, $style, $color, $sub_type, $image_class,
                $id
            );
            $stmt->execute();
            resp(true, 'Đã cập nhật sản phẩm');
        } else {
            // INSERT
            $stmt = $conn->prepare("
                INSERT INTO products 
                    (name, category_id, price, old_price, badge, image_url, description, style, color, sub_type, image_class)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('siidsssssss',
                $name, $cat_id, $price, $old_price, $badge,
                $image_url, $description, $style, $color, $sub_type, $image_class
            );
            $stmt->execute();
            resp(true, 'Đã thêm sản phẩm mới', ['new_id' => $conn->insert_id]);
        }
    }

    case 'delete_product': {
        $id   = (int)($body['id'] ?? 0);
        $stmt = $GLOBALS['conn']->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        resp(true, 'Đã xóa sản phẩm');
    }

    // ====================
    // MÃ GIẢM GIÁ
    // ====================

    case 'save_coupon': {
        $conn  = $GLOBALS['conn'];
        $id    = (int)($body['id'] ?? 0);
        $code  = strtoupper(trim((string)($body['code'] ?? '')));
        $desc  = clean($body['description'] ?? '');
        $type  = $body['type'] ?? '';
        $value = (int)($body['value'] ?? 0);
        $min_order = max(0, (int)($body['min_order'] ?? 0));

        $max_raw      = $body['max_discount'] ?? '';
        $max_discount = ($max_raw !== '' && $max_raw !== null) ? (int)$max_raw : null;
        $lim_raw      = $body['usage_limit'] ?? '';
        $usage_limit  = ($lim_raw !== '' && $lim_raw !== null) ? (int)$lim_raw : null;

        $start = coupon_date($body['start_date'] ?? '', false);
        $end   = coupon_date($body['end_date'] ?? '', true);
        $active = !empty($body['is_active']) ? 1 : 0;

        if (!in_array($type, ['percent', 'fixed'], true)) resp(false, 'Loại giảm giá không hợp lệ');
        if ($value <= 0) resp(false, 'Giá trị giảm phải lớn hơn 0');
        if ($type === 'percent' && $value > 100) resp(false, 'Giảm theo % không được vượt quá 100');
        if ($type === 'fixed') $max_discount = null;           // trần chỉ dùng cho giảm theo %
        if ($max_discount !== null && $max_discount <= 0) $max_discount = null;
        if ($usage_limit !== null && $usage_limit < 1) resp(false, 'Giới hạn lượt dùng phải từ 1 trở lên (hoặc để trống)');
        if ($start === false || $end === false) resp(false, 'Ngày không hợp lệ');
        if ($start !== null && $end !== null && strtotime($end) < strtotime($start)) resp(false, 'Ngày kết thúc phải sau ngày bắt đầu');

        if ($id > 0) {
            // SỬA (không đổi mã vì các đơn cũ đã lưu theo mã này)
            $cs = $conn->prepare("SELECT used_count FROM coupons WHERE id = ? LIMIT 1");
            $cs->bind_param('i', $id);
            $cs->execute();
            $cur = $cs->get_result()->fetch_assoc();
            if (!$cur) resp(false, 'Không tìm thấy mã giảm giá');
            if ($usage_limit !== null && $usage_limit < (int)$cur['used_count']) {
                resp(false, 'Giới hạn không được nhỏ hơn số lượt đã dùng (' . (int)$cur['used_count'] . ')');
            }

            $stmt = $conn->prepare("
                UPDATE coupons SET description=?, type=?, value=?, min_order=?, max_discount=?,
                       usage_limit=?, start_date=?, end_date=?, is_active=?
                WHERE id=?
            ");
            $stmt->bind_param('ssiiiissii', $desc, $type, $value, $min_order, $max_discount,
                              $usage_limit, $start, $end, $active, $id);
            $stmt->execute();
            resp(true, 'Đã cập nhật mã giảm giá');
        } else {
            // THÊM MỚI
            if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $code)) {
                resp(false, 'Mã chỉ gồm chữ HOA không dấu, số, gạch ngang/gạch dưới (3–30 ký tự)');
            }
            $dup = $conn->prepare("SELECT 1 FROM coupons WHERE code = ? LIMIT 1");
            $dup->bind_param('s', $code);
            $dup->execute();
            if ($dup->get_result()->num_rows > 0) resp(false, 'Mã này đã tồn tại');

            $stmt = $conn->prepare("
                INSERT INTO coupons (code, description, type, value, min_order, max_discount, usage_limit, start_date, end_date, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('sssiiiissi', $code, $desc, $type, $value, $min_order, $max_discount,
                              $usage_limit, $start, $end, $active);
            $stmt->execute();
            resp(true, 'Đã thêm mã giảm giá', ['new_id' => $conn->insert_id]);
        }
    }

    case 'toggle_coupon': {
        $id   = (int)($body['id'] ?? 0);
        $stmt = $GLOBALS['conn']->prepare("UPDATE coupons SET is_active = 1 - is_active WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        resp(true, 'Đã đổi trạng thái mã giảm giá');
    }

    case 'delete_coupon': {
        $id   = (int)($body['id'] ?? 0);
        $stmt = $GLOBALS['conn']->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        resp(true, 'Đã xóa mã giảm giá');
    }

    // ====================
    // ĐÁNH GIÁ SẢN PHẨM
    // ====================

    case 'set_review_status': {
        $id     = (int)($body['id'] ?? 0);
        $status = $body['status'] ?? '';
        if (!in_array($status, ['visible', 'hidden'], true)) resp(false, 'Trạng thái không hợp lệ');
        $stmt = $GLOBALS['conn']->prepare("UPDATE reviews SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        resp(true, $status === 'hidden' ? 'Đã ẩn đánh giá' : 'Đã hiện đánh giá');
    }

    case 'delete_review': {
        $id   = (int)($body['id'] ?? 0);
        $stmt = $GLOBALS['conn']->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        resp(true, 'Đã xóa đánh giá');
    }

    default:
        http_response_code(400);
        resp(false, 'Action không hợp lệ: ' . $action);
}