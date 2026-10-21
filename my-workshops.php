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
// XỬ LÝ HỦY ĐẶT LỊCH WORKSHOP (POST + JSON)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $body       = json_decode(file_get_contents('php://input'), true);
    $action     = $body['action']     ?? '';
    $booking_id = (int)($body['booking_id'] ?? 0);

    if ($action === 'cancel' && $booking_id > 0) {
        $chk = $conn->prepare("SELECT id, status FROM workshop_bookings WHERE id = ? AND user_id = ? LIMIT 1");
        $chk->bind_param('ii', $booking_id, $user_id);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy lịch đặt.']);
        } elseif ($row['status'] !== 'pending') {
            $msgs = [
                'confirmed' => 'Lịch đã được xác nhận, vui lòng liên hệ shop để hủy.',
                'cancelled' => 'Lịch đặt này đã được hủy trước đó.',
            ];
            echo json_encode(['success' => false, 'message' => $msgs[$row['status']] ?? 'Không thể hủy lịch đặt này.']);
        } else {
            $upd = $conn->prepare("UPDATE workshop_bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?");
            $upd->bind_param('ii', $booking_id, $user_id);
            $upd->execute();
            echo json_encode(['success' => true, 'message' => 'Lịch đặt workshop đã được hủy thành công.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    }
    exit;
}

// Lọc theo trạng thái
$filter_status = $_GET['status'] ?? '';
$allowed_statuses = ['pending', 'confirmed', 'cancelled'];

// Lấy danh sách lịch đặt workshop của user theo user_id
$where  = "WHERE wb.user_id = ?";
$params = [$user_id];
$types  = 'i';

if ($filter_status && in_array($filter_status, $allowed_statuses)) {
    $where  .= " AND wb.status = ?";
    $params[] = $filter_status;
    $types   .= 's';
}

$sql = "SELECT wb.*, w.name as ws_name, w.price as ws_price, w.duration_info
        FROM workshop_bookings wb
        JOIN workshops w ON wb.workshop_id = w.id
        $where
        ORDER BY wb.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Đếm theo trạng thái cho tab
$count_sql   = "SELECT status, COUNT(*) as cnt FROM workshop_bookings WHERE user_id = ? GROUP BY status";
$count_stmt  = $conn->prepare($count_sql);
$count_stmt->bind_param('i', $user_id);
$count_stmt->execute();
$count_raw    = $count_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$status_counts = ['all' => 0];
foreach ($count_raw as $row) {
    $status_counts[$row['status']] = (int)$row['cnt'];
    $status_counts['all'] += (int)$row['cnt'];
}

$status_labels = [
    'pending'   => ['label' => 'Chờ xác nhận', 'color' => '#F59E0B', 'bg' => '#FFFBEB', 'icon' => '⏳'],
    'confirmed' => ['label' => 'Đã xác nhận',  'color' => '#16A34A', 'bg' => '#F0FDF4', 'icon' => '✅'],
    'cancelled' => ['label' => 'Đã hủy',        'color' => '#DC2626', 'bg' => '#FEF2F2', 'icon' => '✕'],
];

// Ảnh fallback cho từng workshop
$ws_imgs = [
    1 => 'https://sf-static.upanhlaylink.com/img/image_20260510a80aac45cbed8b6d1060d0f71f954605.jpg',
    2 => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?q=80&w=900&auto=format&fit=crop',
    3 => 'https://sf-static.upanhlaylink.com/img/image_202605104bd5e177a488151c41e99481bd8069a9.jpg',
];
?>
<?php include 'includes/header.php'; ?>

<style>
/* ============================================================
   TRANG LỊCH WORKSHOP CỦA TÔI
============================================================ */
.myw-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: 48px 24px 80px;
}

/* Breadcrumb */
.myw-breadcrumb {
    font-family: var(--font-mono);
    font-size: 11px;
    color: var(--ink3);
    letter-spacing: 0.5px;
    margin-bottom: 32px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.myw-breadcrumb a { color: var(--ink3); transition: color 0.2s; }
.myw-breadcrumb a:hover { color: var(--rust); }

/* Header */
.myw-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 32px;
    flex-wrap: wrap;
    gap: 16px;
}
.myw-title {
    font-family: var(--font-display);
    font-size: 48px;
    font-weight: 500;
    color: var(--ink);
    letter-spacing: -1px;
    line-height: 1;
}
.myw-subtitle {
    font-family: var(--font-menu);
    font-size: 14px;
    color: var(--ink3);
    margin-top: 6px;
}
.myw-header-actions {
    display: flex;
    gap: 10px;
}
.myw-header-btn {
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
.myw-header-btn:hover { border-color: var(--ink); }
.myw-header-btn-primary {
    background: var(--rust);
    border-color: var(--rust);
    color: #fff;
}
.myw-header-btn-primary:hover { background: #B84A20; border-color: #B84A20; }

/* Filter tabs */
.myw-tabs {
    display: flex;
    gap: 6px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}
.myw-tab {
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
.myw-tab:hover { border-color: var(--ink); color: var(--ink); }
.myw-tab.active {
    background: var(--ink);
    border-color: var(--ink);
    color: #fff;
}
.myw-tab-count {
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
.myw-tab:not(.active) .myw-tab-count {
    background: var(--page);
    color: var(--ink3);
}

/* Danh sách */
.myw-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Card lịch workshop */
.myw-card {
    background: #fff;
    border: 1.5px solid var(--cream2);
    border-radius: 16px;
    overflow: hidden;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.myw-card:hover {
    border-color: #bbb;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

/* Header card */
.myw-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    border-bottom: 1px solid var(--cream2);
    background: #FAFAF8;
    gap: 16px;
    flex-wrap: wrap;
}
.myw-card-id {
    font-family: var(--font-mono);
    font-size: 12px;
    color: var(--ink3);
    letter-spacing: 0.5px;
}
.myw-card-date {
    font-family: var(--font-mono);
    font-size: 11px;
    color: var(--ink3);
}
.myw-card-head-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Badge trạng thái */
.myw-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 14px;
    border-radius: 50px;
    font-family: var(--font-menu);
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

/* Body card */
.myw-card-body {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 20px;
    padding: 20px 24px;
    align-items: center;
}

/* Ảnh workshop nhỏ */
.myw-card-img-wrap {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
}
.myw-card-img-wrap img {
    width: 100%; height: 100%;
    object-fit: cover;
}
.myw-card-img-placeholder {
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #99A98F 0%, #7C8B72 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

/* Info */
.myw-card-info { min-width: 0; }
.myw-card-ws-name {
    font-family: var(--font-display);
    font-size: 20px;
    font-weight: 500;
    color: var(--ink);
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.myw-card-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}
.myw-card-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-family: var(--font-menu);
    font-size: 13px;
    color: var(--ink2);
}
.myw-card-meta-item svg { opacity: 0.5; flex-shrink: 0; }
.myw-card-note {
    margin-top: 8px;
    font-family: var(--font-menu);
    font-size: 12px;
    color: var(--ink3);
    font-style: italic;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 400px;
}

/* Phải: giá + nút */
.myw-card-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
    flex-shrink: 0;
}
.myw-total-label {
    font-family: var(--font-mono);
    font-size: 10px;
    color: var(--ink3);
    text-transform: uppercase;
    letter-spacing: 1px;
}
.myw-total-amount {
    font-family: var(--font-display);
    font-size: 22px;
    font-weight: 600;
    color: var(--ink);
    letter-spacing: -0.5px;
}
.myw-detail-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 50px;
    border: 1.5px solid var(--cream2);
    background: #fff;
    font-family: var(--font-menu);
    font-size: 13px;
    font-weight: 500;
    color: var(--ink);
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}
.myw-detail-btn:hover {
    border-color: var(--ink);
    background: var(--ink);
    color: #fff;
}

/* Empty state */
.myw-empty {
    text-align: center;
    padding: 80px 20px;
    background: #fff;
    border: 1.5px dashed var(--cream2);
    border-radius: 20px;
}
.myw-empty-icon { font-size: 52px; margin-bottom: 16px; }
.myw-empty-title {
    font-family: var(--font-display);
    font-size: 28px;
    font-weight: 500;
    color: var(--ink);
    margin-bottom: 8px;
}
.myw-empty-desc {
    font-family: var(--font-menu);
    font-size: 15px;
    color: var(--ink2);
    margin-bottom: 28px;
    line-height: 1.6;
}

/* ============================================================
   MODAL CHI TIẾT
============================================================ */
.myw-modal-overlay {
    position: fixed; inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
}
.myw-modal-overlay.open { display: flex; }

.myw-modal-backdrop {
    position: absolute; inset: 0;
    background: rgba(26,28,24,0.6);
    backdrop-filter: blur(6px);
}

.myw-modal-box {
    position: relative; z-index: 1;
    background: #fff;
    border-radius: 20px;
    width: 90%;
    max-width: 560px;
    max-height: 88vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 32px 80px rgba(0,0,0,0.2);
    animation: myw-in 0.28s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes myw-in {
    from { opacity: 0; transform: scale(0.9) translateY(16px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.myw-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--cream2);
    flex-shrink: 0;
}
.myw-modal-title {
    font-family: var(--font-mono);
    font-size: 12px;
    font-weight: 500;
    color: var(--ink3);
    letter-spacing: 1.5px;
    text-transform: uppercase;
}
.myw-modal-close {
    width: 32px; height: 32px;
    border-radius: 50%;
    border: none; background: none;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    color: var(--ink3);
    transition: all 0.2s;
}
.myw-modal-close:hover { background: #F3F4F6; color: var(--ink); }

.myw-modal-body {
    overflow-y: auto;
    padding: 24px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* Banner workshop trong modal */
.myw-modal-banner {
    border-radius: 14px;
    overflow: hidden;
    height: 160px;
    position: relative;
}
.myw-modal-banner img {
    width: 100%; height: 100%;
    object-fit: cover;
}
.myw-modal-banner-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(26,28,24,0.75) 0%, transparent 50%);
}
.myw-modal-banner-content {
    position: absolute;
    bottom: 16px; left: 20px; right: 20px;
}
.myw-modal-ws-name {
    font-family: var(--font-display);
    font-size: 22px;
    font-weight: 500;
    color: #fff;
    margin-bottom: 4px;
}
.myw-modal-ws-dur {
    font-family: var(--font-menu);
    font-size: 13px;
    color: rgba(255,255,255,0.75);
}

/* Section trong modal */
.myw-modal-section { display: flex; flex-direction: column; gap: 12px; }
.myw-modal-section-title {
    font-family: var(--font-mono);
    font-size: 10px;
    font-weight: 600;
    color: var(--ink3);
    letter-spacing: 2px;
    text-transform: uppercase;
}

/* Info grid */
.myw-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.myw-info-box {
    background: var(--page);
    border-radius: 10px;
    padding: 12px 14px;
}
.myw-info-box.full { grid-column: 1 / -1; }
.myw-info-label {
    font-family: var(--font-mono);
    font-size: 10px;
    color: var(--ink3);
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.myw-info-val {
    font-family: var(--font-menu);
    font-size: 14px;
    font-weight: 600;
    color: var(--ink);
    line-height: 1.4;
}

/* Tổng tiền trong modal */
.myw-modal-money {
    background: var(--ink);
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.myw-modal-money-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: var(--font-menu);
    font-size: 14px;
    color: rgba(255,255,255,0.65);
}
.myw-modal-money-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: var(--font-menu);
    font-size: 15px;
    font-weight: 700;
    color: #fff;
    padding-top: 8px;
    border-top: 1px solid rgba(255,255,255,0.12);
    margin-top: 2px;
}
.myw-modal-money-total-num {
    font-family: var(--font-display);
    font-size: 20px;
    color: #F9C846;
    letter-spacing: -0.5px;
}

/* Nút đặt thêm trong modal */
.myw-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--cream2);
    display: flex;
    gap: 10px;
    flex-shrink: 0;
}
.myw-modal-footer-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 10px;
    font-family: var(--font-menu);
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
}
.myw-modal-footer-btn-outline {
    border: 1.5px solid var(--cream2);
    background: #fff;
    color: var(--ink);
}
.myw-modal-footer-btn-outline:hover { border-color: var(--ink); }
.myw-modal-footer-btn-primary {
    background: var(--rust);
    color: #fff;
}
.myw-modal-footer-btn-primary:hover { background: #B84A20; }

/* Nút hủy lịch trên card */
.myw-cancel-btn {
    display: flex;
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
.myw-cancel-btn:hover {
    background: #DC2626;
    border-color: #DC2626;
    color: #fff;
}

/* Confirm modal */
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
    background: #fff; border-radius: 20px;
    padding: 36px 32px 28px;
    max-width: 400px; width: 100%;
    box-shadow: 0 24px 64px rgba(0,0,0,0.18);
    animation: sh-confirm-in .25s cubic-bezier(.34,1.56,.64,1);
}
@keyframes sh-confirm-in {
    from { opacity:0; transform:scale(.88) translateY(12px); }
    to   { opacity:1; transform:scale(1) translateY(0); }
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
.sh-confirm-btn-cancel { background: var(--page); border: 1.5px solid var(--cream2); color: var(--ink); }
.sh-confirm-btn-cancel:hover { border-color: var(--ink); }
.sh-confirm-btn-confirm { background: #DC2626; color: #fff; }
.sh-confirm-btn-confirm:hover { background: #B91C1C; }
.sh-confirm-btn-confirm:disabled { opacity: .6; cursor: not-allowed; }

/* Toast */
.sh-toast {
    position: fixed; bottom: 28px; left: 50%;
    transform: translateX(-50%) translateY(20px);
    z-index: 99999; opacity: 0;
    background: var(--ink); color: #fff;
    font-family: var(--font-menu); font-size: 14px; font-weight: 500;
    padding: 12px 22px; border-radius: 50px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    display: flex; align-items: center; gap: 8px;
    transition: all .3s cubic-bezier(.34,1.56,.64,1);
    white-space: nowrap;
}
.sh-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.sh-toast.success { background: #16A34A; }
.sh-toast.error   { background: #DC2626; }
</style>

<main class="myw-page">

    <!-- Breadcrumb -->
    <nav class="myw-breadcrumb" aria-label="Breadcrumb">
        <a href="index.php">Trang chủ</a>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m9 18 6-6-6-6"/></svg>
        <span>Lịch workshop của tôi</span>
    </nav>

    <!-- Header -->
    <div class="myw-header">
        <div>
            <h1 class="myw-title">Lịch Workshop</h1>
            <p class="myw-subtitle">Tổng <?= $status_counts['all'] ?> lượt đặt lịch</p>
        </div>
        <div class="myw-header-actions">
            <a href="orders.php" class="myw-header-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Đơn hàng của tôi
            </a>
            <a href="workshop.php" class="myw-header-btn myw-header-btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Đặt lịch mới
            </a>
        </div>
    </div>

    <!-- Filter tabs -->
    <div class="myw-tabs">
        <a href="my-workshops.php"
           class="myw-tab <?= !$filter_status ? 'active' : '' ?>">
            Tất cả
            <span class="myw-tab-count"><?= $status_counts['all'] ?></span>
        </a>
        <?php
        $tab_defs = [
            'pending'   => ['label' => 'Chờ xác nhận', 'icon' => '⏳'],
            'confirmed' => ['label' => 'Đã xác nhận',  'icon' => '✅'],
            'cancelled' => ['label' => 'Đã hủy',        'icon' => '✕'],
        ];
        foreach ($tab_defs as $key => $def):
            $cnt = $status_counts[$key] ?? 0;
        ?>
        <a href="my-workshops.php?status=<?= $key ?>"
           class="myw-tab <?= $filter_status === $key ? 'active' : '' ?>">
            <?= $def['icon'] ?> <?= $def['label'] ?>
            <?php if ($cnt > 0): ?>
            <span class="myw-tab-count"><?= $cnt ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Danh sách -->
    <?php if (empty($bookings)): ?>
    <div class="myw-empty">
        <div class="myw-empty-icon">🏺</div>
        <h2 class="myw-empty-title">
            <?= $filter_status ? 'Không có lịch nào' : 'Chưa có lịch đặt nào' ?>
        </h2>
        <p class="myw-empty-desc">
            <?= $filter_status
                ? 'Không tìm thấy lịch workshop ở trạng thái này.'
                : 'Bạn chưa đặt lịch workshop nào. Hãy thử trải nghiệm làm gốm cùng nghệ nhân!' ?>
        </p>
        <a href="workshop.php" class="btn-primary">
            Xem các workshop →
        </a>
    </div>

    <?php else: ?>

    <div class="myw-list">
        <?php foreach ($bookings as $b):
            $st     = $status_labels[$b['status']] ?? ['label' => $b['status'], 'color' => '#888', 'bg' => '#f5f5f5', 'icon' => '?'];
            $ws_id  = (int)$b['workshop_id'];
            $img    = $ws_imgs[$ws_id] ?? $ws_imgs[1];
            $total  = (int)$b['ws_price'] * (int)$b['participants'];
            $bdate  = date('d/m/Y', strtotime($b['booking_date']));
            $cdate  = date('d/m/Y', strtotime($b['created_at']));
        ?>
        <div class="myw-card">
            <!-- Head -->
            <div class="myw-card-head">
                <div>
                    <div class="myw-card-id">#WS-<?= str_pad($b['id'], 5, '0', STR_PAD_LEFT) ?></div>
                    <div class="myw-card-date">Đặt ngày <?= $cdate ?></div>
                </div>
                <div class="myw-card-head-right">
                    <span class="myw-status-badge"
                          style="color:<?= $st['color'] ?>;background:<?= $st['bg'] ?>;">
                        <?= $st['icon'] ?> <?= $st['label'] ?>
                    </span>
                </div>
            </div>

            <!-- Body -->
            <div class="myw-card-body">
                <!-- Ảnh nhỏ -->
                <div class="myw-card-img-wrap">
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($b['ws_name']) ?>">
                </div>

                <!-- Info -->
                <div class="myw-card-info">
                    <div class="myw-card-ws-name"><?= htmlspecialchars($b['ws_name']) ?></div>
                    <div class="myw-card-meta">
                        <div class="myw-card-meta-item">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <?= $bdate ?>
                        </div>
                        <div class="myw-card-meta-item">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <?= $b['participants'] ?> người
                        </div>
                        <div class="myw-card-meta-item">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?= htmlspecialchars($b['duration_info']) ?>
                        </div>
                    </div>
                    <?php if (!empty($b['notes'])): ?>
                    <div class="myw-card-note">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="display:inline;vertical-align:middle;margin-right:3px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <?= htmlspecialchars($b['notes']) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Giá + nút -->
                <div class="myw-card-right">
                    <div style="text-align:right;">
                        <div class="myw-total-label">Tổng tiền</div>
                        <div class="myw-total-amount"><?= number_format($total, 0, ',', '.') ?>đ</div>
                    </div>
                    <button class="myw-detail-btn"
                            onclick="openBookingModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>, '<?= $img ?>', <?= $total ?>)">
                        Chi tiết
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </button>
                    <?php if ($b['status'] === 'pending'): ?>
                    <button class="myw-cancel-btn"
                            onclick="confirmCancelWs(<?= $b['id'] ?>, '<?= htmlspecialchars($b['ws_name'], ENT_QUOTES) ?>', '<?= $bdate ?>')">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Hủy lịch
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
     MODAL CHI TIẾT LỊCH WORKSHOP
============================================================ -->
<div class="myw-modal-overlay" id="myw-modal-overlay">
    <div class="myw-modal-backdrop" onclick="closeBookingModal()"></div>
    <div class="myw-modal-box" id="myw-modal-box">
        <div class="myw-modal-header">
            <div class="myw-modal-title" id="myw-modal-title">Chi tiết lịch workshop</div>
            <button class="myw-modal-close" onclick="closeBookingModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="myw-modal-body" id="myw-modal-body">
            <div style="text-align:center;padding:40px;color:var(--ink3);">
                <div style="font-size:32px;margin-bottom:12px;">⏳</div>
                Đang tải...
            </div>
        </div>
        <div class="myw-modal-footer" id="myw-modal-footer">
            <a href="workshop.php" class="myw-modal-footer-btn myw-modal-footer-btn-outline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Đặt lịch mới
            </a>
            <a href="workshop.php" class="myw-modal-footer-btn myw-modal-footer-btn-primary" id="myw-modal-rebook-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/></svg>
                Đặt lại workshop này
            </a>
        </div>
    </div>
</div>

<script>
const STATUS_LABELS_WS = <?= json_encode($status_labels) ?>;

function fmt(n) {
    return Number(n).toLocaleString('vi-VN') + 'đ';
}

function openBookingModal(b, imgSrc, total) {
    const st = STATUS_LABELS_WS[b.status] || { label: b.status, color: '#888', bg: '#f5f5f5', icon: '?' };

    const bookingDate = b.booking_date
        ? new Date(b.booking_date).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
        : '—';
    const createdDate = b.created_at
        ? new Date(b.created_at.replace(' ', 'T')).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
        : '—';

    document.getElementById('myw-modal-title').textContent = '#WS-' + String(b.id).padStart(5, '0');

    // Nút "Đặt lại"
    const rebookBtn = document.getElementById('myw-modal-rebook-btn');
    rebookBtn.href = 'workshop.php?ws_id=' + b.workshop_id;

    // Nút hủy trong footer modal — chỉ khi pending
    const footer = document.getElementById('myw-modal-footer');
    const existingCancel = document.getElementById('myw-modal-cancel-btn');
    if (existingCancel) existingCancel.remove();
    if (b.status === 'pending') {
        const cancelBtn = document.createElement('button');
        cancelBtn.id = 'myw-modal-cancel-btn';
        cancelBtn.className = 'myw-modal-footer-btn';
        cancelBtn.style.cssText = 'background:#FEF2F2;color:#DC2626;border:1.5px solid #FECACA;flex:0 0 auto;';
        cancelBtn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Hủy lịch`;
        cancelBtn.onclick = function() {
            closeBookingModal();
            confirmCancelWs(b.id, b.ws_name, bookingDate);
        };
        footer.insertBefore(cancelBtn, footer.firstChild);
    }

    document.getElementById('myw-modal-body').innerHTML = `
        <!-- Banner -->
        <div class="myw-modal-banner">
            <img src="${imgSrc}" alt="${b.ws_name}">
            <div class="myw-modal-banner-overlay"></div>
            <div class="myw-modal-banner-content">
                <div class="myw-modal-ws-name">${b.ws_name}</div>
                <div class="myw-modal-ws-dur">${b.duration_info}</div>
            </div>
        </div>

        <!-- Trạng thái -->
        <div class="myw-modal-section">
            <div class="myw-modal-section-title">Trạng thái</div>
            <div style="display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:50px;font-family:var(--font-menu);font-size:14px;font-weight:600;background:${st.bg};color:${st.color};">
                ${st.icon} &nbsp;${st.label}
            </div>
            ${b.status === 'pending' ? '<p style="font-family:var(--font-menu);font-size:13px;color:var(--ink3);margin-top:6px;">Chúng tôi sẽ liên hệ trong vòng 24h để xác nhận lịch và hướng dẫn thanh toán.</p>' : ''}
        </div>

        <!-- Thông tin lịch -->
        <div class="myw-modal-section">
            <div class="myw-modal-section-title">Thông tin đặt lịch</div>
            <div class="myw-info-grid">
                <div class="myw-info-box">
                    <div class="myw-info-label">Họ tên</div>
                    <div class="myw-info-val">${b.full_name}</div>
                </div>
                <div class="myw-info-box">
                    <div class="myw-info-label">Số điện thoại</div>
                    <div class="myw-info-val">${b.phone}</div>
                </div>
                <div class="myw-info-box">
                    <div class="myw-info-label">Ngày tham gia</div>
                    <div class="myw-info-val">${bookingDate}</div>
                </div>
                <div class="myw-info-box">
                    <div class="myw-info-label">Số người</div>
                    <div class="myw-info-val">${b.participants} người</div>
                </div>
                ${b.email ? `<div class="myw-info-box full">
                    <div class="myw-info-label">Email</div>
                    <div class="myw-info-val" style="font-weight:400;">${b.email}</div>
                </div>` : ''}
                ${b.notes ? `<div class="myw-info-box full">
                    <div class="myw-info-label">Ghi chú</div>
                    <div class="myw-info-val" style="font-weight:400;color:var(--ink2);">${b.notes}</div>
                </div>` : ''}
                <div class="myw-info-box full">
                    <div class="myw-info-label">Ngày đặt</div>
                    <div class="myw-info-val" style="font-weight:400;color:var(--ink3);font-size:13px;">${createdDate}</div>
                </div>
            </div>
        </div>

        <!-- Tổng tiền -->
        <div class="myw-modal-section">
            <div class="myw-modal-section-title">Chi phí</div>
            <div class="myw-modal-money">
                <div class="myw-modal-money-row">
                    <span>Giá / người</span>
                    <span>${fmt(b.ws_price)}</span>
                </div>
                <div class="myw-modal-money-row">
                    <span>Số người</span>
                    <span>× ${b.participants}</span>
                </div>
                <div class="myw-modal-money-total">
                    <span>Tổng cộng</span>
                    <span class="myw-modal-money-total-num">${fmt(total)}</span>
                </div>
                <div style="font-family:var(--font-mono);font-size:10px;color:rgba(255,255,255,0.35);margin-top:4px;text-align:right;">
                    Thanh toán trực tiếp tại buổi workshop
                </div>
            </div>
        </div>
    `;

    document.getElementById('myw-modal-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeBookingModal() {
    document.getElementById('myw-modal-overlay').classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeBookingModal();
});

// ============================================================
// HỦY ĐẶT LỊCH WORKSHOP — Confirm modal + fetch
// ============================================================
let _cancelWsId   = null;
let _cancelWsName = null;
let _cancelWsDate = null;

function confirmCancelWs(bookingId, wsName, wsDate) {
    _cancelWsId   = bookingId;
    _cancelWsName = wsName;
    _cancelWsDate = wsDate;

    if (!document.getElementById('sh-confirm-modal')) {
        const el = document.createElement('div');
        el.id = 'sh-confirm-modal';
        el.className = 'sh-confirm-overlay';
        el.innerHTML = `
            <div class="sh-confirm-backdrop" onclick="closeCancelConfirm()"></div>
            <div class="sh-confirm-box">
                <div class="sh-confirm-icon">🗑️</div>
                <div class="sh-confirm-title">Hủy lịch workshop?</div>
                <div class="sh-confirm-desc" id="sh-confirm-desc"></div>
                <div class="sh-confirm-btns">
                    <button class="sh-confirm-btn sh-confirm-btn-cancel" onclick="closeCancelConfirm()">Giữ lịch</button>
                    <button class="sh-confirm-btn sh-confirm-btn-confirm" id="sh-confirm-ok" onclick="doCancelWs()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Xác nhận hủy
                    </button>
                </div>
            </div>`;
        document.body.appendChild(el);
    }

    document.getElementById('sh-confirm-desc').innerHTML =
        `Bạn chắc chắn muốn hủy lịch <strong>${wsName}</strong> ngày <strong>${wsDate}</strong>?<br>
        <span style="color:#DC2626;font-size:13px;">Thao tác này không thể hoàn tác.</span>`;
    document.getElementById('sh-confirm-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeCancelConfirm() {
    const m = document.getElementById('sh-confirm-modal');
    if (m) m.style.display = 'none';
    document.body.style.overflow = '';
}

async function doCancelWs() {
    const btn = document.getElementById('sh-confirm-ok');
    btn.disabled = true;
    btn.textContent = 'Đang hủy...';

    try {
        const res  = await fetch('my-workshops.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'cancel', booking_id: _cancelWsId })
        });
        const data = await res.json();

        closeCancelConfirm();

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