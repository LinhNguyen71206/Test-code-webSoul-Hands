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
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,600&family=DM+Mono:wght@300;400&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/category.css">

    <?php
    // Truyền trạng thái đăng nhập sang JS
    $is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    ?>
    <script>
        window.SH_USER = {
            loggedIn: <?= $is_logged_in ? 'true' : 'false' ?>,
            name: <?= $is_logged_in ? json_encode($_SESSION['user_name'] ?? '') : 'null' ?>
        };
    </script>
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
    <a href="workshop.php" class="nav-item" style="color: #D85A30; font-weight: 700;">Đặt lịch Workshop</a>
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
                        <a href="my-workshops.php" class="user-menu-item">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            Lịch Workshop của tôi
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

<div class="mobile-menu" id="mobileMenu">
    <ul>
        <li><a href="shop.php">Danh mục</a></li>
        <li><a href="workshop.php">Đặt lịch Workshop</a></li>
        <li><a href="ve-chung-toi.php">Về chúng tôi</a></li>
        <?php if ($is_logged_in): ?>
        <li><a href="orders.php">Đơn hàng của tôi</a></li>
        <li><a href="logout.php">Đăng xuất</a></li>
        <?php else: ?>
        <li><a href="login.php">Đăng nhập</a></li>
        <li><a href="register.php">Đăng ký</a></li>
        <?php endif; ?>
    </ul>
</div>

<style>
.nav-user-wrapper { position: relative; }

