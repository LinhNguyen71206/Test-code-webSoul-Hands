<?php
// ============================================================
// search.php — Trang kết quả tìm kiếm
// ============================================================
ob_start();
session_start();
require_once 'config/database.php';

// ── Từ khoá ──────────────────────────────────────────────────
$q      = trim($_GET['q'] ?? '');
$sort   = $_GET['sort'] ?? 'popular';
$type   = $_GET['type'] ?? 'all';   // all | product | workshop

$sort_map = [
    'popular'    => 'p.id DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'newest'     => 'p.id DESC',
];
$order_by = $sort_map[$sort] ?? $sort_map['popular'];

$sort_labels = [
    'popular'    => 'Phổ biến nhất',
    'price_asc'  => 'Giá: Thấp → Cao',
    'price_desc' => 'Giá: Cao → Thấp',
    'newest'     => 'Mới nhất',
];

// ── Query sản phẩm ────────────────────────────────────────────
$products   = [];
$total_prod = 0;
if (!empty($q) && in_array($type, ['all', 'product'])) { // chỉ tìm sản phẩm khi có query và type phù hợp
    $kw   = '%' . $q . '%';
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?
        ORDER BY $order_by
    ");
    $stmt->bind_param('sss', $kw, $kw, $kw);
    $stmt->execute();
    $products   = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $total_prod = count($products);
}

// ── Query workshop ────────────────────────────────────────────
$workshops   = [];
$total_ws    = 0;
if (!empty($q) && in_array($type, ['all', 'workshop'])) {
    $kw   = '%' . $q . '%';
    $stmt = $conn->prepare("
        SELECT * FROM workshops
        WHERE name LIKE ? OR duration_info LIKE ?
        ORDER BY id DESC
    ");
    $stmt->bind_param('ss', $kw, $kw);
    $stmt->execute();
    $workshops = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $total_ws  = count($workshops);
}

$total_all = $total_prod + $total_ws;

// ── Ảnh fallback workshop ─────────────────────────────────────
$ws_imgs = [
    1 => 'https://sf-static.upanhlaylink.com/img/image_20260510a80aac45cbed8b6d1060d0f71f954605.jpg',
    2 => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?q=80&w=900&auto=format&fit=crop',
    3 => 'https://sf-static.upanhlaylink.com/img/image_202605104bd5e177a488151c41e99481bd8069a9.jpg',
];

// ── URL helper ────────────────────────────────────────────────
function search_url($q, $type = 'all', $sort = 'popular') { 
    $p = ['q' => $q];
    if ($type !== 'all')     $p['type'] = $type;
    if ($sort !== 'popular') $p['sort'] = $sort;
    return 'search.php?' . http_build_query($p);
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* ============================================================
   TRANG TÌM KIẾM
============================================================ */
.srp-page {
    max-width: var(--max-w);
    margin: 0 auto;
    padding: 40px 24px 80px;
}

/* ── Header kết quả ── */
.srp-head {
    margin-bottom: 32px;
    padding-bottom: 28px;
    border-bottom: 1px solid var(--cream2);
}

.srp-eyebrow {
    font-family: var(--font-mono);
    font-size: 11px;
    letter-spacing: 2px;
    color: var(--rust);
    text-transform: uppercase;
    margin-bottom: 10px;
}

.srp-title {
    font-family: var(--font-display);
    font-size: 44px;
    font-weight: 500;
    color: var(--ink);
    letter-spacing: -1px;
    line-height: 1.1;
    margin-bottom: 4px;
}
.srp-title em {
    font-style: italic;
    color: var(--rust);
}

.srp-count {
    font-family: var(--font-menu);
    font-size: 14px;
    color: var(--ink3);
    margin-top: 8px;
}

/* ── Thanh lọc + sort ── */
.srp-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
    gap: 16px;
    flex-wrap: wrap;
}

.srp-type-tabs {
    display: flex;
    gap: 6px;
}

.srp-tab {
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
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
}
.srp-tab:hover { border-color: var(--ink); color: var(--ink); }
.srp-tab.active { background: var(--ink); border-color: var(--ink); color: #fff; }
.srp-tab-count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 18px; height: 18px; padding: 0 4px;
    border-radius: 9px; font-size: 11px; font-weight: 700;
    background: rgba(255,255,255,0.2);
}
.srp-tab:not(.active) .srp-tab-count { background: var(--cream3); color: var(--ink3); }

/* Sort dropdown */
.srp-sort-wrap { position: relative; }
.srp-sort-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 18px; border-radius: 50px;
    background: var(--ink); border: 1.5px solid var(--ink);
    color: #fff; font-family: var(--font-menu); font-size: 13px; font-weight: 500;
    cursor: pointer; transition: 0.2s; white-space: nowrap;
}
.srp-sort-btn:hover { background: #000; }
.srp-sort-menu {
    position: absolute; top: calc(100% + 10px); right: 0;
    background: #fff; border: 1px solid var(--cream2);
    border-radius: 12px; box-shadow: 0 16px 40px rgba(0,0,0,0.1);
    width: 220px; padding: 10px 0; z-index: 100;
    opacity: 0; visibility: hidden; transform: translateY(8px);
    transition: all 0.2s;
}
.srp-sort-menu.open { opacity: 1; visibility: visible; transform: translateY(0); }
.srp-sort-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 11px 20px; font-family: var(--font-menu); font-size: 14px;
    color: var(--ink); cursor: pointer; transition: 0.15s; text-decoration: none;
}
.srp-sort-item:hover { background: #F6F3EE; }
.srp-sort-item.active { font-weight: 700; }

/* ── Section tiêu đề (Sản phẩm / Workshop) ── */
.srp-section { margin-bottom: 48px; }
.srp-section-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--cream2);
}
.srp-section-label {
    font-family: var(--font-mono);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 2px;
    color: var(--ink3);
    text-transform: uppercase;
}
.srp-section-count {
    font-family: var(--font-menu);
    font-size: 13px;
    color: var(--ink3);
    font-weight: 400;
}
.srp-section-link {
    margin-left: auto;
    font-family: var(--font-menu);
    font-size: 13px;
    color: var(--rust);
    font-weight: 600;
    transition: color 0.2s;
}
.srp-section-link:hover { color: var(--rust-dark); }

