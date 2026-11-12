<?php
ob_start();
session_start();
require_once '../config/database.php';

// Bảo vệ: chỉ admin mới vào được
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// ============================================================
// LẤY DỮ LIỆU THỐNG KÊ
// ============================================================

// Tổng đơn hàng (không tính đơn hủy) và doanh thu (chỉ đơn đã giao)
$r = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'delivered' THEN total ELSE 0 END) as revenue FROM orders WHERE status != 'cancelled'");
$order_stats = $r->fetch_assoc();

// Đơn hàng chờ xử lý
$r2 = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending'");
$pending_orders = $r2->fetch_assoc()['cnt'];

// Tổng sản phẩm
$r3 = $conn->query("SELECT COUNT(*) as cnt FROM products");
$total_products = $r3->fetch_assoc()['cnt'];

// Tổng khách hàng
$r4 = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'customer'");
$total_customers = $r4->fetch_assoc()['cnt'];

// Đơn hàng mới nhất (10 đơn)
$recent_orders = $conn->query("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    ORDER BY o.created_at DESC LIMIT 10
");

// Doanh thu 7 ngày gần nhất (chỉ đơn đã giao thành công)
$revenue_7d = $conn->query("
    SELECT DATE(created_at) as day, SUM(total) as rev, COUNT(*) as cnt
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'delivered' 
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$rev_data = [];
while ($row = $revenue_7d->fetch_assoc()) {
    $rev_data[] = $row;
}

// Sản phẩm bán chạy (chỉ từ đơn đã giao thành công)
$top_products = $conn->query("
    SELECT oi.product_name, SUM(oi.quantity) as sold, SUM(oi.subtotal) as revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'delivered' 
    GROUP BY oi.product_name
    ORDER BY sold DESC LIMIT 5
");

$status_colors = [
    'pending'   => '#F59E0B',
    'confirmed' => '#3B82F6',
    'shipping'  => '#8B5CF6',
    'delivered' => '#16A34A',
    'cancelled' => '#DC2626',
];
$status_labels = [
    'pending'   => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping'  => 'Đang giao',
    'delivered' => 'Đã giao',
    'cancelled' => 'Đã hủy',
];
$payment_labels = ['cod' => 'COD'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Soul & Hands</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500;1,600&family=Inter:ital,wght@0,300..700;1,300..700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
    /* ============================================================
       ADMIN — DESIGN SYSTEM
    ============================================================ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --bg:       #F5F4F0;
        --surface:  #FFFFFF;
        --border:   #E8E5DE;
        --ink:      #1A1C18;
        --ink2:     #4A4A48;
        --ink3:     #9A9890;
        --rust:     #D85A30;
        --rust-lt:  #FAECE7;
        --green:    #16A34A;
        --green-lt: #F0FDF4;
        --amber:    #F59E0B;
        --amber-lt: #FFFBEB;
        --blue:     #3B82F6;
        --blue-lt:  #EFF6FF;
        --purple:   #8B5CF6;
        --sidebar-w: 240px;
        --header-h:  64px;
    }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--ink); display: flex; min-height: 100vh; }

    /* ---- SIDEBAR ---- */
    .sidebar {
        width: var(--sidebar-w);
        background: var(--ink);
        height: 100vh;
        position: fixed; top: 0; left: 0;
        display: flex; flex-direction: column;
        z-index: 100; overflow-y: auto;
    }
    .sidebar-logo {
        padding: 24px 20px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        flex-shrink: 0;
    }
    .sidebar-logo-text {
        font-family: 'Cormorant Garamond', serif;
        font-size: 22px; color: #fff; letter-spacing: 3px;
    }
    .sidebar-logo-text em { font-style: normal; color: #C9A84C; }
    .sidebar-logo-sub {
        font-size: 10px; color: rgba(255,255,255,0.35);
        letter-spacing: 2px; text-transform: uppercase; margin-top: 4px;
    }
    .sidebar-nav { padding: 16px 0; flex: 1; }
    .nav-section-title {
        font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.3);
        letter-spacing: 2px; text-transform: uppercase;
        padding: 12px 20px 6px;
    }
    .nav-item {
        display: flex; align-items: center; gap: 12px;
        padding: 11px 20px; color: rgba(255,255,255,0.6);
        font-size: 14px; font-weight: 500; cursor: pointer;
        transition: 0.15s; text-decoration: none; border-radius: 0;
        position: relative;
    }
    .nav-item:hover { background: rgba(255,255,255,0.06); color: #fff; }
    .nav-item.active {
        background: rgba(216,90,48,0.15);
        color: #fff;
        border-right: 3px solid var(--rust);
    }
    .nav-item svg { flex-shrink: 0; opacity: 0.7; }
    .nav-item.active svg { opacity: 1; }
    .nav-badge {
        margin-left: auto; background: var(--rust);
        color: #fff; font-size: 10px; font-weight: 700;
        padding: 2px 7px; border-radius: 10px;
    }
    .sidebar-footer {
        padding: 16px 20px;
        border-top: 1px solid rgba(255,255,255,0.08);
    }
    .sidebar-user {
        display: flex; align-items: center; gap: 10px;
    }
    .sidebar-avatar {
        width: 34px; height: 34px; border-radius: 50%;
        background: var(--rust); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 700; flex-shrink: 0;
    }
    .sidebar-user-name { font-size: 13px; color: #fff; font-weight: 600; }
    .sidebar-user-role { font-size: 11px; color: rgba(255,255,255,0.4); }
    .sidebar-logout {
        margin-top: 12px; width: 100%;
        padding: 9px; border-radius: 8px;
        background: rgba(255,255,255,0.06); border: none;
        color: rgba(255,255,255,0.5); font-size: 13px; font-weight: 500;
        cursor: pointer; transition: 0.15s; text-align: center;
        display: block; text-decoration: none;
    }
    .sidebar-logout:hover { background: rgba(220,38,38,0.2); color: #FCA5A5; }

    /* ---- MAIN ---- */
    .main {
        margin-left: var(--sidebar-w);
        flex: 1; display: flex; flex-direction: column;
        min-height: 100vh;
    }

    /* ---- TOP BAR ---- */
    .topbar {
        height: var(--header-h);
        background: var(--surface);
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center;
        padding: 0 32px; gap: 16px;
        position: sticky; top: 0; z-index: 50;
    }
    .topbar-title {
        font-size: 18px; font-weight: 700; color: var(--ink); flex: 1;
    }
    .topbar-sub { font-size: 13px; color: var(--ink3); font-weight: 400; margin-left: 8px; }
    .topbar-date {
        font-family: 'Inter', sans-serif; font-variant-numeric: tabular-nums;
        font-size: 12px; color: var(--ink3);
    }
    .topbar-btn {
        display: flex; align-items: center; gap: 6px;
        padding: 8px 16px; border-radius: 8px;
        background: var(--ink); color: #fff;
        font-size: 13px; font-weight: 600;
        border: none; cursor: pointer; text-decoration: none;
        transition: background 0.2s;
    }
    .topbar-btn:hover { background: var(--rust); }

    /* ---- PAGE CONTENT ---- */
    .page { padding: 28px 32px; flex: 1; }
    .page-section { display: none; }
    .page-section.active { display: block; }

    /* ---- STAT CARDS ---- */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px; margin-bottom: 28px;
    }
    .stat-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px; padding: 20px 20px 18px;
        position: relative; overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .stat-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
    .stat-card-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; margin-bottom: 14px;
    }
    .stat-card-label {
        font-size: 12px; color: var(--ink3); font-weight: 500;
        text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;
    }
    .stat-card-value {
        font-size: 26px; font-weight: 700; color: var(--ink);
        letter-spacing: -0.5px; line-height: 1;
    }
    .stat-card-sub {
        font-size: 12px; color: var(--ink3); margin-top: 6px;
    }

    /* ---- GRID 2 CỘT ---- */
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .grid-3-1 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }

    /* ---- CARD ---- */
    .card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px; overflow: hidden;
    }
    .card-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 24px 16px;
        border-bottom: 1px solid var(--border);
    }
    .card-title {
        font-size: 15px; font-weight: 700; color: var(--ink);
        display: flex; align-items: center; gap: 8px;
    }
    .card-link {
        font-size: 12px; color: var(--rust); font-weight: 600;
        text-decoration: none; transition: opacity 0.2s; cursor: pointer;
    }
    .card-link:hover { opacity: 0.7; }
    .card-body { padding: 20px 24px; }

    /* ---- TABLE ---- */
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th {
        font-size: 11px; font-weight: 600; color: var(--ink3);
        text-transform: uppercase; letter-spacing: 0.5px;
        padding: 10px 16px; text-align: left;
        background: var(--bg); border-bottom: 1px solid var(--border);
    }
    .data-table td {
        padding: 13px 16px; font-size: 13px; color: var(--ink2);
        border-bottom: 1px solid var(--border);
    }
    .data-table tr:last-child td { border-bottom: none; }
    .data-table tr:hover td { background: #FAFAF8; }
    .data-table td strong { color: var(--ink); font-weight: 600; }

    /* ---- STATUS BADGE ---- */
    .status-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 600;
        white-space: nowrap;
    }
    .status-dot { width: 6px; height: 6px; border-radius: 50%; }

    /* ---- ACTION BUTTONS ---- */
    .action-btn {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 5px 10px; border-radius: 6px;
        font-size: 12px; font-weight: 500; cursor: pointer;
        border: 1px solid var(--border); background: #fff;
        color: var(--ink2); transition: 0.15s; text-decoration: none;
    }
    .action-btn:hover { border-color: var(--ink); color: var(--ink); }
    .action-btn.danger:hover { border-color: #DC2626; color: #DC2626; background: #FEF2F2; }
    .action-btn.primary { background: var(--rust); border-color: var(--rust); color: #fff; }
    .action-btn.primary:hover { background: #B84A20; }
    .action-btn:disabled, .action-btn:disabled:hover { opacity: 0.45; cursor: not-allowed; border-color: var(--border); color: var(--ink2); background: #fff; }

    /* ---- SEARCH & FILTER BAR ---- */
    .filter-bar {
        display: flex; align-items: center; gap: 12px;
        padding: 16px 24px; border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
    }
    .filter-bar input, .filter-bar select {
        height: 36px; padding: 0 12px;
        border: 1px solid var(--border); border-radius: 8px;
        font-size: 13px; color: var(--ink); outline: none;
        background: #fff; transition: border-color 0.2s;
    }
    .filter-bar input:focus, .filter-bar select:focus { border-color: var(--rust); }
    .filter-bar input { flex: 1; min-width: 200px; }

    /* ---- FORM ---- */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group.full { grid-column: 1 / -1; }
    .form-label { font-size: 12px; font-weight: 600; color: var(--ink2); }
    .form-input, .form-select, .form-textarea {
        padding: 9px 12px; border: 1px solid var(--border);
        border-radius: 8px; font-size: 13px; color: var(--ink);
        outline: none; transition: border-color 0.2s; width: 100%;
        background: #fff;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--rust); }
    .form-textarea { resize: vertical; min-height: 80px; }

    /* ---- MODAL ---- */
    .modal-overlay {
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,0.5); backdrop-filter: blur(3px);
        display: flex; align-items: center; justify-content: center;
    }
    .modal-box {
        background: var(--surface); border-radius: 16px;
        padding: 0; width: 90%; max-width: 600px;
        max-height: 90vh; overflow-y: auto;
        box-shadow: 0 24px 64px rgba(0,0,0,0.15);
        animation: modal-in 0.25s ease;
    }
    .modal-box.sm { max-width: 420px; }
    @keyframes modal-in { from { opacity: 0; transform: translateY(20px) scale(0.96); } to { opacity: 1; transform: none; } }
    .modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 20px 24px; border-bottom: 1px solid var(--border);
        position: sticky; top: 0; background: var(--surface); z-index: 1;
    }
    .modal-title { font-size: 16px; font-weight: 700; color: var(--ink); }
    .modal-close {
        width: 32px; height: 32px; border-radius: 8px;
        background: none; border: 1px solid var(--border);
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        color: var(--ink3); transition: 0.15s;
    }
    .modal-close:hover { background: var(--bg); color: var(--ink); }
    .modal-body { padding: 24px; }
    .modal-footer {
        display: flex; justify-content: flex-end; gap: 10px;
        padding: 16px 24px; border-top: 1px solid var(--border);
    }
    .btn { padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; }
    .btn-ghost { background: var(--bg); color: var(--ink2); border: 1px solid var(--border); }
    .btn-ghost:hover { border-color: var(--ink); color: var(--ink); }
    .btn-primary { background: var(--ink); color: #fff; }
    .btn-primary:hover { background: var(--rust); }
    .btn-danger { background: #DC2626; color: #fff; }
    .btn-danger:hover { background: #B91C1C; }

    /* ---- EMPTY STATE ---- */
    .empty-state { text-align: center; padding: 60px 20px; color: var(--ink3); }
    .empty-state-icon { font-size: 40px; margin-bottom: 12px; }
    .empty-state p { font-size: 14px; }

    /* ---- TOAST ---- */
    .admin-toast {
        position: fixed; bottom: 24px; right: 24px; z-index: 99999;
        padding: 14px 20px; border-radius: 10px; font-size: 14px;
        font-weight: 500; display: flex; align-items: center; gap: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        animation: toast-in 0.3s ease;
    }
    @keyframes toast-in { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: none; } }
    .admin-toast.success { background: #1A1C18; color: #4ADE80; }
    .admin-toast.error   { background: #1A1C18; color: #F87171; }

    /* ---- MISC ---- */
    .text-right { text-align: right; }
    .text-mono { font-family: 'Inter', sans-serif; font-variant-numeric: tabular-nums; font-size: 12px; }
    .flex-gap { display: flex; gap: 8px; align-items: center; }
    .product-thumb {
        width: 44px; height: 44px; border-radius: 8px; object-fit: cover;
        border: 1px solid var(--border); background: var(--bg); flex-shrink: 0;
    }
    .product-thumb-placeholder {
        width: 44px; height: 44px; border-radius: 8px;
        background: var(--bg); border: 1px solid var(--border);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; flex-shrink: 0;
    }
    .pagination { display: flex; gap: 6px; justify-content: flex-end; padding: 16px 24px; }
    .page-btn {
        width: 32px; height: 32px; border-radius: 6px;
        border: 1px solid var(--border); background: #fff;
        font-size: 13px; cursor: pointer; transition: 0.15s;
        display: flex; align-items: center; justify-content: center;
    }
    .page-btn.active { background: var(--ink); color: #fff; border-color: var(--ink); }
    .page-btn:hover:not(.active) { border-color: var(--ink); }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-text">Soul <em>&</em> Hands</div>
        <div class="sidebar-logo-sub">Quản trị hệ thống</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Tổng quan</div>
        <a class="nav-item active" data-section="dashboard">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>

        <div class="nav-section-title">Bán hàng</div>
        <a class="nav-item" data-section="orders">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            Đơn hàng
            <?php if ($pending_orders > 0): ?>
            <span class="nav-badge"><?= $pending_orders ?></span>
            <?php endif; ?>
        </a>

        <a class="nav-item" data-section="coupons">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            Mã giảm giá
        </a>
        <a class="nav-item" data-section="reviews">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Đánh giá
        </a>

        <div class="nav-section-title">Danh mục</div>
        <a class="nav-item" data-section="products">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            Sản phẩm
        </a>

        <div class="nav-section-title">Người dùng</div>
        <a class="nav-item" data-section="customers">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Khách hàng
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= strtoupper(mb_substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
            <div>
                <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></div>
                <div class="sidebar-user-role">Quản trị viên</div>
            </div>
        </div>
        <a href="../logout.php" class="sidebar-logout">← Đăng xuất</a>
    </div>
</aside>

<!-- ===== MAIN ===== -->
<div class="main">

    <!-- TOP BAR -->
    <div class="topbar">
        <div class="topbar-title" id="topbar-title">
            Dashboard
            <span class="topbar-sub" id="topbar-sub">Tổng quan hệ thống</span>
        </div>
        <div class="topbar-date"><?= date('d/m/Y H:i') ?></div>
        <a href="../index.php" class="topbar-btn" target="_blank">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Xem website
        </a>
    </div>

    <div class="page">

    <!-- ================================================================
         SECTION 1: DASHBOARD
    ================================================================ -->
    <div class="page-section active" id="section-dashboard">

        <!-- Stat cards -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-card-label">Tổng đơn hàng</div>
                <div class="stat-card-value"><?= number_format($order_stats['total']) ?></div>
                <div class="stat-card-sub"><?= $pending_orders ?> đang chờ xử lý</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Doanh thu</div>
                <div class="stat-card-value" style="font-size:20px;"><?= number_format($order_stats['revenue'] ?? 0, 0, ',', '.') ?>đ</div>
                <div class="stat-card-sub">Chỉ tính đơn đã giao thành công</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Sản phẩm</div>
                <div class="stat-card-value"><?= $total_products ?></div>
                <div class="stat-card-sub">4 danh mục</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Khách hàng</div>
                <div class="stat-card-value"><?= $total_customers ?></div>
                <div class="stat-card-sub">Tài khoản đã đăng ký</div>
            </div>
        </div>

        <div class="grid-3-1">
            <!-- Biểu đồ doanh thu 7 ngày -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        Doanh thu 7 ngày gần nhất
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="120"></canvas>
                </div>
            </div>

            <!-- Sản phẩm bán chạy -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">🔥 Bán chạy nhất</div>
                </div>
                <div style="padding: 8px 0;">
                    <?php if ($top_products->num_rows > 0):
                        $rank = 1;
                        while ($tp = $top_products->fetch_assoc()): ?>
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 20px;border-bottom:1px solid var(--border);">
                        <div style="width:22px;height:22px;border-radius:50%;background:<?= $rank===1?'#F59E0B':($rank===2?'#9CA3AF':($rank===3?'#D97706':'var(--bg)')) ?>;color:<?= $rank<=3?'#fff':'var(--ink3)' ?>;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?= $rank ?></div>
                        <div style="flex:1;font-size:13px;font-weight:500;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($tp['product_name']) ?></div>
                        <div style="font-size:12px;color:var(--ink3);white-space:nowrap;"><?= $tp['sold'] ?> bán</div>
                    </div>
                    <?php $rank++; endwhile;
                    else: ?>
                    <div class="empty-state"><div class="empty-state-icon">📊</div><p>Chưa có dữ liệu</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Đơn hàng mới nhất -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                    Đơn hàng mới nhất
                </div>
                <a class="card-link" data-section="orders">Xem tất cả →</a>
            </div>
            <table class="data-table">
                <thead><tr>
                    <th>Mã đơn</th><th>Khách hàng</th><th>SĐT</th>
                    <th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Ngày đặt</th><th></th>
                </tr></thead>
                <tbody>
                <?php if ($recent_orders->num_rows > 0):
                    while ($ord = $recent_orders->fetch_assoc()):
                        $sc = $status_colors[$ord['status']] ?? '#9CA3AF';
                        $sl = $status_labels[$ord['status']] ?? $ord['status'];
                ?>
                <tr>
                    <td><span class="text-mono" style="color:var(--rust);"><?= htmlspecialchars($ord['order_code']) ?></span></td>
                    <td><strong><?= htmlspecialchars($ord['receiver_name']) ?></strong></td>
                    <td class="text-mono"><?= htmlspecialchars($ord['receiver_phone']) ?></td>
                    <td><strong><?= number_format($ord['total'], 0, ',', '.') ?>đ</strong></td>
                    <td><?= htmlspecialchars($payment_labels[$ord['payment_method']] ?? $ord['payment_method']) ?></td>
                    <td>
                        <span class="status-badge" style="background:<?= $sc ?>18;color:<?= $sc ?>">
                            <span class="status-dot" style="background:<?= $sc ?>"></span>
                            <?= $sl ?>
                        </span>
                    </td>
                    <td class="text-mono"><?= date('d/m H:i', strtotime($ord['created_at'])) ?></td>
                    <td>
                        <button class="action-btn" onclick="viewOrder(<?= $ord['id'] ?>)">Chi tiết</button>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="8"><div class="empty-state"><div class="empty-state-icon">📭</div><p>Chưa có đơn hàng nào</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================================================================
         SECTION 2: ĐƠN HÀNG
    ================================================================ -->
    <div class="page-section" id="section-orders">
        <div class="card">
            <div class="filter-bar">
                <input type="text" id="order-search" placeholder="Tìm mã đơn, tên, SĐT..." oninput="filterOrders()">
                <select id="order-status-filter" onchange="filterOrders()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending">Chờ xác nhận</option>
                    <option value="confirmed">Đã xác nhận</option>
                    <option value="shipping">Đang giao</option>
                    <option value="delivered">Đã giao</option>
                    <option value="cancelled">Đã hủy</option>
                </select>
                <select id="order-payment-filter" onchange="filterOrders()">
                    <option value="">Tất cả thanh toán</option>
                    <option value="cod">COD</option>
                </select>
            </div>
            <table class="data-table" id="orders-table">
                <thead><tr>
                    <th>Mã đơn</th><th>Khách hàng</th><th>Địa chỉ</th>
                    <th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Ngày đặt</th><th>Thao tác</th>
                </tr></thead>
                <tbody id="orders-tbody">
                <?php
                $all_orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
                while ($ord = $all_orders->fetch_assoc()):
                    $sc = $status_colors[$ord['status']] ?? '#9CA3AF';
                    $sl = $status_labels[$ord['status']] ?? $ord['status'];
                ?>
                <tr class="order-row"
                    data-search="<?= htmlspecialchars(mb_strtolower($ord['order_code'].' '.$ord['receiver_name'].' '.$ord['receiver_phone'], 'UTF-8'), ENT_QUOTES) ?>"
                    data-status="<?= $ord['status'] ?>"
                    data-payment="<?= $ord['payment_method'] ?>">
                    <td><span class="text-mono" style="color:var(--rust);"><?= htmlspecialchars($ord['order_code']) ?></span></td>
                    <td>
                        <strong><?= htmlspecialchars($ord['receiver_name']) ?></strong><br>
                        <span class="text-mono" style="color:var(--ink3)"><?= htmlspecialchars($ord['receiver_phone']) ?></span>
                    </td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($ord['full_address']) ?>">
                        <?= htmlspecialchars($ord['full_address']) ?>
                    </td>
                    <td><strong><?= number_format($ord['total'], 0, ',', '.') ?>đ</strong></td>
                    <td><?= htmlspecialchars($payment_labels[$ord['payment_method']] ?? '') ?></td>
                    <td>
                        <span class="status-badge" style="background:<?= $sc ?>18;color:<?= $sc ?>">
                            <span class="status-dot" style="background:<?= $sc ?>"></span><?= $sl ?>
                        </span>
                    </td>
                    <td class="text-mono"><?= date('d/m/y H:i', strtotime($ord['created_at'])) ?></td>
                    <td>
                        <div class="flex-gap">
                            <button class="action-btn" onclick="viewOrder(<?= $ord['id'] ?>)">Chi tiết</button>
                            <?php if (in_array($ord['status'], ['delivered', 'cancelled'], true)): ?>
                            <button class="action-btn" disabled title="Đơn đã <?= $ord['status'] === 'delivered' ? 'giao thành công' : 'bị hủy' ?>, không thể cập nhật nữa">Cập nhật</button>
                            <?php else: ?>
                            <button class="action-btn" onclick="updateOrderStatus(<?= $ord['id'] ?>, '<?= $ord['status'] ?>')">Cập nhật</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================================================================
         SECTION 3: SẢN PHẨM
    ================================================================ -->
    <div class="page-section" id="section-products">
        <div class="card">
            <div class="filter-bar">
                <input type="text" id="product-search" placeholder="Tìm tên sản phẩm..." oninput="filterProducts()">
                <select id="product-cat-filter" onchange="filterProducts()">
                    <option value="">Tất cả danh mục</option>
                    <option value="1">Gốm sứ</option>
                    <option value="2">Gỗ điêu khắc</option>
                    <option value="3">Mây tre</option>
                    <option value="4">Sơn mài</option>
                </select>
                <button class="action-btn primary" onclick="openProductModal()">+ Thêm sản phẩm</button>
            </div>
            <table class="data-table">
                <thead><tr>
                    <th>Ảnh</th><th>Tên sản phẩm</th><th>Danh mục</th>
                    <th>Giá</th><th>Giá cũ</th><th>Badge</th><th>Ngày tạo</th><th>Thao tác</th>
                </tr></thead>
                <tbody id="products-tbody">
                <?php
                $all_products = $conn->query("
                    SELECT p.*, c.name as cat_name 
                    FROM products p JOIN categories c ON p.category_id = c.id 
                    ORDER BY p.id DESC
                ");
                while ($prod = $all_products->fetch_assoc()):
                    // Ảnh: link http(s) dùng nguyên, đường dẫn nội bộ thêm "../"
                    $thumb = '';
                    if (!empty($prod['image_url'])) {
                        $thumb = preg_match('#^https?://#i', $prod['image_url'])
                            ? $prod['image_url']
                            : '../' . ltrim($prod['image_url'], '/');
                    }
                ?>
                <tr class="product-row"
                    data-search="<?= htmlspecialchars(mb_strtolower($prod['name'], 'UTF-8'), ENT_QUOTES) ?>"
                    data-cat="<?= $prod['category_id'] ?>">
                    <td>
                        <?php if ($thumb): ?>
                            <img src="<?= htmlspecialchars($thumb) ?>" class="product-thumb" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div class="product-thumb-placeholder">🏺</div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($prod['name']) ?></strong>
                        <?php if (!empty($prod['description'])): ?>
                        <div style="font-size:12px;color:var(--ink3);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px;"><?= htmlspecialchars($prod['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($prod['cat_name']) ?></td>
                    <td><strong><?= number_format($prod['price'], 0, ',', '.') ?>đ</strong></td>
                    <td><?= $prod['old_price'] ? number_format($prod['old_price'], 0, ',', '.').'đ' : '—' ?></td>
                    <td>
                        <?php if ($prod['badge']): ?>
                        <span class="status-badge" style="background:<?= strpos($prod['badge'],'-')===0?'#FAECE7':'#F0FDF4' ?>;color:<?= strpos($prod['badge'],'-')===0?'var(--rust)':'var(--green)' ?>">
                            <?= htmlspecialchars($prod['badge']) ?>
                        </span>
                        <?php else: echo '—'; endif; ?>
                    </td>
                    <td class="text-mono"><?= date('d/m/y', strtotime($prod['created_at'])) ?></td>
                    <td>
                        <div class="flex-gap">
                            <button class="action-btn" onclick="editProduct(<?= htmlspecialchars(json_encode($prod), ENT_QUOTES) ?>)">Sửa</button>
                            <button class="action-btn danger" onclick="deleteProduct(<?= $prod['id'] ?>, <?= htmlspecialchars(json_encode($prod['name']), ENT_QUOTES) ?>)">Xóa</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================================================================
         SECTION 4: KHÁCH HÀNG
    ================================================================ -->
    <div class="page-section" id="section-customers">
        <div class="card">
            <div class="filter-bar">
                <input type="text" id="customer-search" placeholder="Tìm tên, email..." oninput="filterCustomers()">
            </div>
            <table class="data-table">
                <thead><tr>
                    <th>Họ tên</th><th>Email</th><th>SĐT</th><th>Vai trò</th><th>Ngày đăng ký</th><th>Đơn hàng</th>
                </tr></thead>
                <tbody id="customers-tbody">
                <?php
                $all_users = $conn->query("
                    SELECT u.*, 
                        (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count
                    FROM users u ORDER BY u.created_at DESC
                ");
                while ($u = $all_users->fetch_assoc()):
                ?>
                <tr class="customer-row" data-search="<?= htmlspecialchars(mb_strtolower($u['full_name'].' '.$u['email'], 'UTF-8'), ENT_QUOTES) ?>">
                    <td>
                        <div class="flex-gap">
                            <div style="width:32px;height:32px;border-radius:50%;background:<?= $u['role']==='admin'?'var(--rust)':'var(--blue)' ?>;color:#fff;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <?= strtoupper(mb_substr($u['full_name'], 0, 1)) ?>
                            </div>
                            <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td class="text-mono"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                    <td>
                        <span class="status-badge" style="background:<?= $u['role']==='admin'?'#FAECE7':'#EFF6FF' ?>;color:<?= $u['role']==='admin'?'var(--rust)':'var(--blue)' ?>">
                            <?= $u['role'] === 'admin' ? 'Admin' : 'Khách hàng' ?>
                        </span>
                    </td>
                    <td class="text-mono"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td><?= $u['order_count'] ?> đơn</td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- ================================================================
         SECTION: MÃ GIẢM GIÁ
    ================================================================ -->
    <div class="page-section" id="section-coupons">
        <div class="card">
            <div class="filter-bar">
                <input type="text" id="coupon-search" placeholder="Tìm mã giảm giá..." oninput="filterCoupons()">
                <button class="action-btn primary" onclick="openCouponModal()">+ Thêm mã giảm giá</button>
            </div>
            <table class="data-table">
                <thead><tr>
                    <th>Mã</th><th>Mức giảm</th><th>Điều kiện</th><th>Đã dùng</th>
                    <th>Hiệu lực</th><th>Trạng thái</th><th>Thao tác</th>
                </tr></thead>
                <tbody id="coupons-tbody">
                <?php
                $all_coupons = null;
                try { $all_coupons = $conn->query("SELECT * FROM coupons ORDER BY id DESC"); }
                catch (Throwable $e) { $all_coupons = null; }
                $now_ts = time();

                if ($all_coupons === null):
                ?>
                <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Chưa có bảng <strong>coupons</strong>. Hãy chạy file <strong>coupons_reviews.sql</strong> trong phpMyAdmin.</p></div></td></tr>
                <?php elseif ($all_coupons->num_rows === 0): ?>
                <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">🏷️</div><p>Chưa có mã giảm giá nào</p></div></td></tr>
                <?php else:
                while ($cp = $all_coupons->fetch_assoc()):
                    if (!(int)$cp['is_active'])                                                        { $cs_label = 'Đã tắt';          $cs_color = '#9CA3AF'; }
                    elseif (!empty($cp['start_date']) && strtotime($cp['start_date']) > $now_ts)      { $cs_label = 'Chưa bắt đầu';    $cs_color = '#3B82F6'; }
                    elseif (!empty($cp['end_date']) && strtotime($cp['end_date']) < $now_ts)          { $cs_label = 'Hết hạn';         $cs_color = '#DC2626'; }
                    elseif ($cp['usage_limit'] !== null && (int)$cp['used_count'] >= (int)$cp['usage_limit']) { $cs_label = 'Hết lượt'; $cs_color = '#F59E0B'; }
                    else                                                                               { $cs_label = 'Đang hoạt động';  $cs_color = '#16A34A'; }
                ?>
                <tr class="coupon-row" data-search="<?= htmlspecialchars(mb_strtolower($cp['code'] . ' ' . (string)$cp['description'], 'UTF-8'), ENT_QUOTES) ?>">
                    <td>
                        <span class="text-mono" style="color:var(--rust);font-weight:600;font-size:13px;"><?= htmlspecialchars($cp['code']) ?></span>
                        <?php if (!empty($cp['description'])): ?>
                        <div style="font-size:12px;color:var(--ink3);margin-top:2px;"><?= htmlspecialchars($cp['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= $cp['type'] === 'percent' ? (int)$cp['value'] . '%' : number_format($cp['value'], 0, ',', '.') . 'đ' ?></strong>
                        <?php if ($cp['type'] === 'percent' && !empty($cp['max_discount'])): ?>
                        <div style="font-size:12px;color:var(--ink3);">tối đa <?= number_format($cp['max_discount'], 0, ',', '.') ?>đ</div>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)$cp['min_order'] > 0 ? 'Đơn từ ' . number_format($cp['min_order'], 0, ',', '.') . 'đ' : 'Mọi đơn' ?></td>
                    <td><?= (int)$cp['used_count'] ?> / <?= $cp['usage_limit'] !== null ? (int)$cp['usage_limit'] : '∞' ?></td>
                    <td class="text-mono">
                        <?= !empty($cp['start_date']) ? date('d/m/Y', strtotime($cp['start_date'])) : '—' ?>
                        →
                        <?= !empty($cp['end_date']) ? date('d/m/Y', strtotime($cp['end_date'])) : '—' ?>
                    </td>
                    <td>
                        <span class="status-badge" style="background:<?= $cs_color ?>18;color:<?= $cs_color ?>">
                            <span class="status-dot" style="background:<?= $cs_color ?>"></span><?= $cs_label ?>
                        </span>
                    </td>
                    <td>
                        <div class="flex-gap">
                            <button class="action-btn" onclick="editCoupon(<?= htmlspecialchars(json_encode($cp), ENT_QUOTES) ?>)">Sửa</button>
                            <button class="action-btn" onclick="toggleCoupon(<?= (int)$cp['id'] ?>)"><?= (int)$cp['is_active'] ? 'Tắt' : 'Bật' ?></button>
                            <button class="action-btn danger" onclick="deleteCoupon(<?= (int)$cp['id'] ?>, <?= htmlspecialchars(json_encode($cp['code']), ENT_QUOTES) ?>)">Xóa</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================================================================
         SECTION: ĐÁNH GIÁ SẢN PHẨM
    ================================================================ -->
    <div class="page-section" id="section-reviews">
        <div class="card">
            <div class="filter-bar">
                <input type="text" id="review-search" placeholder="Tìm sản phẩm, khách hàng, nội dung..." oninput="filterReviews()">
                <select id="review-rating-filter" onchange="filterReviews()">
                    <option value="">Tất cả số sao</option>
                    <option value="5">5 sao</option>
                    <option value="4">4 sao</option>
                    <option value="3">3 sao</option>
                    <option value="2">2 sao</option>
                    <option value="1">1 sao</option>
                </select>
                <select id="review-status-filter" onchange="filterReviews()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="visible">Đang hiện</option>
                    <option value="hidden">Đã ẩn</option>
                </select>
            </div>
            <table class="data-table">
                <thead><tr>
                    <th>Sản phẩm</th><th>Khách hàng</th><th>Đánh giá</th><th>Nội dung</th>
                    <th>Ngày</th><th>Trạng thái</th><th>Thao tác</th>
                </tr></thead>
                <tbody id="reviews-tbody">
                <?php
                $all_reviews = null;
                try {
                    $all_reviews = $conn->query("
                        SELECT r.*, p.name AS product_name, u.full_name, u.email
                        FROM reviews r
                        JOIN products p ON p.id = r.product_id
                        JOIN users u ON u.id = r.user_id
                        ORDER BY r.created_at DESC
                    ");
                } catch (Throwable $e) { $all_reviews = null; }

                if ($all_reviews === null):
                ?>
                <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Chưa có bảng <strong>reviews</strong>. Hãy chạy file <strong>coupons_reviews.sql</strong> trong phpMyAdmin.</p></div></td></tr>
                <?php elseif ($all_reviews->num_rows === 0): ?>
                <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">⭐</div><p>Chưa có đánh giá nào</p></div></td></tr>
                <?php else:
                while ($rv = $all_reviews->fetch_assoc()):
                    $hidden = $rv['status'] === 'hidden';
                ?>
                <tr class="review-row"
                    data-search="<?= htmlspecialchars(mb_strtolower($rv['product_name'] . ' ' . $rv['full_name'] . ' ' . $rv['email'] . ' ' . (string)$rv['comment'], 'UTF-8'), ENT_QUOTES) ?>"
                    data-rating="<?= (int)$rv['rating'] ?>"
                    data-status="<?= htmlspecialchars($rv['status']) ?>">
                    <td style="max-width:180px;"><strong><?= htmlspecialchars($rv['product_name']) ?></strong></td>
                    <td>
                        <strong><?= htmlspecialchars($rv['full_name']) ?></strong><br>
                        <span class="text-mono" style="color:var(--ink3)"><?= htmlspecialchars($rv['email']) ?></span>
                    </td>
                    <td style="white-space:nowrap;color:#F59E0B;letter-spacing:1px;">
                        <?= str_repeat('★', (int)$rv['rating']) ?><span style="color:#D3D1C7;"><?= str_repeat('★', 5 - (int)$rv['rating']) ?></span>
                    </td>
                    <td style="max-width:280px;white-space:normal;line-height:1.5;">
                        <?= trim((string)$rv['comment']) !== '' ? htmlspecialchars(mb_strimwidth($rv['comment'], 0, 160, '…', 'UTF-8')) : '<span style="color:var(--ink3);">(không có nội dung)</span>' ?>
                    </td>
                    <td class="text-mono"><?= date('d/m/y', strtotime($rv['created_at'])) ?></td>
                    <td>
                        <span class="status-badge" style="background:<?= $hidden ? '#9CA3AF18' : '#16A34A18' ?>;color:<?= $hidden ? '#6B7280' : '#16A34A' ?>">
                            <span class="status-dot" style="background:<?= $hidden ? '#9CA3AF' : '#16A34A' ?>"></span><?= $hidden ? 'Đã ẩn' : 'Đang hiện' ?>
                        </span>
                    </td>
                    <td>
                        <div class="flex-gap">
                            <button class="action-btn" onclick="setReviewStatus(<?= (int)$rv['id'] ?>, '<?= $hidden ? 'visible' : 'hidden' ?>')"><?= $hidden ? 'Hiện' : 'Ẩn' ?></button>
                            <button class="action-btn danger" onclick="deleteReview(<?= (int)$rv['id'] ?>)">Xóa</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    </div><!-- /page -->
</div><!-- /main -->

<!-- ================================================================
     MODALS
================================================================ -->

<!-- Modal: Chi tiết đơn hàng -->
<div class="modal-overlay" id="modal-order" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Chi tiết đơn hàng</div>
            <button class="modal-close" onclick="closeModal('modal-order')">✕</button>
        </div>
        <div class="modal-body" id="modal-order-body">Đang tải...</div>
    </div>
</div>

<!-- Modal: Cập nhật trạng thái đơn -->
<div class="modal-overlay" id="modal-order-status" style="display:none;">
    <div class="modal-box sm">
        <div class="modal-header">
            <div class="modal-title">Cập nhật trạng thái đơn</div>
            <button class="modal-close" onclick="closeModal('modal-order-status')">✕</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="status-order-id">
            <div class="form-group">
                <label class="form-label">Trạng thái mới</label>
                <select class="form-select" id="new-order-status">
                    <option value="pending">Chờ xác nhận</option>
                    <option value="confirmed">Đã xác nhận</option>
                    <option value="shipping">Đang giao hàng</option>
                    <option value="delivered">Đã giao thành công</option>
                    <option value="cancelled">Hủy đơn</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-order-status')">Hủy</button>
            <button class="btn btn-primary" onclick="submitOrderStatus()">Lưu</button>
        </div>
    </div>
</div>

<!-- Modal: Thêm/Sửa mã giảm giá -->
<div class="modal-overlay" id="modal-coupon" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title" id="coupon-modal-title">Thêm mã giảm giá</div>
            <button class="modal-close" onclick="closeModal('modal-coupon')">✕</button>
        </div>
        <div class="modal-body">
            <form id="coupon-form" class="form-grid" onsubmit="return false;">
                <input type="hidden" id="coupon-id">
                <div class="form-group">
                    <label class="form-label">Mã *</label>
                    <input type="text" class="form-input" id="coupon-code" placeholder="VD: SUMMER10" maxlength="30" style="text-transform:uppercase;">
                </div>
                <div class="form-group">
                    <label class="form-label">Loại giảm *</label>
                    <select class="form-select" id="coupon-type" onchange="onCouponTypeChange()">
                        <option value="percent">Giảm theo %</option>
                        <option value="fixed">Giảm số tiền cố định (đ)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" id="coupon-value-label">Phần trăm giảm (%) *</label>
                    <input type="number" class="form-input" id="coupon-value" min="1">
                </div>
                <div class="form-group" id="coupon-max-group">
                    <label class="form-label">Giảm tối đa (đ)</label>
                    <input type="number" class="form-input" id="coupon-max" min="0" placeholder="Để trống = không giới hạn">
                </div>
                <div class="form-group">
                    <label class="form-label">Đơn tối thiểu (đ)</label>
                    <input type="number" class="form-input" id="coupon-min" min="0" placeholder="0 = mọi đơn">
                </div>
                <div class="form-group">
                    <label class="form-label">Giới hạn số lượt dùng</label>
                    <input type="number" class="form-input" id="coupon-limit" min="1" placeholder="Để trống = không giới hạn">
                </div>
                <div class="form-group">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" class="form-input" id="coupon-start">
                </div>
                <div class="form-group">
                    <label class="form-label">Đến hết ngày</label>
                    <input type="date" class="form-input" id="coupon-end">
                </div>
                <div class="form-group full">
                    <label class="form-label">Mô tả (chỉ admin thấy)</label>
                    <input type="text" class="form-input" id="coupon-desc" maxlength="255" placeholder="VD: Khuyến mãi mùa hè">
                </div>
                <div class="form-group full">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                        <input type="checkbox" id="coupon-active" checked> Đang bật (khách có thể dùng)
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-coupon')">Hủy</button>
            <button class="btn btn-primary" onclick="submitCoupon()">Lưu mã</button>
        </div>
    </div>
</div>

<!-- Modal: Thêm/Sửa sản phẩm -->
<div class="modal-overlay" id="modal-product" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title" id="product-modal-title">Thêm sản phẩm</div>
            <button class="modal-close" onclick="closeModal('modal-product')">✕</button>
        </div>
        <div class="modal-body">
            <form id="product-form" class="form-grid">
                <input type="hidden" id="prod-id">
                <div class="form-group full">
                    <label class="form-label">Tên sản phẩm *</label>
                    <input type="text" class="form-input" id="prod-name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Danh mục *</label>
                    <select class="form-select" id="prod-cat" onchange="onProductCatChange()">
                        <option value="1">Gốm sứ</option>
                        <option value="2">Gỗ điêu khắc</option>
                        <option value="3">Mây tre</option>
                        <option value="4">Sơn mài</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Badge (ví dụ: -20%, Mới)</label>
                    <input type="text" class="form-input" id="prod-badge" placeholder="-20% hoặc Mới">
                </div>
                <div class="form-group">
                    <label class="form-label">Giá bán (đ) *</label>
                    <input type="number" class="form-input" id="prod-price" min="0" required oninput="calcDiscountFromPrices()">
                </div>
                <div class="form-group">
                    <label class="form-label">Giá cũ (đ)</label>
                    <input type="number" class="form-input" id="prod-old-price" min="0" oninput="calcDiscountFromPrices()">
                </div>
                <div class="form-group">
                    <label class="form-label">Giảm giá (%)</label>
                    <input type="number" class="form-input" id="prod-discount" min="1" max="99" step="1" placeholder="Ví dụ: 20" oninput="calcPriceFromDiscount()">
                </div>
                <div class="form-group" style="justify-content:flex-end;">
                    <span style="font-size:12px;color:var(--ink3);line-height:1.5;">Nhập <b>giá bán + giá cũ</b> để tự tính %, hoặc nhập <b>giá cũ + %</b> để tự tính giá bán. Badge sẽ tự điền dạng -20%.</span>
                </div>
                <div class="form-group full">
                    <label class="form-label">URL ảnh</label>
                    <input type="text" class="form-input" id="prod-image" placeholder="assets/images/... hoặc https://...">
                </div>
                <div class="form-group full">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-textarea" id="prod-desc"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Phong cách</label>
                    <select class="form-select" id="prod-style">
                        <option value="">— Chọn —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Màu sắc</label>
                    <select class="form-select" id="prod-color">
                        <option value="">— Chọn —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Loại sản phẩm</label>
                    <select class="form-select" id="prod-subtype">
                        <option value="">— Chọn —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Image class (fallback)</label>
                    <select class="form-select" id="prod-imgclass">
                        <option value="">—</option>
                        <option value="rust">rust</option>
                        <option value="dark">dark</option>
                        <option value="warm">warm</option>
                        <option value="sage">sage</option>
                        <option value="cream">cream</option>
                        <option value="mauve">mauve</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-product')">Hủy</button>
            <button class="btn btn-primary" onclick="submitProduct()">Lưu sản phẩm</button>
        </div>
    </div>
</div>

<!-- ================================================================
     JAVASCRIPT
================================================================ -->
<script>
// ---- Sidebar navigation ----
const sections = {
    'dashboard': { title: 'Dashboard',  sub: 'Tổng quan hệ thống' },
    'orders':    { title: 'Đơn hàng',   sub: 'Quản lý đơn hàng' },
    'products':  { title: 'Sản phẩm',   sub: 'Quản lý sản phẩm' },
    'customers': { title: 'Khách hàng', sub: 'Danh sách tài khoản' },
    'coupons':   { title: 'Mã giảm giá', sub: 'Quản lý mã khuyến mãi' },
    'reviews':   { title: 'Đánh giá',    sub: 'Đánh giá sản phẩm của khách' },
};

document.querySelectorAll('.nav-item[data-section]').forEach(function(item) {
    item.addEventListener('click', function() {
        const sec = this.dataset.section;
        // Active nav
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        this.classList.add('active');
        // Show section
        document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
        document.getElementById('section-' + sec).classList.add('active');
        // Update topbar
        document.getElementById('topbar-title').innerHTML =
            sections[sec].title +
            ' <span class="topbar-sub">' + sections[sec].sub + '</span>';
    });
});

// Xử lý card-link click
document.querySelectorAll('.card-link[data-section]').forEach(function(link) {
    link.addEventListener('click', function() {
        const sec = this.dataset.section;
        document.querySelector('.nav-item[data-section="'+sec+'"]').click();
    });
});

// ---- Biểu đồ doanh thu ----
const revData = <?= json_encode($rev_data) ?>;
const labels  = revData.map(d => {
    const dt = new Date(d.day);
    return (dt.getDate()).toString().padStart(2,'0') + '/' + (dt.getMonth()+1).toString().padStart(2,'0');
});
const values = revData.map(d => d.rev);

const ctx = document.getElementById('revenueChart');
if (ctx) {
    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(216,90,48,0.18)');
    gradient.addColorStop(1, 'rgba(216,90,48,0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length ? labels : ['Chưa có dữ liệu'],
            datasets: [{
                label: 'Doanh thu (đ)',
                data: values.length ? values : [0],
                backgroundColor: gradient,
                borderColor: '#D85A30',
                borderWidth: 2.5,
                pointBackgroundColor: '#D85A30',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1A1C18',
                    titleColor: '#fff',
                    bodyColor: '#D0CEC8',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: ctx => ' ' + Number(ctx.raw).toLocaleString('vi-VN') + 'đ'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: v => (v >= 1000000)
                            ? (v/1000000).toFixed(1) + 'tr'
                            : (v/1000).toLocaleString('vi-VN') + 'k',
                        font: { size: 11 }, color: '#9A9890'
                    },
                    grid: { color: '#F0EDE6' },
                    border: { display: false }
                },
                x: {
                    ticks: { font: { size: 11 }, color: '#9A9890' },
                    grid: { display: false },
                    border: { display: false }
                }
            }
        }
    });
}

// ---- Modal helpers ----
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }

document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.style.display = 'none';
    });
});

// ---- Toast ----
function showToast(msg, type) {
    type = type || 'success';
    var t = document.createElement('div');
    t.className = 'admin-toast ' + type;
    t.innerHTML = (type === 'success' ? '✓ ' : '✗ ') + msg;
    document.body.appendChild(t);
    setTimeout(function() { t.remove(); }, 3000);
}

// ---- AJAX helper ----
function ajax(url, data, callback) {
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(callback)
    .catch(function(e) { showToast('Lỗi kết nối: ' + e.message, 'error'); });
}

// ---- Escape HTML (chống XSS khi ghép chuỗi vào innerHTML) ----
function esc(v) {
    return String(v == null ? '' : v)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ---- Filter orders ----
function filterOrders() {
    var q  = document.getElementById('order-search').value.toLowerCase();
    var st = document.getElementById('order-status-filter').value;
    var pm = document.getElementById('order-payment-filter').value;
    document.querySelectorAll('.order-row').forEach(function(row) {
        var ok = (!q  || row.dataset.search.includes(q))
              && (!st || row.dataset.status  === st)
              && (!pm || row.dataset.payment === pm);
        row.style.display = ok ? '' : 'none';
    });
}

// ---- Filter products ----
function filterProducts() {
    var q   = document.getElementById('product-search').value.toLowerCase();
    var cat = document.getElementById('product-cat-filter').value;
    document.querySelectorAll('.product-row').forEach(function(row) {
        var ok = (!q   || row.dataset.search.includes(q))
              && (!cat || row.dataset.cat === cat);
        row.style.display = ok ? '' : 'none';
    });
}

// ---- Filter customers ----
function filterCustomers() {
    var q = document.getElementById('customer-search').value.toLowerCase();
    document.querySelectorAll('.customer-row').forEach(function(row) {
        row.style.display = !q || row.dataset.search.includes(q) ? '' : 'none';
    });
}

// ---- Xem chi tiết đơn hàng ----
function viewOrder(id) {
    document.getElementById('modal-order-body').innerHTML = '<div style="text-align:center;padding:40px;color:var(--ink3);">Đang tải...</div>';
    openModal('modal-order');
    ajax('admin_ajax.php', { action: 'get_order', id: id }, function(data) {
        if (!data.success) { document.getElementById('modal-order-body').innerHTML = '<p>Lỗi tải dữ liệu</p>'; return; }
        var o = data.order;
        var items = data.items;
        var sc = { pending:'#F59E0B', confirmed:'#3B82F6', shipping:'#8B5CF6', delivered:'#16A34A', cancelled:'#DC2626' };
        var sl = { pending:'Chờ xác nhận', confirmed:'Đã xác nhận', shipping:'Đang giao', delivered:'Đã giao', cancelled:'Đã hủy' };
        var pl = { cod:'Thanh toán khi nhận'};

        var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">'
            +'<div><div style="font-size:11px;color:var(--ink3);margin-bottom:4px;">MÃ ĐƠN</div>'
            +'<div style="font-family:Inter,sans-serif;font-variant-numeric:tabular-nums;font-size:20px;font-weight:700;color:var(--rust);">'+esc(o.order_code)+'</div></div>'
            +'<div style="text-align:right;"><span class="status-badge" style="background:'+(sc[o.status]||'#ccc')+'18;color:'+(sc[o.status]||'#ccc')+';">'
            +'<span class="status-dot" style="background:'+(sc[o.status]||'#ccc')+'"></span>'+(sl[o.status]||o.status)+'</span></div>'
            +'</div>'
            +'<div style="background:var(--bg);border-radius:10px;padding:16px;margin-bottom:16px;">'
            +'<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;">'
            +'<div><span style="color:var(--ink3);">Người nhận: </span><strong>'+esc(o.receiver_name)+'</strong></div>'
            +'<div><span style="color:var(--ink3);">SĐT: </span>'+esc(o.receiver_phone)+'</div>'
            +'<div style="grid-column:1/-1;"><span style="color:var(--ink3);">Địa chỉ: </span>'+esc(o.full_address)+'</div>'
            +(o.note ? '<div style="grid-column:1/-1;"><span style="color:var(--ink3);">Ghi chú: </span>'+esc(o.note)+'</div>' : '')
            +'</div></div>'
            +'<div style="margin-bottom:16px;">'
            +'<div style="font-size:12px;font-weight:600;color:var(--ink3);margin-bottom:8px;text-transform:uppercase;">Sản phẩm</div>';

        items.forEach(function(item) {
            html += '<div style="display:flex;gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);">'
                +'<div style="flex:1;font-size:13px;font-weight:500;">'+esc(item.product_name)+'</div>'
                +'<div style="font-size:12px;color:var(--ink3);">x'+item.quantity+'</div>'
                +'<div style="font-weight:700;font-size:13px;">'+Number(item.subtotal).toLocaleString('vi-VN')+'đ</div>'
                +'</div>';
        });

        html += '</div>'
            +'<div style="border-top:1px solid var(--border);padding-top:12px;display:flex;flex-direction:column;gap:6px;font-size:13px;">'
            +'<div style="display:flex;justify-content:space-between;color:var(--ink2);"><span>Tạm tính</span><span>'+Number(o.subtotal).toLocaleString('vi-VN')+'đ</span></div>'
            +'<div style="display:flex;justify-content:space-between;color:var(--ink2);"><span>Phí ship</span><span>'+Number(o.shipping_fee).toLocaleString('vi-VN')+'đ</span></div>'
            +(o.discount>0?'<div style="display:flex;justify-content:space-between;color:#16A34A;"><span>Giảm giá</span><span>− '+Number(o.discount).toLocaleString('vi-VN')+'đ</span></div>':'')
            +'<div style="display:flex;justify-content:space-between;font-weight:700;font-size:16px;color:var(--ink);margin-top:4px;"><span>Tổng cộng</span><span style="color:var(--rust);">'+Number(o.total).toLocaleString('vi-VN')+'đ</span></div>'
            +'<div style="color:var(--ink3);font-size:12px;">'+(pl[o.payment_method]||o.payment_method)+'</div>'
            +'</div>';

        document.getElementById('modal-order-body').innerHTML = html;
    });
}

// ---- Cập nhật trạng thái đơn ----
function updateOrderStatus(id, currentStatus) {
    if (currentStatus === 'delivered' || currentStatus === 'cancelled') {
        showToast('Đơn đã hoàn tất hoặc đã hủy, không thể cập nhật nữa', 'error');
        return;
    }
    document.getElementById('status-order-id').value = id;
    document.getElementById('new-order-status').value = currentStatus;
    openModal('modal-order-status');
}
function submitOrderStatus() {
    var id  = document.getElementById('status-order-id').value;
    var st  = document.getElementById('new-order-status').value;
    ajax('admin_ajax.php', { action: 'update_order_status', id: id, status: st }, function(data) {
        if (data.success) {
            showToast('Cập nhật trạng thái thành công');
            closeModal('modal-order-status');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            showToast(data.message || 'Lỗi', 'error');
        }
    });
}

// ---- Sản phẩm ----
// Lựa chọn theo từng danh mục — khớp với bộ lọc ở gom-su.php, go-dieu-khac.php, may-tre.php, son-mai.php
const PRODUCT_OPTIONS = {
    '1': { // Gốm sứ
        color: { 'trang':'Trắng', 'nau':'Nâu', 'xanh':'Xanh', 'den':'Đen', 'hong':'Hồng', 'kem':'Kem' },
        type:  { 'binh-hoa':'Bình hoa & Lọ', 'am-tra':'Bộ ấm trà', 'chen-bat':'Chén bát', 'tuong':'Tượng trang trí' },
        style: { 'hien-dai':'Hiện đại', 'truyen-thong':'Truyền thống', 'toi-gian':'Tối giản', 'dan-gian':'Dân gian' }
    },
    '2': { // Gỗ điêu khắc
        color: { 'nau-sang':'Nâu sáng', 'nau-dam':'Nâu đậm', 'den':'Đen', 'tu-nhien':'Tự nhiên', 'vang-gold':'Vàng gold' },
        type:  { 'tuong-nghe-thuat':'Tượng nghệ thuật', 'khay-dia':'Khay & Đĩa gỗ', 'do-trang-tri':'Đồ trang trí bàn', 'do-dung':'Đồ dùng hàng ngày' },
        style: { 'hien-dai':'Hiện đại', 'truyen-thong':'Truyền thống', 'dan-gian':'Dân gian', 'thien-nhien':'Thiên nhiên' }
    },
    '3': { // Mây tre
        color: { 'kem-vang':'Kem vàng', 'nau-tre':'Nâu tre', 'trang-moc':'Trắng mộc', 'xanh-la':'Xanh lá', 'den-bong':'Đen bóng' },
        type:  { 'gio-ro':'Giỏ & Rổ đan tay', 'khay-may':'Khay khảm mây', 'den-long':'Đèn lồng mây tre', 'do-noi-that':'Đồ nội thất nhỏ' },
        style: { 'thien-nhien':'Thiên nhiên', 'truyen-thong':'Truyền thống', 'hien-dai':'Hiện đại', 'bohemian':'Bohemian' }
    },
    '4': { // Sơn mài
        color: { 'do-son':'Đỏ son', 'den-son':'Đen sơn', 'vang-nhung':'Vàng nhũ', 'xanh-co-vit':'Xanh cổ vịt', 'nau-canh-gian':'Nâu cánh gián' },
        type:  { 'tranh-tuong':'Tranh treo tường', 'hop-khay':'Hộp & Khay sơn mài', 'trang-suc':'Trang sức sơn mài', 'do-trang-tri':'Đồ trang trí' },
        style: { 'truyen-thong':'Truyền thống', 'hien-dai':'Hiện đại', 'co-dien':'Cổ điển', 'phong-dong':'Phương Đông' }
    }
};

// Nạp lại 3 ô chọn (phong cách, màu sắc, loại) theo danh mục; selected = giá trị cần chọn sẵn
function fillProductOptions(catId, selected) {
    selected = selected || {};
    var opts = PRODUCT_OPTIONS[String(catId)] || { color: {}, type: {}, style: {} };
    var targets = [
        { id: 'prod-style',   list: opts.style, value: selected.style },
        { id: 'prod-color',   list: opts.color, value: selected.color },
        { id: 'prod-subtype', list: opts.type,  value: selected.sub_type }
    ];
    targets.forEach(function(t) {
        var sel = document.getElementById(t.id);
        sel.innerHTML = '';
        var blank = document.createElement('option');
        blank.value = ''; blank.textContent = '— Chọn —';
        sel.appendChild(blank);
        Object.keys(t.list).forEach(function(key) {
            var o = document.createElement('option');
            o.value = key; o.textContent = t.list[key];
            sel.appendChild(o);
        });
        // Giữ lại giá trị cũ nếu nó không nằm trong danh sách (tránh mất dữ liệu khi sửa)
        if (t.value && !t.list[t.value]) {
            var legacy = document.createElement('option');
            legacy.value = t.value; legacy.textContent = t.value + ' (giá trị cũ)';
            sel.appendChild(legacy);
        }
        sel.value = t.value || '';
    });
}

// ---- Tự tính giá / phần trăm giảm giá ----
function toNum(id) {
    var n = parseFloat(document.getElementById(id).value);
    return isNaN(n) ? 0 : n;
}
// Tự điền badge dạng -N% khi có giảm giá; nếu hết giảm giá mà badge đang là "-..%" thì xóa
function setAutoBadge(pct) {
    var b = document.getElementById('prod-badge');
    if (pct > 0) {
        b.value = '-' + pct + '%';
    } else if (/^\s*-/.test(b.value)) {
        b.value = '';
    }
}
// Có giá bán + giá cũ  →  tính % giảm
function calcDiscountFromPrices(touchBadge) {
    var price = toNum('prod-price');
    var old   = toNum('prod-old-price');
    var d     = document.getElementById('prod-discount');
    var pct   = (old > 0 && price > 0 && old > price) ? Math.round((1 - price / old) * 100) : 0;
    d.value = pct > 0 ? pct : '';
    if (touchBadge !== false) setAutoBadge(pct);
}
// Có giá cũ + %  →  tính giá bán (làm tròn đến đồng)
function calcPriceFromDiscount() {
    var old = toNum('prod-old-price');
    var pct = toNum('prod-discount');
    if (old > 0 && pct > 0 && pct < 100) {
        document.getElementById('prod-price').value = Math.round(old * (1 - pct / 100));
        setAutoBadge(Math.round(pct));
    } else if (!(pct > 0)) {
        setAutoBadge(0);
    }
}

// Khi đổi danh mục: nạp lại các lựa chọn (không giữ giá trị cũ vì khác danh mục)
function onProductCatChange() {
    fillProductOptions(document.getElementById('prod-cat').value, {});
}

function openProductModal() {
    document.getElementById('product-modal-title').textContent = 'Thêm sản phẩm';
    document.getElementById('product-form').reset();
    document.getElementById('prod-id').value = '';
    fillProductOptions(document.getElementById('prod-cat').value, {});
    openModal('modal-product');
}
function editProduct(prod) {
    document.getElementById('product-modal-title').textContent = 'Sửa sản phẩm';
    document.getElementById('prod-id').value        = prod.id;
    document.getElementById('prod-name').value      = prod.name;
    document.getElementById('prod-cat').value       = prod.category_id;
    document.getElementById('prod-badge').value     = prod.badge || '';
    document.getElementById('prod-price').value     = prod.price;
    document.getElementById('prod-old-price').value = prod.old_price || '';
    document.getElementById('prod-image').value     = prod.image_url || '';
    document.getElementById('prod-desc').value      = prod.description || '';
    fillProductOptions(prod.category_id, { style: prod.style, color: prod.color, sub_type: prod.sub_type });
    calcDiscountFromPrices(false);
    document.getElementById('prod-imgclass').value  = prod.image_class || '';
    openModal('modal-product');
}
function submitProduct() {
    var payload = {
        action:      'save_product',
        id:          document.getElementById('prod-id').value,
        name:        document.getElementById('prod-name').value,
        category_id: document.getElementById('prod-cat').value,
        badge:       document.getElementById('prod-badge').value,
        price:       document.getElementById('prod-price').value,
        old_price:   document.getElementById('prod-old-price').value,
        image_url:   document.getElementById('prod-image').value,
        description: document.getElementById('prod-desc').value,
        style:       document.getElementById('prod-style').value,
        color:       document.getElementById('prod-color').value,
        sub_type:    document.getElementById('prod-subtype').value,
        image_class: document.getElementById('prod-imgclass').value,
    };
    if (!payload.name || !payload.price) { showToast('Vui lòng điền tên và giá', 'error'); return; }
    ajax('admin_ajax.php', payload, function(data) {
        if (data.success) {
            showToast(data.message || 'Đã lưu sản phẩm');
            closeModal('modal-product');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            showToast(data.message || 'Lỗi', 'error');
        }
    });
}
function deleteProduct(id, name) {
    if (!confirm('Xóa sản phẩm "' + name + '"?\nHành động này không thể hoàn tác!')) return;
    ajax('admin_ajax.php', { action: 'delete_product', id: id }, function(data) {
        if (data.success) {
            showToast('Đã xóa sản phẩm');
            setTimeout(function() { location.reload(); }, 600);
        } else {
            showToast(data.message || 'Lỗi', 'error');
        }
    });
}

// ---- Mã giảm giá ----
function filterCoupons() {
    var q = document.getElementById('coupon-search').value.toLowerCase();
    document.querySelectorAll('.coupon-row').forEach(function(row) {
        row.style.display = !q || row.dataset.search.includes(q) ? '' : 'none';
    });
}
function onCouponTypeChange() {
    var isPercent = document.getElementById('coupon-type').value === 'percent';
    document.getElementById('coupon-value-label').textContent = isPercent ? 'Phần trăm giảm (%) *' : 'Số tiền giảm (đ) *';
    document.getElementById('coupon-value').max = isPercent ? 100 : '';
    document.getElementById('coupon-max-group').style.display = isPercent ? '' : 'none';
}
function openCouponModal() {
    document.getElementById('coupon-modal-title').textContent = 'Thêm mã giảm giá';
    document.getElementById('coupon-form').reset();
    document.getElementById('coupon-id').value = '';
    document.getElementById('coupon-code').disabled = false;
    document.getElementById('coupon-active').checked = true;
    onCouponTypeChange();
    openModal('modal-coupon');
}
function editCoupon(c) {
    document.getElementById('coupon-modal-title').textContent = 'Sửa mã giảm giá';
    document.getElementById('coupon-id').value      = c.id;
    document.getElementById('coupon-code').value    = c.code;
    document.getElementById('coupon-code').disabled = true;   // không đổi mã khi sửa
    document.getElementById('coupon-type').value    = c.type;
    document.getElementById('coupon-value').value   = c.value;
    document.getElementById('coupon-max').value     = c.max_discount || '';
    document.getElementById('coupon-min').value     = Number(c.min_order) > 0 ? c.min_order : '';
    document.getElementById('coupon-limit').value   = c.usage_limit || '';
    document.getElementById('coupon-start').value   = c.start_date ? String(c.start_date).slice(0, 10) : '';
    document.getElementById('coupon-end').value     = c.end_date ? String(c.end_date).slice(0, 10) : '';
    document.getElementById('coupon-desc').value    = c.description || '';
    document.getElementById('coupon-active').checked = String(c.is_active) === '1';
    onCouponTypeChange();
    openModal('modal-coupon');
}
function submitCoupon() {
    var payload = {
        action:       'save_coupon',
        id:           document.getElementById('coupon-id').value,
        code:         document.getElementById('coupon-code').value.trim().toUpperCase(),
        type:         document.getElementById('coupon-type').value,
        value:        document.getElementById('coupon-value').value,
        max_discount: document.getElementById('coupon-max').value,
        min_order:    document.getElementById('coupon-min').value,
        usage_limit:  document.getElementById('coupon-limit').value,
        start_date:   document.getElementById('coupon-start').value,
        end_date:     document.getElementById('coupon-end').value,
        description:  document.getElementById('coupon-desc').value,
        is_active:    document.getElementById('coupon-active').checked ? 1 : 0
    };
    if (!payload.id && !payload.code) { showToast('Vui lòng nhập mã', 'error'); return; }
    if (!payload.value) { showToast('Vui lòng nhập mức giảm', 'error'); return; }
    ajax('admin_ajax.php', payload, function(data) {
        if (data.success) {
            showToast(data.message || 'Đã lưu');
            closeModal('modal-coupon');
            setTimeout(function() { location.reload(); }, 700);
        } else {
            showToast(data.message || 'Lỗi', 'error');
        }
    });
}
function toggleCoupon(id) {
    ajax('admin_ajax.php', { action: 'toggle_coupon', id: id }, function(data) {
        if (data.success) { showToast(data.message); setTimeout(function() { location.reload(); }, 500); }
        else showToast(data.message || 'Lỗi', 'error');
    });
}
function deleteCoupon(id, code) {
    if (!confirm('Xóa mã "' + code + '"?\nCác đơn hàng đã dùng mã này vẫn giữ nguyên.')) return;
    ajax('admin_ajax.php', { action: 'delete_coupon', id: id }, function(data) {
        if (data.success) { showToast(data.message); setTimeout(function() { location.reload(); }, 500); }
        else showToast(data.message || 'Lỗi', 'error');
    });
}

// ---- Đánh giá ----
function filterReviews() {
    var q  = document.getElementById('review-search').value.toLowerCase();
    var rt = document.getElementById('review-rating-filter').value;
    var st = document.getElementById('review-status-filter').value;
    document.querySelectorAll('.review-row').forEach(function(row) {
        var ok = (!q  || row.dataset.search.includes(q))
              && (!rt || row.dataset.rating === rt)
              && (!st || row.dataset.status === st);
        row.style.display = ok ? '' : 'none';
    });
}
function setReviewStatus(id, status) {
    ajax('admin_ajax.php', { action: 'set_review_status', id: id, status: status }, function(data) {
        if (data.success) { showToast(data.message); setTimeout(function() { location.reload(); }, 500); }
        else showToast(data.message || 'Lỗi', 'error');
    });
}
function deleteReview(id) {
    if (!confirm('Xóa vĩnh viễn đánh giá này?\nNếu chỉ muốn khách khác không thấy, hãy dùng nút "Ẩn".')) return;
    ajax('admin_ajax.php', { action: 'delete_review', id: id }, function(data) {
        if (data.success) { showToast(data.message); setTimeout(function() { location.reload(); }, 500); }
        else showToast(data.message || 'Lỗi', 'error');
    });
}
</script>
</body>
</html>