.nav-user-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: none;
    border: 1.5px solid var(--cream2, #E5E2D9);
    border-radius: 50px;
    padding: 6px 14px 6px 6px;
    cursor: pointer;
    font-family: var(--font-menu, sans-serif);
    font-size: 14px;
    font-weight: 500;
    color: var(--ink, #1A1C18);
    transition: border-color 0.2s, background 0.2s;
    white-space: nowrap;
}
.nav-user-btn:hover { border-color: var(--ink, #1A1C18); background: #F6F3EE; }

.nav-user-avatar {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: var(--rust, #D85A30);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700;
    flex-shrink: 0;
}

.nav-user-name { max-width: 120px; overflow: hidden; text-overflow: ellipsis; }

.nav-user-dropdown {
    position: absolute;
    top: calc(100% + 12px); right: 0;
    background: #fff;
    border: 1px solid var(--cream2, #E5E2D9);
    border-radius: 14px;
    box-shadow: 0 16px 40px rgba(0,0,0,0.1);
    min-width: 200px;
    padding: 8px 0;
    z-index: 200;
    opacity: 0; visibility: hidden; transform: translateY(8px);
    transition: all 0.2s ease;
}
.nav-user-dropdown.open { opacity: 1; visibility: visible; transform: translateY(0); }

.user-menu-item {
    display: flex; align-items: center; gap: 10px;
    padding: 11px 20px;
    font-family: var(--font-menu, sans-serif);
    font-size: 14px; color: var(--ink, #1A1C18);
    text-decoration: none;
    transition: background 0.15s;
}
.user-menu-item:hover { background: #F6F3EE; }
.user-menu-item svg { flex-shrink: 0; opacity: 0.6; }

.user-menu-divider { height: 1px; background: var(--cream2, #E5E2D9); margin: 6px 0; }

.user-menu-logout { color: #B91C1C; }
.user-menu-logout svg { opacity: 1; }
.user-menu-logout:hover { background: #FEF2F2; }

/* ── Live search suggestion dropdown ── */
.nav-search-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    left: 0; right: 0;
    background: #fff;
    border: 1px solid var(--cream2, #E5E2D9);
    border-radius: 14px;
    box-shadow: 0 16px 48px rgba(0,0,0,0.12);
    z-index: 500;
    overflow: hidden;
    display: none;
    max-height: 420px;
    overflow-y: auto;
}
.nav-search-dropdown.open { display: block; }

.nsd-section-label {
    padding: 10px 18px 6px;
    font-family: var(--font-mono, monospace);
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1.5px;
    color: var(--ink3, #888);
    text-transform: uppercase;
    background: #FAFAF8;
    border-bottom: 1px solid var(--cream2, #E5E2D9);
}
.nsd-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 18px;
    text-decoration: none;
    color: var(--ink, #2C2C2A);
    transition: background 0.15s;
    cursor: pointer;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    font-family: var(--font-menu, sans-serif);
}
.nsd-item:hover, .nsd-item.focused { background: #F6F3EE; }
.nsd-item-img {
    width: 36px; height: 36px;
    border-radius: 6px;
    object-fit: cover;
    flex-shrink: 0;
    background: var(--cream3, #E8E5DC);
}
.nsd-item-img-placeholder {
    width: 36px; height: 36px;
    border-radius: 6px;
    background: var(--cream3, #E8E5DC);
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
}
.nsd-item-info { flex: 1; min-width: 0; }
.nsd-item-name {
    font-size: 14px;
    font-weight: 500;
    color: var(--ink, #2C2C2A);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.nsd-item-name mark {
    background: #FAECE7;
    color: var(--rust, #D85A30);
    border-radius: 3px;
    padding: 0 2px;
    font-weight: 700;
}
.nsd-item-meta {
    font-size: 11px;
    color: var(--ink3, #888);
    margin-top: 2px;
    font-family: var(--font-mono, monospace);
}
.nsd-item-price {
    font-family: var(--font-menu, sans-serif);
    font-size: 13px;
    font-weight: 700;
    color: var(--ink, #2C2C2A);
    white-space: nowrap;
    flex-shrink: 0;
}
.nsd-footer {
    padding: 12px 18px;
    border-top: 1px solid var(--cream2, #E5E2D9);
    background: #FAFAF8;
    display: flex;
    justify-content: center;
}
.nsd-footer-link {
    font-family: var(--font-menu, sans-serif);
    font-size: 13px;
    font-weight: 600;
    color: var(--rust, #D85A30);
    transition: color 0.2s;
    text-decoration: none;
}
.nsd-footer-link:hover { color: var(--rust-dark, #993C1D); }
.nsd-loading {
    padding: 24px 18px;
    text-align: center;
    font-family: var(--font-menu, sans-serif);
    font-size: 13px;
    color: var(--ink3, #888);
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
@keyframes nsd-spin { to { transform: rotate(360deg); } }
.nsd-spinner {
    width: 14px; height: 14px;
    border: 2px solid var(--cream2, #E5E2D9);
    border-top-color: var(--rust, #D85A30);
    border-radius: 50%;
    animation: nsd-spin 0.6s linear infinite;
    flex-shrink: 0;
}
.nsd-empty {
    padding: 20px 18px;
    text-align: center;
    font-family: var(--font-menu, sans-serif);
    font-size: 13px;
    color: var(--ink3, #888);
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('user-menu-btn');
        var menu = document.getElementById('user-menu-content');
        if (!btn || !menu) return;
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var isOpen = menu.classList.toggle('open');
            btn.setAttribute('aria-expanded', String(isOpen));
        });
        document.addEventListener('click', function(e) {
            if (!menu.contains(e.target) && !btn.contains(e.target)) {
                menu.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { menu.classList.remove('open'); }
        });
    });
})();
</script>

<script>
/* ============================================================
   LIVE SEARCH — Gọi search_suggest.php để lấy gợi ý
============================================================ */
(function() {
    const input   = document.getElementById('nav-search-input');
    const dropdown= document.getElementById('nav-search-suggestions');
    if (!input || !dropdown) return;

    let timer     = null;
    let lastQuery = '';
    let focusIdx  = -1;

    function highlight(text, q) {
        if (!q) return escHtml(text);
        return escHtml(text).replace(
            new RegExp('(' + escRegex(escHtml(q)) + ')', 'gi'),
            '<mark>$1</mark>'
        );
    }
    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function escRegex(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
    function fmtMoney(n) { return Number(n).toLocaleString('vi-VN') + 'đ'; }

    function showLoading() {
        dropdown.innerHTML = '<div class="nsd-loading"><div class="nsd-spinner"></div> Đang tìm...</div>';
        dropdown.classList.add('open');
    }

    function close() {
        dropdown.classList.remove('open');
        focusIdx = -1;
    }

    function renderResults(data, q) {
        const { products = [], workshops = [] } = data;

        if (!products.length && !workshops.length) {
            dropdown.innerHTML = '<div class="nsd-empty">Không tìm thấy kết quả nào cho "<strong>' + escHtml(q) + '</strong>"</div>';
            dropdown.classList.add('open');
            return;
        }

        let html = '';

        if (products.length) {
            html += '<div class="nsd-section-label">Sản phẩm</div>';
            products.forEach(p => {
                const img = p.image_url
                    ? `<img class="nsd-item-img" src="${escHtml(p.image_url)}" alt="" loading="lazy">`
                    : `<div class="nsd-item-img-placeholder">🏺</div>`;
                html += `<a href="product.php?id=${p.id}" class="nsd-item">
                    ${img}
                    <div class="nsd-item-info">
                        <div class="nsd-item-name">${highlight(p.name, q)}</div>
                        <div class="nsd-item-meta">${escHtml(p.category_name ?? '')}</div>
                    </div>
                    <span class="nsd-item-price">${fmtMoney(p.price)}</span>
                </a>`;
            });
        }

        if (workshops.length) {
            html += '<div class="nsd-section-label">Workshop</div>';
            workshops.forEach(w => {
                html += `<a href="workshop.php?ws_id=${w.id}" class="nsd-item">
                    <div class="nsd-item-img-placeholder">🎨</div>
                    <div class="nsd-item-info">
                        <div class="nsd-item-name">${highlight(w.name, q)}</div>
                        <div class="nsd-item-meta">${escHtml(w.duration_info ?? '')}</div>
                    </div>
                    <span class="nsd-item-price">${fmtMoney(w.price)}</span>
                </a>`;
            });
        }

        html += `<div class="nsd-footer">
            <a href="search.php?q=${encodeURIComponent(q)}" class="nsd-footer-link">
                Xem tất cả kết quả cho "${escHtml(q)}" →
            </a>
        </div>`;

        dropdown.innerHTML = html;
        dropdown.classList.add('open');
        focusIdx = -1;
    }

    function fetchSuggestions(q) {
        if (q === lastQuery) return;
        lastQuery = q;

        if (q.length < 2) { close(); return; }

        showLoading();

        fetch('search_suggest.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => { if (input.value.trim() === q) renderResults(data, q); })
            .catch(() => { close(); });
    }

    // Keyboard nav
    function moveFocus(dir) {
        const items = dropdown.querySelectorAll('.nsd-item');
        if (!items.length) return;
        items[focusIdx]?.classList.remove('focused');
        focusIdx = (focusIdx + dir + items.length + 1) % (items.length + 1) - (dir < 0 ? 0 : 0);
        focusIdx = Math.max(-1, Math.min(focusIdx + dir, items.length - 1));
        if (focusIdx >= 0) {
            items[focusIdx].classList.add('focused');
            items[focusIdx].scrollIntoView({ block: 'nearest' });
        }
    }

    input.addEventListener('input', function() {
        const q = this.value.trim();
        clearTimeout(timer);
        if (!q) { close(); lastQuery = ''; return; }
        timer = setTimeout(() => fetchSuggestions(q), 220);
    });

    input.addEventListener('keydown', function(e) {
        if (!dropdown.classList.contains('open')) return;
        if (e.key === 'ArrowDown')  { e.preventDefault(); moveFocus(1); }
        if (e.key === 'ArrowUp')    { e.preventDefault(); moveFocus(-1); }
        if (e.key === 'Escape')     { close(); this.blur(); }
        if (e.key === 'Enter' && focusIdx >= 0) {
            e.preventDefault();
            dropdown.querySelectorAll('.nsd-item')[focusIdx]?.click();
        }
    });

    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) close();
    });

    // Re-open khi focus lại nếu đã có query
    input.addEventListener('focus', function() {
        const q = this.value.trim();
        if (q.length >= 2 && dropdown.innerHTML) dropdown.classList.add('open');
    });
})();
</script>