/* ── Grid sản phẩm ── */
.srp-products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

/* ── Workshop cards ── */
.srp-workshops-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.srp-ws-card {
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
    border: 1.5px solid var(--cream2);
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
}
.srp-ws-card:hover {
    border-color: #aaa;
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}
.srp-ws-img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    display: block;
}
.srp-ws-body {
    padding: 16px 18px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.srp-ws-name {
    font-family: var(--font-display);
    font-size: 19px;
    font-weight: 500;
    color: var(--ink);
    line-height: 1.3;
}
.srp-ws-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.srp-ws-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-family: var(--font-menu);
    font-size: 12px;
    color: var(--ink3);
}
.srp-ws-price {
    font-family: var(--font-display);
    font-size: 20px;
    font-weight: 600;
    color: var(--ink);
    margin-top: auto;
    padding-top: 8px;
    border-top: 1px solid var(--cream2);
}
.srp-ws-book-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    font-family: var(--font-menu);
    font-size: 12px;
    font-weight: 600;
    color: var(--rust);
    transition: gap 0.2s;
}
.srp-ws-card:hover .srp-ws-book-btn { gap: 10px; }

/* ── Empty state ── */
.srp-empty {
    text-align: center;
    padding: 80px 20px;
    background: #fff;
    border: 1.5px dashed var(--cream2);
    border-radius: 20px;
}
.srp-empty-icon { font-size: 52px; margin-bottom: 16px; }
.srp-empty-title {
    font-family: var(--font-display);
    font-size: 30px;
    font-weight: 500;
    color: var(--ink);
    margin-bottom: 8px;
}
.srp-empty-desc {
    font-family: var(--font-menu);
    font-size: 15px;
    color: var(--ink2);
    margin-bottom: 28px;
    line-height: 1.7;
}

