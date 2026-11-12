<?php
// ============================================================
// shop.php — Tất cả sản phẩm (lọc theo danh mục, sắp xếp, phân trang)
// ============================================================
require_once 'config/database.php';
require_once 'includes/product-card.php';

$per_page = 12;

// ── Danh mục (lấy từ DB, dùng làm whitelist cho bộ lọc) ───────
$categories = $conn->query("SELECT id, name, slug FROM categories ORDER BY id ASC")
                   ->fetch_all(MYSQLI_ASSOC);

$cat_pages = [
    'gom-su'       => 'gom-su.php',
    'go-dieu-khac' => 'go-dieu-khac.php',
    'may-tre'      => 'may-tre.php',
    'son-mai'      => 'son-mai.php',
];

// Đếm sản phẩm theo danh mục
$cat_counts = [];
$total_all  = 0;
$res = $conn->query("SELECT category_id, COUNT(*) AS cnt FROM products GROUP BY category_id");
while ($r = $res->fetch_assoc()) {
    $cat_counts[(int)$r['category_id']] = (int)$r['cnt'];
    $total_all += (int)$r['cnt'];
}

// ── Tham số lọc / sắp xếp / trang ─────────────────────────────
$cat_slug   = $_GET['cat'] ?? '';
$active_cat = null;
foreach ($categories as $c) {
    if ($c['slug'] === $cat_slug) { $active_cat = $c; break; }
}
if (!$active_cat) $cat_slug = '';

$sort_map = [
    'popular'    => 'p.id DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'newest'     => 'p.created_at DESC',
];
$sort_labels = [
    'popular'    => 'Sắp Xếp',
    'price_asc'  => 'Giá: Từ Thấp Đến Cao',
    'price_desc' => 'Giá: Từ Cao Đến Thấp',
    'newest'     => 'Hàng Mới Về',
];
$sort = $_GET['sort'] ?? 'popular';
if (!isset($sort_map[$sort])) $sort = 'popular';
$order_by      = $sort_map[$sort];
$current_label = $sort_labels[$sort];

$page = max(1, (int)($_GET['page'] ?? 1));

