<?php
// ============================================================
// admin/admin_ajax.php — Xử lý tất cả CRUD cho admin panel
// ============================================================
ob_start();
session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Bảo vệ — chỉ admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Đọc JSON body
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

// ============================================================
function resp($success, $message = '', $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}
function clean($v) { return htmlspecialchars(strip_tags(trim($v ?? ''))); }
// ============================================================

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
        $id     = (int)($body['id'] ?? 0);
        $status = $body['status'] ?? '';
        $allowed = ['pending','confirmed','shipping','delivered','cancelled'];
        if (!in_array($status, $allowed)) resp(false, 'Trạng thái không hợp lệ');

        $stmt = $GLOBALS['conn']->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        resp(true, 'Cập nhật trạng thái thành công');
    }

    case 'update_payment_status': {
        $id     = (int)($body['id'] ?? 0);
        $status = $body['payment_status'] ?? '';
        if (!in_array($status, ['unpaid','paid'])) resp(false, 'Không hợp lệ');

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
        $conn = $GLOBALS['conn'];
        $id          = (int)($body['id'] ?? 0);
        $name        = clean($body['name'] ?? '');
        $cat_id      = (int)($body['category_id'] ?? 1);
        $price       = (float)($body['price'] ?? 0);
        $old_price   = $body['old_price'] !== '' ? (float)$body['old_price'] : null;
        $badge       = clean($body['badge'] ?? '');
        $image_url   = clean($body['image_url'] ?? '');
        $description = clean($body['description'] ?? '');
        $style       = clean($body['style'] ?? '');
        $color       = clean($body['color'] ?? '');
        $sub_type    = clean($body['sub_type'] ?? '');
        $image_class = clean($body['image_class'] ?? '');

        if (empty($name)) resp(false, 'Tên sản phẩm không được để trống');
        if ($price <= 0)  resp(false, 'Giá phải lớn hơn 0');

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
    // WORKSHOP
    // ====================

    case 'save_workshop': {
        $conn  = $GLOBALS['conn'];
        $id    = (int)($body['id'] ?? 0);
        $name  = clean($body['name'] ?? '');
        $dur   = clean($body['duration_info'] ?? '');
        $price = (float)($body['price'] ?? 0);

        if (empty($name)) resp(false, 'Tên workshop không được để trống');
        if ($price <= 0)  resp(false, 'Giá phải lớn hơn 0');

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE workshops SET name=?, duration_info=?, price=? WHERE id=?");
            $stmt->bind_param('ssdi', $name, $dur, $price, $id);
            $stmt->execute();
            resp(true, 'Đã cập nhật workshop');
        } else {
            $stmt = $conn->prepare("INSERT INTO workshops (name, duration_info, price) VALUES (?,?,?)");
            $stmt->bind_param('ssd', $name, $dur, $price);
            $stmt->execute();
            resp(true, 'Đã thêm workshop mới', ['new_id' => $conn->insert_id]);
        }
    }

    case 'delete_workshop': {
        $id   = (int)($body['id'] ?? 0);
        $stmt = $GLOBALS['conn']->prepare("DELETE FROM workshops WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        resp(true, 'Đã xóa workshop');
    }

    case 'delete_booking': {
        $id   = (int)($body['id'] ?? 0);
        $stmt = $GLOBALS['conn']->prepare("DELETE FROM workshop_bookings WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        resp(true, 'Đã xóa lịch đặt workshop');
    }

    // ====================
    // WORKSHOP BOOKING
    // ====================

    case 'update_booking_status': {
        $id     = (int)($body['id'] ?? 0);
        $status = $body['status'] ?? '';
        if (!in_array($status, ['pending','confirmed','cancelled'])) resp(false, 'Trạng thái không hợp lệ');

        $stmt = $GLOBALS['conn']->prepare("UPDATE workshop_bookings SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        resp(true, 'Đã cập nhật trạng thái đặt lịch');
    }

    case 'get_booking': {
        $id   = (int)($body['id'] ?? 0);
        $stmt = $GLOBALS['conn']->prepare("
            SELECT wb.*, w.name as ws_name
            FROM workshop_bookings wb
            JOIN workshops w ON wb.workshop_id = w.id
            WHERE wb.id = ? LIMIT 1
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        if (!$booking) resp(false, 'Không tìm thấy lịch đặt');
        resp(true, '', ['booking' => $booking]);
    }

    default:
        http_response_code(400);
        resp(false, 'Action không hợp lệ: ' . $action);
}