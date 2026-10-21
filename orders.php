<?php
ob_start();
session_start();
require_once 'config/database.php';

// Chỉ user đã đăng nhập mới xem được
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ============================================================
// XỬ LÝ HỦY ĐƠN HÀNG (POST + JSON)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $body     = json_decode(file_get_contents('php://input'), true);
    $action   = $body['action']   ?? '';
    $order_id = (int)($body['order_id'] ?? 0);

    if ($action === 'cancel' && $order_id > 0) {
        // Kiểm tra đơn thuộc user này và đang pending
        $chk = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
        $chk->bind_param('ii', $order_id, $user_id);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng.']);
        } elseif ($row['status'] !== 'pending') {
            $msgs = [
                'confirmed' => 'Đơn đã xác nhận, vui lòng liên hệ shop để hủy.',
                'shipping'  => 'Đơn đang được giao, không thể hủy.',
                'delivered' => 'Đơn đã giao thành công, không thể hủy.',
                'cancelled' => 'Đơn hàng đã được hủy trước đó.',
            ];
            echo json_encode(['success' => false, 'message' => $msgs[$row['status']] ?? 'Không thể hủy đơn hàng này.']);
        } else {
            $upd = $conn->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND user_id = ?");
            $upd->bind_param('ii', $order_id, $user_id);
            $upd->execute();
            echo json_encode(['success' => true, 'message' => 'Đơn hàng đã được hủy thành công.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    }
    exit;
}

// Lọc theo trạng thái
$filter_status = $_GET['status'] ?? '';
$allowed_statuses = ['pending','confirmed','shipping','delivered','cancelled'];

$where = "WHERE o.user_id = ?";
$params = [$user_id];
$types  = 'i';

if ($filter_status && in_array($filter_status, $allowed_statuses)) {
    $where  .= " AND o.status = ?";
    $params[] = $filter_status;
    $types   .= 's';
}

// Lấy danh sách đơn hàng
$sql = "SELECT o.*,
               COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        $where
        GROUP BY o.id
        ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Đếm theo từng trạng thái (cho tab filter)
$count_stmt = $conn->prepare("SELECT status, COUNT(*) as cnt FROM orders WHERE user_id = ? GROUP BY status");
$count_stmt->bind_param('i', $user_id);
$count_stmt->execute();
$count_raw = $count_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$status_counts = ['all' => 0];
foreach ($count_raw as $row) {
    $status_counts[$row['status']] = (int)$row['cnt'];
    $status_counts['all'] += (int)$row['cnt'];
}

$status_labels = [
    'pending'   => ['label' => 'Chờ xác nhận', 'color' => '#F59E0B', 'bg' => '#FFFBEB', 'icon' => '⏳'],
    'confirmed' => ['label' => 'Đã xác nhận',  'color' => '#3B82F6', 'bg' => '#EFF6FF', 'icon' => '✅'],
    'shipping'  => ['label' => 'Đang giao',     'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'icon' => '🚚'],
    'delivered' => ['label' => 'Đã giao',       'color' => '#16A34A', 'bg' => '#F0FDF4', 'icon' => '🎉'],
    'cancelled' => ['label' => 'Đã hủy',        'color' => '#DC2626', 'bg' => '#FEF2F2', 'icon' => '✕'],
];

$payment_labels = [
    'cod'  => 'COD',
    'bank' => 'Chuyển khoản',
    'momo' => 'MoMo',
];
?>
<?php include 'includes/header.php'; ?>

<style>
/* ============================================================
   TRANG ĐƠN HÀNG CỦA TÔI
============================================================ */
.orders-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: 48px 24px 80px;
}

/* Breadcrumb */
.orders-breadcrumb {
    font-family: var(--font-mono);
    font-size: 11px;
    color: var(--ink3);
    letter-spacing: 0.5px;
    margin-bottom: 32px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.orders-breadcrumb a { color: var(--ink3); transition: color 0.2s; }
.orders-breadcrumb a:hover { color: var(--rust); }

/* Header */
.orders-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 32px;
}
.orders-title {
    font-family: var(--font-display);
    font-size: 48px;
    font-weight: 500;
    color: var(--ink);
    letter-spacing: -1px;
    line-height: 1;
}
.orders-subtitle {
    font-family: var(--font-menu);
    font-size: 14px;
    color: var(--ink3);
    margin-top: 6px;
}
.orders-header-actions {
    display: flex;
    gap: 10px;
}
.orders-header-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 50px;
    font-family: var(--font-menu);
    font-size: 13px;
    font-weight: 500;
    border: 1.5px solid var(--cream2);
    background: #fff;
    color: var(--ink);
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}
.orders-header-btn:hover { border-color: var(--ink); }

/* Filter tabs */
.orders-tabs {
    display: flex;
    gap: 6px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.orders-tab {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    border-radius: 50px;
    font-family: var(--font-menu);
    font-size: 13px;
    font-weight: 500;
    border: 1.5px solid var(--cream2);
    background: #fff;
    color: var(--ink2);
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}
.orders-tab:hover { border-color: var(--ink); color: var(--ink); }
.orders-tab.active {
    background: var(--ink);
    border-color: var(--ink);
    color: #fff;
}
.tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 5px;
    border-radius: 9px;
    font-size: 11px;
    font-weight: 700;
    background: rgba(255,255,255,0.2);
    color: inherit;
}
.orders-tab:not(.active) .tab-count {
    background: var(--page);
    color: var(--ink3);
}

/* Danh sách đơn */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Card đơn hàng */
.order-card {
    background: #fff;
    border: 1.5px solid var(--cream2);
    border-radius: 16px;
    overflow: hidden;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.order-card:hover {
    border-color: #bbb;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

/* Header card */
.order-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid var(--cream2);
    background: #FAFAF8;
    gap: 16px;
    flex-wrap: wrap;
}
.order-card-code {
    font-family: var(--font-mono);
    font-size: 13px;
    font-weight: 500;
    color: var(--ink);
    letter-spacing: 0.5px;
}
.order-card-date {
    font-family: var(--font-mono);
    font-size: 11px;
    color: var(--ink3);
}
.order-card-head-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Badge trạng thái */
.order-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 20px;
    font-family: var(--font-menu);
    font-size: 12px;
    font-weight: 600;
}

/* Body card */
.order-card-body {
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 20px;
}

/* Ảnh sản phẩm thu nhỏ */
.order-thumbs {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}
.order-thumb {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--cream2);
    flex-shrink: 0;
    background: var(--page);
}
.order-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.order-thumb-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.order-thumb-more {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    border: 1.5px dashed var(--cream2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-mono);
    font-size: 12px;
    color: var(--ink3);
    flex-shrink: 0;
}

