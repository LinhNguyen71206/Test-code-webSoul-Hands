<?php
// ============================================================
// search_suggest.php — API gợi ý tìm kiếm (trả JSON)
// Dùng bởi live search dropdown trong header
// ============================================================
ob_start();
require_once 'config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$q = trim($_GET['q'] ?? '');

// Từ khóa dưới 2 ký tự: trả rỗng (mb_strlen để đếm đúng chữ có dấu)
if (mb_strlen($q) < 2) {
    echo json_encode(['products' => []]);
    exit;
}

$kw = '%' . $q . '%';

// Sản phẩm (tối đa 5): tìm trong tên, mô tả hoặc tên danh mục, mới nhất trước
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.price, p.image_url, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?
    ORDER BY p.id DESC
    LIMIT 5
");
$stmt->bind_param('sss', $kw, $kw, $kw);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['products' => $products], JSON_UNESCAPED_UNICODE);