/* ── Gợi ý tìm kiếm (khi q trống) ── */
.srp-suggestions {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-top: 8px;
}
.srp-suggest-card {
    border-radius: 14px;
    overflow: hidden;
    position: relative;
    height: 180px;
    cursor: pointer;
    text-decoration: none;
    display: block;
    transition: transform 0.2s;
}
.srp-suggest-card:hover { transform: translateY(-3px); }
.srp-suggest-card img { width: 100%; height: 100%; object-fit: cover; }
.srp-suggest-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(26,28,24,0.75) 0%, transparent 55%);
}
.srp-suggest-label {
    position: absolute;
    bottom: 14px; left: 16px;
    font-family: var(--font-menu);
    font-size: 14px;
    font-weight: 600;
    color: #fff;
}
.srp-suggest-count {
    position: absolute;
    top: 12px; right: 12px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(4px);
    border-radius: 50px;
    padding: 3px 10px;
    font-family: var(--font-mono);
    font-size: 10px;
    color: #fff;
    letter-spacing: 1px;
}

/* ── Keyword highlight ── */
.srp-highlight { color: var(--rust); font-weight: 600; }

/* ── Ẩn sort khi không có kết quả sản phẩm ── */
.srp-sort-wrap.hidden { display: none; }
</style>

<main class="srp-page">

    <?php if (empty($q)): ?>
    <!-- ======================================================
         TRẠNG THÁI TRỐNG — chưa nhập từ khoá
    ====================================================== -->
    <div class="srp-head">
        <div class="srp-eyebrow">Khám phá</div>
        <h1 class="srp-title">Tìm kiếm sản phẩm</h1>
        <p class="srp-count">Nhập từ khoá để tìm kiếm sản phẩm thủ công và workshop</p>
    </div>

    <div class="srp-section">
        <div class="srp-section-header">
            <span class="srp-section-label">Danh mục phổ biến</span>
        </div>
        <div class="srp-suggestions">
            <a href="gom-su.php" class="srp-suggest-card">
                <img src="https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?q=80&w=600&auto=format&fit=crop" alt="Gốm sứ">
                <div class="srp-suggest-overlay"></div>
                <div class="srp-suggest-label">Gốm sứ</div>
                <div class="srp-suggest-count">Thủ công</div>
            </a>
            <a href="go-dieu-khac.php" class="srp-suggest-card">
                <img src="https://images.unsplash.com/photo-1600585152220-90363fe7e115?q=80&w=600&auto=format&fit=crop" alt="Gỗ điêu khắc">
                <div class="srp-suggest-overlay"></div>
                <div class="srp-suggest-label">Gỗ điêu khắc</div>
                <div class="srp-suggest-count">Độc bản</div>
            </a>
            <a href="may-tre.php" class="srp-suggest-card">
                <img src="https://images.unsplash.com/photo-1610701596007-11502861dcfa?q=80&w=600&auto=format&fit=crop" alt="Mây tre">
                <div class="srp-suggest-overlay"></div>
                <div class="srp-suggest-label">Mây tre</div>
                <div class="srp-suggest-count">Truyền thống</div>
            </a>
            <a href="son-mai.php" class="srp-suggest-card">
                <img src="https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?q=80&w=600&auto=format&fit=crop" alt="Sơn mài">
                <div class="srp-suggest-overlay"></div>
                <div class="srp-suggest-label">Sơn mài</div>
                <div class="srp-suggest-count">Nghệ thuật</div>
            </a>
        </div>
    </div>

    <?php elseif ($total_all === 0): ?>
    <!-- ======================================================
         KHÔNG TÌM THẤY
    ====================================================== -->
    <div class="srp-head">
        <div class="srp-eyebrow">Kết quả tìm kiếm</div>
        <h1 class="srp-title">
            Không tìm thấy <em>"<?= htmlspecialchars($q) ?>"</em>
        </h1>
        <p class="srp-count">0 kết quả phù hợp</p>
    </div>

    <div class="srp-empty">
        <div class="srp-empty-icon">🔍</div>
        <h2 class="srp-empty-title">Không có kết quả</h2>
        <p class="srp-empty-desc">
            Không tìm thấy sản phẩm nào với từ khoá <strong>"<?= htmlspecialchars($q) ?>"</strong>.<br>
            Hãy thử từ khoá khác hoặc khám phá các danh mục bên dưới.
        </p>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
            <a href="gom-su.php" class="btn-primary">Xem Gốm sứ</a>
            <a href="workshop.php" class="btn-outline" style="border-color:var(--ink);color:var(--ink);">Xem Workshop</a>
        </div>
    </div>

    <?php else: ?>
    <!-- ======================================================
         CÓ KẾT QUẢ
    ====================================================== -->

    <!-- Header -->
    <div class="srp-head">
        <div class="srp-eyebrow">Kết quả tìm kiếm</div>
        <h1 class="srp-title">
            <?php
            // Highlight từ khoá trong tiêu đề
            $safe_q = htmlspecialchars($q);
            ?>
            Kết quả cho <em>"<?= $safe_q ?>"</em>
        </h1>
        <p class="srp-count">
            Tìm thấy <?= $total_all ?> kết quả
            <?php if ($type !== 'all'): ?>
                trong <?= $type === 'product' ? 'sản phẩm' : 'workshop' ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Toolbar: Tabs + Sort -->
    <div class="srp-toolbar">

        <!-- Tabs lọc loại -->
        <div class="srp-type-tabs">
            <a href="<?= htmlspecialchars(search_url($q, 'all', $sort)) ?>"
               class="srp-tab <?= $type === 'all' ? 'active' : '' ?>">
                Tất cả
                <span class="srp-tab-count"><?= $total_all ?></span>
            </a>
            <?php if ($total_prod > 0): ?>
            <a href="<?= htmlspecialchars(search_url($q, 'product', $sort)) ?>"
               class="srp-tab <?= $type === 'product' ? 'active' : '' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Sản phẩm
                <span class="srp-tab-count"><?= $total_prod ?></span>
            </a>
            <?php endif; ?>
            <?php if ($total_ws > 0): ?>
            <a href="<?= htmlspecialchars(search_url($q, 'workshop', $sort)) ?>"
               class="srp-tab <?= $type === 'workshop' ? 'active' : '' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Workshop
                <span class="srp-tab-count"><?= $total_ws ?></span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Sort (chỉ hiện khi có sản phẩm) -->
        <?php if ($total_prod > 0 && $type !== 'workshop'): ?>
        <div class="srp-sort-wrap">
            <button class="srp-sort-btn" id="srp-sort-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="9" y1="18" x2="15" y2="18"/></svg>
                <?= htmlspecialchars($sort_labels[$sort] ?? 'Sắp xếp') ?>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="srp-sort-menu" id="srp-sort-menu">
                <?php foreach ($sort_labels as $key => $label): ?>
                <a href="<?= htmlspecialchars(search_url($q, $type, $key)) ?>"
                   class="srp-sort-item <?= $sort === $key ? 'active' : '' ?>">
                    <?= htmlspecialchars($label) ?>
                    <?php if ($sort === $key): ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ======================================================
         KẾT QUẢ SẢN PHẨM
    ====================================================== -->
    <?php if (!empty($products) && $type !== 'workshop'): ?>
    <div class="srp-section">
        <?php if ($type === 'all'): ?>
        <div class="srp-section-header">
            <span class="srp-section-label">Sản phẩm</span>
            <span class="srp-section-count"><?= $total_prod ?> kết quả</span>
            <?php if ($total_ws > 0): ?>
            <a href="<?= htmlspecialchars(search_url($q, 'product', $sort)) ?>" class="srp-section-link">
                Xem tất cả sản phẩm →
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="srp-products-grid">
            <?php
            $display_products = ($type === 'all') ? array_slice($products, 0, 8) : $products;
            foreach ($display_products as $row):
                // Highlight từ khoá trong tên sản phẩm
                $highlighted_name = preg_replace(
                    '/(' . preg_quote(htmlspecialchars($q), '/') . ')/iu',
                    '<mark style="background:#FAECE7;color:var(--rust);border-radius:3px;padding:0 2px;">$1</mark>',
                    htmlspecialchars($row['name'])
                );
            ?>
            <div class="product-card">
                <div class="product-img" onclick="location.href='product.php?id=<?= $row['id'] ?>'">
                    <?php if (!empty($row['image_url'])): ?>
                        <img src="<?= htmlspecialchars($row['image_url']) ?>"
                             alt="<?= htmlspecialchars($row['name']) ?>"
                             class="real-product-img">
                    <?php else: ?>
                        <div class="fallback-img <?= htmlspecialchars($row['image_class'] ?? '') ?>"></div>
                    <?php endif; ?>

                    <?php if (!empty($row['badge'])): ?>
                        <div class="product-badge <?= $row['badge'] === 'Mới' ? 'new-badge' : '' ?>">
                            <?= htmlspecialchars($row['badge']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="price-floating-badge">
                        <?= number_format($row['price'], 0, ',', '.') ?>đ
                    </div>
                </div>
                <div class="product-body">
                    <h3 class="product-name" onclick="location.href='product.php?id=<?= $row['id'] ?>'">
                        <?= $highlighted_name ?>
                    </h3>
                    <?php if (!empty($row['category_name'])): ?>
                    <div style="font-family:var(--font-mono);font-size:10px;color:var(--ink3);letter-spacing:1px;text-transform:uppercase;margin-bottom:8px;">
                        <?= htmlspecialchars($row['category_name']) ?>
                    </div>
                    <?php endif; ?>
                    <div class="product-actions">
                        <button class="btn-add-cart"
                            data-id="<?= $row['id'] ?>"
                            data-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>"
                            data-price="<?= (int)$row['price'] ?>"
                            data-image="<?= htmlspecialchars($row['image_url'] ?? '', ENT_QUOTES) ?>">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            Giỏ hàng
                        </button>
                        <button class="btn-buy-now"
                            data-id="<?= $row['id'] ?>"
                            data-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>"
                            data-price="<?= (int)$row['price'] ?>"
                            data-image="<?= htmlspecialchars($row['image_url'] ?? '', ENT_QUOTES) ?>">
                            Mua ngay
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($type === 'all' && $total_prod > 8): ?>
        <div style="text-align:center;margin-top:24px;">
            <a href="<?= htmlspecialchars(search_url($q, 'product', $sort)) ?>"
               class="btn-primary">
                Xem thêm <?= $total_prod - 8 ?> sản phẩm →
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ======================================================
         KẾT QUẢ WORKSHOP
    ====================================================== -->
    <?php if (!empty($workshops) && $type !== 'product'): ?>
    <div class="srp-section">
        <?php if ($type === 'all'): ?>
        <div class="srp-section-header">
            <span class="srp-section-label">Workshop</span>
            <span class="srp-section-count"><?= $total_ws ?> kết quả</span>
            <?php if ($total_prod > 0): ?>
            <a href="<?= htmlspecialchars(search_url($q, 'workshop')) ?>" class="srp-section-link">
                Xem tất cả workshop →
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="srp-workshops-grid">
            <?php foreach ($workshops as $ws):
                $ws_img = $ws_imgs[(int)$ws['id']] ?? $ws_imgs[1];
            ?>
            <a href="workshop.php?ws_id=<?= $ws['id'] ?>" class="srp-ws-card">
                <img src="<?= $ws_img ?>" alt="<?= htmlspecialchars($ws['name']) ?>" class="srp-ws-img">
                <div class="srp-ws-body">
                    <div class="srp-ws-name"><?= htmlspecialchars($ws['name']) ?></div>
                    <div class="srp-ws-meta">
                        <div class="srp-ws-meta-item">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?= htmlspecialchars($ws['duration_info']) ?>
                        </div>
                    </div>
                    <div class="srp-ws-price"><?= number_format($ws['price'], 0, ',', '.') ?>đ / người</div>
                    <span class="srp-ws-book-btn">
                        Đặt lịch ngay →
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; // end has results ?>

</main>

<script>
// Sort dropdown
(function() {
    const btn  = document.getElementById('srp-sort-btn');
    const menu = document.getElementById('srp-sort-menu');
    if (!btn || !menu) return;

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        menu.classList.toggle('open');
    });
    document.addEventListener('click', function(e) {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove('open');
        }
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') menu.classList.remove('open');
    });
})();
</script>

<?php include 'includes/footer.php'; ?>