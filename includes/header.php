<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soul & Hands — Mỹ Nghệ Thủ Công</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500;1,600&family=Inter:ital,wght@0,300..700;1,300..700&display=swap" rel="stylesheet">
    
    
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: 1 ?>">
    <link rel="stylesheet" href="assets/css/category.css?v=<?= @filemtime(__DIR__ . '/../assets/css/category.css') ?: 1 ?>">

    <?php
    // Truyền trạng thái đăng nhập sang JS
    $is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    ?>
    <script>
        window.SH_USER = {
            loggedIn: <?= $is_logged_in ? 'true' : 'false' ?>,
            id: <?= $is_logged_in ? (int)$_SESSION['user_id'] : 'null' ?>,
            name: <?= $is_logged_in ? json_encode($_SESSION['user_name'] ?? '') : 'null' ?>
        };
    </script>
    <?php if (!$is_logged_in): ?>
    <!-- Khách chưa đăng nhập: xóa giỏ lưu trong trình duyệt và ẩn số trên biểu tượng giỏ.
         Nằm ngay trong HTML do PHP tạo ra nên chạy được kể cả khi cart.js bị cache bản cũ. -->
    <script>
        try {
            localStorage.removeItem('sh_cart');
            localStorage.removeItem('sh_cart_owner');
            localStorage.removeItem('sh_ship_draft');
        } catch (e) {}
    </script>
    <style>.cart-count-badge { display: none !important; }</style>
    <?php endif; ?>
</head>
<body>

<nav class="header-nav" style="position: relative;">
    <div class="nav-top">
        <div class="nav-left">
    <div class="nav-dropdown-wrapper">
        <button class="nav-item nav-category-btn" id="mega-menu-btn" aria-expanded="false">
            Danh mục 
            <svg class="chevron-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        
        <div class="mega-menu" id="mega-menu-content">
            <div class="mega-menu-inner">
                <div class="mega-col">
                    <h4>Gốm sứ</h4>
                    <ul>
                        <li><a href="gom-su.php">Tất cả Gốm sứ</a></li>
                        <li><a href="gom-su.php?sub_type[]=binh-hoa">Bình hoa & Lọ</a></li>
                        <li><a href="gom-su.php?sub_type[]=am-tra">Bộ ấm trà</a></li>
                        <li><a href="gom-su.php?sub_type[]=chen-bat">Chén bát mộc</a></li>
                        <li><a href="gom-su.php?sub_type[]=tuong">Tượng trang trí</a></li>
                    </ul>
                </div>
                <div class="mega-col">
                    <h4>Gỗ điêu khắc</h4>
                    <ul>
                        <li><a href="go-dieu-khac.php">Tất cả Đồ gỗ</a></li>
                        <li><a href="go-dieu-khac.php?sub_type[]=tuong-nghe-thuat">Tượng nghệ thuật</a></li>
                        <li><a href="go-dieu-khac.php?sub_type[]=khay-dia">Khay & Đĩa gỗ</a></li>
                        <li><a href="go-dieu-khac.php?sub_type[]=do-trang-tri">Đồ trang trí bàn</a></li>
                    </ul>
                </div>
                <div class="mega-col">
                    <h4>Mây tre</h4>
                    <ul>
                        <li><a href="may-tre.php">Tất cả Mây tre</a></li>
                        <li><a href="may-tre.php?sub_type[]=gio-ro">Giỏ đan tay</a></li>
                        <li><a href="may-tre.php?sub_type[]=khay-may">Khay khảm mây</a></li>
                        <li><a href="may-tre.php?sub_type[]=den-long">Đèn lồng mây tre</a></li>
                    </ul>
                </div>
                <div class="mega-col">
                    <h4>Sơn mài</h4>
                    <ul>
                        <li><a href="son-mai.php">Tất cả Sơn mài</a></li>
                        <li><a href="son-mai.php?sub_type[]=tranh-tuong">Tranh treo tường</a></li>
                        <li><a href="son-mai.php?sub_type[]=hop-khay">Hộp mứt & Khay</a></li>
                        <li><a href="son-mai.php?sub_type[]=trang-suc">Trang sức sơn mài</a></li>
                    </ul>
                </div>
                
                <div class="mega-card">
                    <img src="https://images.unsplash.com/photo-1610701596007-11502861dcfa?q=80&w=800&auto=format&fit=crop" alt="Bán chạy nhất">
                    <div class="mega-card-title">Bán chạy nhất</div>
                </div>
            </div>
        </div>
    </div>
    <a href="ve-chung-toi.php" class="nav-item">Về chúng tôi</a>
</div>
        
        <div class="nav-center">
            <a href="index.php" class="logo-text">Soul <em>&</em> Hands</a>
        </div>
        
        <div class="nav-right">
            <?php if ($is_logged_in): ?>
                <!-- Đã đăng nhập: Hiện tên + dropdown -->
                <div class="nav-user-wrapper">
                    <button class="nav-user-btn" id="user-menu-btn" aria-expanded="false">
                        <span class="nav-user-avatar">
                            <?= mb_substr($_SESSION['user_name'] ?? 'U', 0, 1, 'UTF-8') ?>
                        </span>
                        <span class="nav-user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
                        <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="nav-user-dropdown" id="user-menu-content">
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="admin/index.php" class="user-menu-item">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            Quản trị
                        </a>
                        <?php endif; ?>
                        <a href="orders.php" class="user-menu-item">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                            Đơn hàng của tôi
                        </a>
                        
                        <div class="user-menu-divider"></div>
                        <a href="logout.php" class="user-menu-item user-menu-logout">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Đăng xuất
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Chưa đăng nhập -->
                <a href="login.php" class="nav-item">Đăng nhập</a>
                <a href="register.php" class="nav-item">Đăng ký</a>
            <?php endif; ?>
            <a href="cart.php" class="cart-icon" aria-label="Giỏ hàng">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            </a>
        </div>
    </div>

    <div class="nav-bottom">
        <div class="search-bar" id="search-bar-wrap" style="position:relative;">
            <form method="GET" action="search.php" role="search" style="display:contents;">
                <?php $search_q = htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES); ?>
                <input
                    type="text" name="q" id="nav-search-input"
                    placeholder="Tìm kiếm sản phẩm độc bản..."
                    class="search-input" autocomplete="off"
                    value="<?= $search_q ?>"
                    aria-label="Tìm kiếm"
                >
                <button type="submit" class="search-btn" aria-label="Tìm kiếm">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </button>
            </form>
            <div id="nav-search-suggestions" class="nav-search-dropdown"></div>
        </div>
        <ul class="sub-categories">
            <li><a href="gom-su.php">Gốm sứ</a></li>
            <li><a href="go-dieu-khac.php">Gỗ điêu khắc</a></li>
            <li><a href="may-tre.php">Mây tre</a></li>
            <li><a href="son-mai.php">Sơn mài</a></li>
            <li><a href="sale.php" class="sale-link">SALE</a></li>
        </ul>
    </div>
</nav>