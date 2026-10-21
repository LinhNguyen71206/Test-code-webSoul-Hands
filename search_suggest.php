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

if (mb_strlen($q) < 2) { //đếm số ký tự, dùng multibyte để hỗ trợ Unicode
    echo json_encode(['products' => [], 'workshops' => []]); //trả rỗng nếu query quá ngắn dưới 2 ký tự
    exit;
}

$kw = '%' . $q . '%'; // đều có thể tìm trong tên, mô tả, hoặc danh mục (đối với sản phẩm) 

// ── Sản phẩm (tối đa 5) ──────────────────────────────────────//tìm trong tên, mô tả, hoặc tên danh mục, ưu tiên sản phẩm mới nhất (id DESC)
$products = [];
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.price, p.image_url, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ? 
    ORDER BY p.id DESC
    LIMIT 5
");
$stmt->bind_param('sss', $kw, $kw, $kw); //
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Workshop (tối đa 3) ───────────────────────────────────────//tìm trong tên hoặc thông tin thời lượng, ưu tiên workshop mới nhất (id DESC)
$workshops = [];
$stmt = $conn->prepare("
    SELECT id, name, price, duration_info
    FROM workshops
    WHERE name LIKE ? OR duration_info LIKE ?
    ORDER BY id DESC
    LIMIT 3
");
$stmt->bind_param('ss', $kw, $kw);
$stmt->execute();
$workshops = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'products'  => $products,
    'workshops' => $workshops,
], JSON_UNESCAPED_UNICODE);