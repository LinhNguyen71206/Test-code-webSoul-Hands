<?php
$host     = 'localhost';
$username = 'root';
$password = '';
$database = 'soul_and_hands';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);

    // Detect JSON request bằng Content-Type hoặc X-Requested-With
    // — không hardcode tên file, hoạt động với mọi AJAX endpoint hiện tại và tương lai
    $is_json_request = (
        (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    );

    if ($is_json_request) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Không thể kết nối database']);
    } else {
        http_response_code(500);
        echo "Kết nối Database thất bại. Vui lòng thử lại sau.";
    }
    exit;
}

$conn->set_charset("utf8mb4");