// ── Đếm tổng + lấy sản phẩm ───────────────────────────────────
$total_items = $active_cat ? ($cat_counts[(int)$active_cat['id']] ?? 0) : $total_all;
$total_pages = max(1, (int)ceil($total_items / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

if ($active_cat) {
    $stmt = $conn->prepare("
        SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = ?
        ORDER BY $order_by
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->bind_param('i', $active_cat['id']);
} else {
    $stmt = $conn->prepare("
        SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        ORDER BY $order_by
        LIMIT $per_page OFFSET $offset
    ");
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── URL helper ────────────────────────────────────────────────
function shop_url($cat = '', $sort = 'popular', $page = 1) {
    $p = [];
    if ($cat !== '')         $p['cat']  = $cat;
    if ($sort !== 'popular') $p['sort'] = $sort;
    if ($page > 1)           $p['page'] = $page;
    return 'shop.php' . ($p ? '?' . http_build_query($p) : '');
}

include 'includes/header.php';
?>

<style>
/* ============================================================
   SHOP — TẤT CẢ SẢN PHẨM
============================================================ */
.shop-hero {
    background: var(--ink);
    padding: 72px 40px 64px;
    border-bottom: 0.5px solid #444441;
}
.shop-hero-inner { max-width: 1440px; margin: 0 auto; }
.shop-hero-title {
    font-family: var(--font-display);
    font-size: 64px;
    font-weight: 500;
    color: var(--cream);
    letter-spacing: -1px;
    line-height: 1.1;
    margin: 8px 0 14px;
}
.shop-hero-title em { font-style: italic; color: var(--amber); }
.shop-hero-sub {
    font-family: var(--font-menu);
    font-size: 15px;
    color: var(--ink3);
    max-width: 520px;
    line-height: 1.7;
}

/* Thanh danh mục */
.shop-cats {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}
.shop-cat {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 20px;
    border-radius: 50px;
    font-family: var(--font-menu);
    font-size: 14px;
    font-weight: 500;
    border: 1.5px solid var(--cream2);
    background: #fff;
    color: var(--ink2);
    transition: all 0.2s;
}
.shop-cat:hover { border-color: var(--ink); color: var(--ink); }
.shop-cat.active { background: var(--ink); border-color: var(--ink); color: #fff; }
.shop-cat-count {
    font-size: 11px;
    font-weight: 700;
    padding: 1px 8px;
    border-radius: 9px;
    background: var(--page);
    color: var(--ink3);
}
.shop-cat.active .shop-cat-count { background: rgba(255,255,255,0.2); color: #fff; }

/* Phân trang */
.shop-pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 48px;
    flex-wrap: wrap;
}
.shop-page-link {
    min-width: 40px; height: 40px;
    padding: 0 14px;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 50px;
    border: 1.5px solid var(--cream2);
    background: #fff;
    font-family: var(--font-menu);
    font-size: 14px; font-weight: 600;
    color: var(--ink);
    transition: all 0.2s;
}
.shop-page-link:hover { border-color: var(--ink); }
.shop-page-link.active { background: var(--ink); border-color: var(--ink); color: #fff; }
.shop-page-link.disabled { opacity: 0.4; pointer-events: none; }

/* Trang shop dùng đủ 4 cột (không có sidebar lọc) */
.shop-grid-wrap .products-grid { grid-template-columns: repeat(4, 1fr); }
</style>

<main class="category-page">

    <!-- ========== HERO ========== -->
    <section class="shop-hero">
        <div class="shop-hero-inner">
            <div class="section-eyebrow light">Cửa hàng</div>
            <h1 class="shop-hero-title"><?= $active_cat ? htmlspecialchars($active_cat['name']) : 'Tất cả <em>tác phẩm</em>' ?></h1>
            <p class="shop-hero-sub">
                Gốm sứ, gỗ điêu khắc, mây tre và sơn mài — mỗi sản phẩm là một tác phẩm độc bản, được làm hoàn toàn bằng tay.
            </p>
        </div>
    </section>

    <!-- ========== KHU VỰC SẢN PHẨM ========== -->
    <section class="products-section shop-grid-wrap">

        <!-- Danh mục -->
        <div class="shop-cats">
            <a href="<?= htmlspecialchars(shop_url('', $sort)) ?>" class="shop-cat <?= $cat_slug === '' ? 'active' : '' ?>">
                Tất cả <span class="shop-cat-count"><?= $total_all ?></span>
            </a>
            <?php foreach ($categories as $c): ?>
            <a href="<?= htmlspecialchars(shop_url($c['slug'], $sort)) ?>" class="shop-cat <?= $cat_slug === $c['slug'] ? 'active' : '' ?>">
                <?= htmlspecialchars($c['name']) ?>
                <span class="shop-cat-count"><?= $cat_counts[(int)$c['id']] ?? 0 ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Toolbar -->
        <div class="products-toolbar">
            <div class="toolbar-left">
                <span class="item-count"><?= $total_items ?> mặt hàng</span>
                <?php if ($active_cat && isset($cat_pages[$active_cat['slug']])): ?>
                <a href="<?= $cat_pages[$active_cat['slug']] ?>" class="filter-clear-all" style="text-decoration:none;color:var(--rust);font-weight:600;">
                    Dùng bộ lọc chi tiết của <?= htmlspecialchars($active_cat['name']) ?> →
                </a>
                <?php endif; ?>
            </div>
            <div class="toolbar-right">
                <div class="sort-dropdown-wrapper">
                    <button class="toolbar-btn btn-sort" id="sort-btn">
                        <?= htmlspecialchars($current_label) ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="sort-menu" id="sort-menu">
                        <?php foreach ($sort_labels as $val => $label): ?>
                        <a href="<?= htmlspecialchars(shop_url($cat_slug, $val)) ?>"
                           class="sort-item <?= ($sort === $val) ? 'active' : '' ?>">
                            <?= htmlspecialchars($label) ?>
                            <?php if ($sort === $val): ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lưới sản phẩm -->
        <div class="products-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $row) render_product_card($row); ?>
            <?php else: ?>
                <div class="no-results">
                    <div class="no-results-icon">🏺</div>
                    <p>Chưa có sản phẩm nào trong mục này.</p>
                    <?php if ($cat_slug !== ''): ?>
                    <a href="shop.php" class="btn-primary" style="margin-top:16px;display:inline-flex;">Xem tất cả sản phẩm</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
        <nav class="shop-pagination" aria-label="Phân trang">
            <a href="<?= htmlspecialchars(shop_url($cat_slug, $sort, $page - 1)) ?>"
               class="shop-page-link <?= $page <= 1 ? 'disabled' : '' ?>">← Trước</a>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?= htmlspecialchars(shop_url($cat_slug, $sort, $i)) ?>"
               class="shop-page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <a href="<?= htmlspecialchars(shop_url($cat_slug, $sort, $page + 1)) ?>"
               class="shop-page-link <?= $page >= $total_pages ? 'disabled' : '' ?>">Sau →</a>
        </nav>
        <?php endif; ?>

    </section>
</main>

<?php include 'includes/footer.php'; ?>