/* Thông tin đơn */
.order-card-info {
    flex: 1;
    min-width: 0;
}
.order-item-summary {
    font-family: var(--font-menu);
    font-size: 14px;
    font-weight: 600;
    color: var(--ink);
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.order-item-count {
    font-family: var(--font-menu);
    font-size: 13px;
    color: var(--ink3);
}
.order-payment-method {
    margin-top: 8px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-family: var(--font-mono);
    font-size: 11px;
    color: var(--ink3);
    background: var(--page);
    padding: 3px 10px;
    border-radius: 4px;
}

/* Giá + Action */
.order-card-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 12px;
    flex-shrink: 0;
}
.order-total-label {
    font-family: var(--font-mono);
    font-size: 11px;
    color: var(--ink3);
    text-align: right;
}
.order-total-amount {
    font-family: var(--font-display);
    font-size: 24px;
    font-weight: 600;
    color: var(--rust);
    line-height: 1;
}
.order-detail-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 50px;
    font-family: var(--font-menu);
    font-size: 13px;
    font-weight: 600;
    background: var(--ink);
    color: #fff;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
}
.order-detail-btn:hover { background: var(--rust); }

/* Empty state */
.orders-empty {
    text-align: center;
    padding: 80px 20px;
    background: #fff;
    border: 1.5px dashed var(--cream2);
    border-radius: 16px;
}
.orders-empty-icon { font-size: 56px; margin-bottom: 16px; }
.orders-empty-title {
    font-family: var(--font-display);
    font-size: 32px;
    font-weight: 500;
    color: var(--ink);
    margin-bottom: 8px;
}
.orders-empty-desc {
    font-family: var(--font-menu);
    font-size: 15px;
    color: var(--ink3);
    margin-bottom: 28px;
}
.orders-empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 28px;
    border-radius: 50px;
    background: var(--ink);
    color: #fff;
    font-family: var(--font-menu);
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s;
}
.orders-empty-btn:hover { background: var(--rust); }

/* Nút hủy đơn trên card */
.order-cancel-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 14px;
    border-radius: 50px;
    font-family: var(--font-menu);
    font-size: 12px;
    font-weight: 600;
    border: 1.5px solid #FECACA;
    background: #FEF2F2;
    color: #DC2626;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}
.order-cancel-btn:hover {
    background: #DC2626;
    border-color: #DC2626;
    color: #fff;
}
.order-cancel-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Confirm modal hủy */
.sh-confirm-overlay {
    position: fixed; inset: 0;
    z-index: 99999;
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
}
.sh-confirm-backdrop {
    position: absolute; inset: 0;
    background: rgba(26,28,24,0.65);
    backdrop-filter: blur(5px);
}
.sh-confirm-box {
    position: relative; z-index: 1;
    background: #fff;
    border-radius: 20px;
    padding: 36px 32px 28px;
    max-width: 400px; width: 100%;
    box-shadow: 0 24px 64px rgba(0,0,0,0.18);
    animation: sh-confirm-in .25s cubic-bezier(.34,1.56,.64,1);
}
@keyframes sh-confirm-in {
    from { opacity: 0; transform: scale(.88) translateY(12px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.sh-confirm-icon { font-size: 44px; margin-bottom: 14px; }
.sh-confirm-title {
    font-family: var(--font-display);
    font-size: 24px; font-weight: 500;
    color: var(--ink); margin-bottom: 8px; letter-spacing: -.5px;
}
.sh-confirm-desc {
    font-family: var(--font-menu);
    font-size: 14px; color: var(--ink2);
    line-height: 1.6; margin-bottom: 24px;
}
.sh-confirm-desc strong { color: var(--ink); }
.sh-confirm-btns { display: flex; gap: 10px; }
.sh-confirm-btn {
    flex: 1; display: flex; align-items: center; justify-content: center;
    gap: 6px; padding: 13px 16px; border-radius: 10px;
    font-family: var(--font-menu); font-size: 14px; font-weight: 600;
    cursor: pointer; transition: all 0.2s; border: none;
}
.sh-confirm-btn-cancel {
    background: var(--page);
    border: 1.5px solid var(--cream2);
    color: var(--ink);
}
.sh-confirm-btn-cancel:hover { border-color: var(--ink); }
.sh-confirm-btn-confirm {
    background: #DC2626; color: #fff;
}
.sh-confirm-btn-confirm:hover { background: #B91C1C; }
.sh-confirm-btn-confirm:disabled {
    opacity: 0.6; cursor: not-allowed;
}

/* Toast thông báo */
.sh-toast {
    position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%) translateY(20px);
    z-index: 99999; opacity: 0;
    background: var(--ink); color: #fff;
    font-family: var(--font-menu); font-size: 14px; font-weight: 500;
    padding: 12px 22px; border-radius: 50px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    display: flex; align-items: center; gap: 8px;
    transition: all 0.3s cubic-bezier(.34,1.56,.64,1);
    white-space: nowrap;
}
.sh-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.sh-toast.success { background: #16A34A; }
.sh-toast.error   { background: #DC2626; }

/* ============================================================
   MODAL CHI TIẾT ĐƠN HÀNG
============================================================ */
.order-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
}
.order-modal-overlay.open { display: flex; }

.order-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(44,44,42,0.6);
    backdrop-filter: blur(4px);
}

.order-modal-box {
    position: relative;
    z-index: 1;
    background: #fff;
    border-radius: 20px;
    width: 100%;
    max-width: 680px;
    max-height: 88vh;
    overflow-y: auto;
    box-shadow: 0 32px 80px rgba(0,0,0,0.2);
    animation: modal-in 0.25s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes modal-in {
    from { opacity: 0; transform: scale(0.92) translateY(12px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.order-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 28px 20px;
    border-bottom: 1px solid var(--cream2);
    position: sticky;
    top: 0;
    background: #fff;
    border-radius: 20px 20px 0 0;
    z-index: 2;
}
.order-modal-title {
    font-family: var(--font-display);
    font-size: 24px;
    font-weight: 500;
    color: var(--ink);
    letter-spacing: -0.5px;
}
.order-modal-close {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: var(--page);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ink2);
    transition: all 0.2s;
    flex-shrink: 0;
}
.order-modal-close:hover { background: #e8e5de; color: var(--ink); }

.order-modal-body { padding: 24px 28px 28px; }

/* Sections trong modal */
.modal-section { margin-bottom: 24px; }
.modal-section:last-child { margin-bottom: 0; }
.modal-section-title {
    font-family: var(--font-menu);
    font-size: 12px;
    font-weight: 700;
    color: var(--ink3);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
}

/* Timeline trong modal */
.modal-timeline {
    display: flex;
    align-items: center;
    gap: 0;
    margin-bottom: 6px;
}
.modal-tl-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    flex: 1;
}
.modal-tl-dot {
    width: 34px; height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    border: 2px solid var(--cream2);
    background: #fff;
}
.modal-tl-done .modal-tl-dot  { background: #16A34A; border-color: #16A34A; }
.modal-tl-current .modal-tl-dot { background: var(--rust); border-color: var(--rust); box-shadow: 0 0 0 4px rgba(216,90,48,0.15); }
.modal-tl-label {
    font-family: var(--font-mono);
    font-size: 10px;
    color: var(--ink3);
    text-align: center;
    line-height: 1.3;
}
.modal-tl-done .modal-tl-label { color: #16A34A; }
.modal-tl-current .modal-tl-label { color: var(--rust); font-weight: 700; }
.modal-tl-line {
    flex: 1;
    height: 2px;
    background: var(--cream2);
    margin-bottom: 22px;
    max-width: 60px;
}
.modal-tl-line-done { background: #16A34A; }

/* Items trong modal */
.modal-items { display: flex; flex-direction: column; gap: 0; }
.modal-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid var(--cream2);
}
.modal-item:last-child { border-bottom: none; }
.modal-item-img {
    width: 56px; height: 56px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--cream2);
    flex-shrink: 0;
}
.modal-item-img img { width: 100%; height: 100%; object-fit: cover; }
.modal-item-placeholder {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; background: var(--page);
}
.modal-item-name {
    font-family: var(--font-menu);
    font-size: 14px;
    font-weight: 600;
    color: var(--ink);
    flex: 1;
}
.modal-item-qty {
    font-family: var(--font-mono);
    font-size: 12px;
    color: var(--ink3);
    margin-top: 2px;
}
.modal-item-price {
    font-family: var(--font-menu);
    font-size: 14px;
    font-weight: 700;
    color: var(--ink);
    white-space: nowrap;
}

/* Info grid */
.modal-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.modal-info-box {
    background: var(--page);
    border-radius: 10px;
    padding: 14px 16px;
}
.modal-info-label {
    font-family: var(--font-mono);
    font-size: 10px;
    color: var(--ink3);
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.modal-info-val {
    font-family: var(--font-menu);
    font-size: 14px;
    font-weight: 600;
    color: var(--ink);
    line-height: 1.4;
}
.modal-info-box.full { grid-column: 1 / -1; }

/* Tóm tắt tiền */
.modal-money { display: flex; flex-direction: column; gap: 8px; }
.modal-money-row {
    display: flex;
    justify-content: space-between;
    font-family: var(--font-menu);
    font-size: 14px;
    color: var(--ink2);
}
.modal-money-divider { height: 1px; background: var(--cream2); margin: 4px 0; }
.modal-money-total {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    font-family: var(--font-menu);
    font-size: 15px;
    font-weight: 700;
    color: var(--ink);
}
.modal-money-total-num {
    font-family: var(--font-display);
    font-size: 28px;
    font-weight: 600;
    color: var(--rust);
}
</style>

<main class="orders-page">

    <!-- Breadcrumb -->
    <div class="orders-breadcrumb">
        <a href="index.php">Trang chủ</a>
        <span>›</span>
        <span>Đơn hàng của tôi</span>
    </div>

    <!-- Header -->
    <div class="orders-header">
        <div>
            <h1 class="orders-title">Đơn hàng</h1>
            <p class="orders-subtitle">Xin chào, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></strong> · <?= $status_counts['all'] ?> đơn hàng</p>
        </div>
        <div class="orders-header-actions">
            <a href="index.php" class="orders-header-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                Tiếp tục mua sắm
            </a>
        </div>
    </div>

    <!-- Filter tabs -->
    <div class="orders-tabs">
        <a href="orders.php" class="orders-tab <?= !$filter_status ? 'active' : '' ?>">
            Tất cả
            <span class="tab-count"><?= $status_counts['all'] ?? 0 ?></span>
        </a>
        <?php foreach ($status_labels as $key => $info): ?>
        <a href="orders.php?status=<?= $key ?>" class="orders-tab <?= $filter_status === $key ? 'active' : '' ?>">
            <?= $info['icon'] ?> <?= $info['label'] ?>
            <?php if (!empty($status_counts[$key])): ?>
            <span class="tab-count"><?= $status_counts[$key] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Danh sách đơn hàng -->
    <?php if (empty($orders)): ?>
    <div class="orders-empty">
        <div class="orders-empty-icon">📦</div>
        <h2 class="orders-empty-title">
            <?= $filter_status ? 'Không có đơn nào ở trạng thái này' : 'Chưa có đơn hàng nào' ?>
        </h2>
        <p class="orders-empty-desc">
            <?= $filter_status ? 'Thử xem các tab khác hoặc đặt thêm đơn mới.' : 'Bạn chưa thực hiện đơn hàng nào. Hãy khám phá các sản phẩm thủ công độc đáo!' ?>
        </p>
        <a href="<?= $filter_status ? 'orders.php' : 'index.php' ?>" class="orders-empty-btn">
            <?= $filter_status ? '← Xem tất cả đơn' : 'Khám phá cửa hàng →' ?>
        </a>
    </div>

    <?php else: ?>
    <div class="orders-list">
        <?php foreach ($orders as $order):
            $st = $status_labels[$order['status']] ?? ['label' => $order['status'], 'color' => '#888', 'bg' => '#f5f5f5', 'icon' => '?'];

            // Lấy sản phẩm trong đơn (tối đa 3 ảnh)
            $items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ? LIMIT 4");
            $items_stmt->bind_param('i', $order['id']);
            $items_stmt->execute();
            $order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $thumb_items = array_slice($order_items, 0, 3);
            $extra_count = $order['item_count'] - 3;

            // Tên sản phẩm đầu tiên
            $first_name = $order_items[0]['product_name'] ?? '';
        ?>
        <div class="order-card">
            <!-- Header -->
            <div class="order-card-head">
                <div>
                    <div class="order-card-code"><?= htmlspecialchars($order['order_code']) ?></div>
                    <div class="order-card-date">
                        <?= date('d/m/Y · H:i', strtotime($order['created_at'])) ?>
                    </div>
                </div>
                <div class="order-card-head-right">
                    <div class="order-status-badge"
                         style="color:<?= $st['color'] ?>;background:<?= $st['bg'] ?>;">
                        <?= $st['icon'] ?> <?= $st['label'] ?>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="order-card-body">
                <!-- Thumbnails -->
                <div class="order-thumbs">
                    <?php foreach ($thumb_items as $it): ?>
                    <div class="order-thumb">
                        <?php if (!empty($it['product_image'])): ?>
                            <img src="<?= htmlspecialchars($it['product_image']) ?>" alt="<?= htmlspecialchars($it['product_name']) ?>">
                        <?php else: ?>
                            <div class="order-thumb-placeholder">🏺</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if ($extra_count > 0): ?>
                    <div class="order-thumb-more">+<?= $extra_count ?></div>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="order-card-info">
                    <div class="order-item-summary">
                        <?= htmlspecialchars($first_name) ?>
                        <?php if ($order['item_count'] > 1): ?>
                        <span style="color:var(--ink3);font-weight:400;"> và <?= $order['item_count'] - 1 ?> sản phẩm khác</span>
                        <?php endif; ?>
                    </div>
                    <div class="order-item-count"><?= $order['item_count'] ?> sản phẩm</div>
                    <div class="order-payment-method">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        <?= $payment_labels[$order['payment_method']] ?? $order['payment_method'] ?>
                        <?php if ($order['payment_status'] === 'paid'): ?>
                        · <span style="color:#16A34A;">Đã thanh toán</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Giá + nút -->
                <div class="order-card-right">
                    <div>
                        <div class="order-total-label">Tổng tiền</div>
                        <div class="order-total-amount"><?= number_format($order['total'], 0, ',', '.') ?>đ</div>
                    </div>
                    <button class="order-detail-btn" onclick="openOrderModal(<?= $order['id'] ?>)">
                        Chi tiết
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </button>
                    <?php if ($order['status'] === 'pending'): ?>
                    <button class="order-cancel-btn" onclick="confirmCancelOrder(<?= $order['id'] ?>, '<?= htmlspecialchars($order['order_code']) ?>')">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Hủy đơn
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</main>

<!-- ============================================================
     MODAL CHI TIẾT ĐƠN HÀNG
============================================================ -->
<div class="order-modal-overlay" id="order-modal-overlay">
    <div class="order-modal-backdrop" onclick="closeOrderModal()"></div>
    <div class="order-modal-box" id="order-modal-box">
        <div class="order-modal-header">
            <div class="order-modal-title" id="modal-order-code">Chi tiết đơn hàng</div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div id="order-modal-header-actions"></div>
                <button class="order-modal-close" onclick="closeOrderModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="order-modal-body" id="order-modal-body">
            <!-- Nội dung được inject bằng JS -->
            <div style="text-align:center;padding:40px;color:var(--ink3);">
                <div style="font-size:32px;margin-bottom:12px;">⏳</div>
                Đang tải...
            </div>
        </div>
    </div>
</div>

<!-- Dữ liệu đơn hàng nhúng sẵn (PHP → JS) -->
<script>
const ALL_ORDERS = <?= json_encode(array_column($orders, null, 'id')) ?>;

const STATUS_LABELS = <?= json_encode($status_labels) ?>;
const PAYMENT_LABELS = <?= json_encode($payment_labels) ?>;

// Dữ liệu order_items cho từng đơn (load sẵn)
const ALL_ITEMS = {};
<?php
// Load items cho tất cả đơn hàng để không cần AJAX
foreach ($orders as $order) {
    $oi = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $oi->bind_param('i', $order['id']);
    $oi->execute();
    $oi_data = $oi->get_result()->fetch_all(MYSQLI_ASSOC);
    echo "ALL_ITEMS[" . $order['id'] . "] = " . json_encode($oi_data) . ";\n";
}
?>

// ---- Timeline helper ----
const TL_STEPS = [
    { key: 'pending',   label: 'Chờ xác nhận', icon: '⏳' },
    { key: 'confirmed', label: 'Đã xác nhận',  icon: '✅' },
    { key: 'shipping',  label: 'Đang giao',     icon: '🚚' },
    { key: 'delivered', label: 'Đã giao',       icon: '🎉' },
];

function buildTimeline(currentStatus) {
    if (currentStatus === 'cancelled') {
        return `<div style="display:flex;align-items:center;gap:8px;padding:12px 14px;background:#FEF2F2;border-radius:10px;font-family:var(--font-menu);font-size:13px;color:#DC2626;">
            ✕ &nbsp;Đơn hàng đã bị hủy
        </div>`;
    }

    const currentIdx = TL_STEPS.findIndex(s => s.key === currentStatus);
    let html = '<div class="modal-timeline">';

    TL_STEPS.forEach(function(step, i) {
        const isDone    = i < currentIdx;
        const isCurrent = i === currentIdx;
        const cls = isDone ? 'modal-tl-done' : (isCurrent ? 'modal-tl-current' : '');

        html += `<div class="modal-tl-item ${cls}">
            <div class="modal-tl-dot">${isDone ? '✓' : step.icon}</div>
            <div class="modal-tl-label">${step.label}</div>
        </div>`;

        if (i < TL_STEPS.length - 1) {
            html += `<div class="modal-tl-line ${isDone ? 'modal-tl-line-done' : ''}"></div>`;
        }
    });

    html += '</div>';
    return html;
}

// ---- Mở modal ----
function openOrderModal(orderId) {
    const order = ALL_ORDERS[orderId];
    const items = ALL_ITEMS[orderId] || [];
    if (!order) return;

    const st = STATUS_LABELS[order.status] || { label: order.status, color: '#888', bg: '#f5f5f5', icon: '?' };
    const pm = PAYMENT_LABELS[order.payment_method] || order.payment_method;

    // Format ngày
    const dateStr = order.created_at
        ? new Date(order.created_at.replace(' ', 'T')).toLocaleDateString('vi-VN', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' })
        : '';

    // Format tiền
    const fmt = n => Number(n).toLocaleString('vi-VN') + 'đ';

    // Build items HTML
    let itemsHtml = items.map(function(it) {
        const imgHtml = it.product_image
            ? `<img src="${it.product_image}" alt="${it.product_name}">`
            : `<div class="modal-item-placeholder">🏺</div>`;
        return `<div class="modal-item">
            <div class="modal-item-img">${imgHtml}</div>
            <div style="flex:1;min-width:0;">
                <div class="modal-item-name">${it.product_name}</div>
                <div class="modal-item-qty">x${it.quantity} · ${fmt(it.unit_price)}/sp</div>
            </div>
            <div class="modal-item-price">${fmt(it.subtotal)}</div>
        </div>`;
    }).join('');

    // Build nội dung modal
    document.getElementById('modal-order-code').textContent = order.order_code;

    // Nút hủy trong modal (chỉ khi pending)
    const modalHeader = document.getElementById('order-modal-header-actions');
    if (modalHeader) {
        if (order.status === 'pending') {
            modalHeader.innerHTML = `<button class="order-cancel-btn" style="font-size:13px;padding:8px 18px;" onclick="confirmCancelOrder(${order.id}, '${order.order_code}')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Hủy đơn hàng
            </button>`;
        } else {
            modalHeader.innerHTML = '';
        }
    }

    document.getElementById('order-modal-body').innerHTML = `

        <!-- Timeline -->
        <div class="modal-section">
            <div class="modal-section-title">Trạng thái đơn hàng</div>
            ${buildTimeline(order.status)}
        </div>

        <!-- Sản phẩm -->
        <div class="modal-section">
            <div class="modal-section-title">Sản phẩm (${items.length})</div>
            <div class="modal-items">${itemsHtml}</div>
        </div>

        <!-- Thông tin giao hàng -->
        <div class="modal-section">
            <div class="modal-section-title">Thông tin giao hàng</div>
            <div class="modal-info-grid">
                <div class="modal-info-box">
                    <div class="modal-info-label">Người nhận</div>
                    <div class="modal-info-val">${order.receiver_name}</div>
                </div>
                <div class="modal-info-box">
                    <div class="modal-info-label">Số điện thoại</div>
                    <div class="modal-info-val">${order.receiver_phone}</div>
                </div>
                <div class="modal-info-box full">
                    <div class="modal-info-label">Địa chỉ</div>
                    <div class="modal-info-val">${order.full_address}</div>
                </div>
                ${order.note ? `<div class="modal-info-box full">
                    <div class="modal-info-label">Ghi chú</div>
                    <div class="modal-info-val" style="font-weight:400;color:var(--ink2);">${order.note}</div>
                </div>` : ''}
            </div>
        </div>

        <!-- Tóm tắt thanh toán -->
        <div class="modal-section">
            <div class="modal-section-title">Thanh toán</div>
            <div class="modal-money">
                <div class="modal-money-row">
                    <span>Tạm tính</span>
                    <span>${fmt(order.subtotal)}</span>
                </div>
                <div class="modal-money-row">
                    <span>Phí vận chuyển</span>
                    <span>${Number(order.shipping_fee) === 0 ? '<span style="color:#16A34A;">Miễn phí</span>' : fmt(order.shipping_fee)}</span>
                </div>
                ${Number(order.discount) > 0 ? `<div class="modal-money-row" style="color:#16A34A;">
                    <span>Giảm giá ${order.coupon_code ? '(' + order.coupon_code + ')' : ''}</span>
                    <span>-${fmt(order.discount)}</span>
                </div>` : ''}
                <div class="modal-money-divider"></div>
                <div class="modal-money-total">
                    <span>Tổng cộng</span>
                    <span class="modal-money-total-num">${fmt(order.total)}</span>
                </div>
                <div style="font-family:var(--font-mono);font-size:11px;color:var(--ink3);text-align:right;margin-top:2px;">
                    ${pm} · ${order.payment_status === 'paid' ? '<span style="color:#16A34A;">Đã thanh toán</span>' : 'Chưa thanh toán'}
                </div>
            </div>
        </div>
    `;

    document.getElementById('order-modal-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeOrderModal() {
    document.getElementById('order-modal-overlay').classList.remove('open');
    document.body.style.overflow = '';
}

// Đóng bằng Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeOrderModal();
});

// ============================================================
// HỦY ĐƠN HÀNG — Confirm modal + fetch
// ============================================================
let _cancelOrderId   = null;
let _cancelOrderCode = null;

function confirmCancelOrder(orderId, orderCode) {
    _cancelOrderId   = orderId;
    _cancelOrderCode = orderCode;

    // Tạo modal confirm nếu chưa có
    if (!document.getElementById('sh-confirm-modal')) {
        const el = document.createElement('div');
        el.id = 'sh-confirm-modal';
        el.className = 'sh-confirm-overlay';
        el.innerHTML = `
            <div class="sh-confirm-backdrop" onclick="closeCancelConfirm()"></div>
            <div class="sh-confirm-box">
                <div class="sh-confirm-icon">🗑️</div>
                <div class="sh-confirm-title">Hủy đơn hàng?</div>
                <div class="sh-confirm-desc" id="sh-confirm-desc"></div>
                <div class="sh-confirm-btns">
                    <button class="sh-confirm-btn sh-confirm-btn-cancel" onclick="closeCancelConfirm()">
                        Giữ đơn
                    </button>
                    <button class="sh-confirm-btn sh-confirm-btn-confirm" id="sh-confirm-ok" onclick="doCancel('order')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Xác nhận hủy
                    </button>
                </div>
            </div>`;
        document.body.appendChild(el);
    }

    document.getElementById('sh-confirm-desc').innerHTML =
        `Bạn chắc chắn muốn hủy đơn hàng <strong>${orderCode}</strong>?<br>
        <span style="color:#DC2626;font-size:13px;">Thao tác này không thể hoàn tác.</span>`;
    document.getElementById('sh-confirm-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeCancelConfirm() {
    const m = document.getElementById('sh-confirm-modal');
    if (m) m.style.display = 'none';
    document.body.style.overflow = '';
}

async function doCancel(type) {
    const btn = document.getElementById('sh-confirm-ok');
    btn.disabled = true;
    btn.textContent = 'Đang hủy...';

    try {
        const payload = type === 'order'
            ? { action: 'cancel', order_id: _cancelOrderId }
            : { action: 'cancel', booking_id: _cancelOrderId };

        const endpoint = type === 'order' ? 'orders.php' : 'my-workshops.php';

        const res  = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        closeCancelConfirm();
        closeOrderModal();

        if (data.success) {
            showToast('✓ ' + data.message, 'success');
            setTimeout(() => location.reload(), 1400);
        } else {
            showToast('⚠ ' + data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Xác nhận hủy';
        }
    } catch(e) {
        closeCancelConfirm();
        showToast('⚠ Lỗi kết nối, vui lòng thử lại.', 'error');
        btn.disabled = false;
    }
}

// Toast helper
let _toastTimer;
function showToast(msg, type = '') {
    let toast = document.getElementById('sh-global-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'sh-global-toast';
        toast.className = 'sh-toast';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.className = 'sh-toast' + (type ? ' ' + type : '');
    requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));
    clearTimeout(_toastTimer);
    _toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
}
</script>

<?php include 'includes/footer.php'; ?>