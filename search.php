<?php
// ============================================================
// search.php — Trang kết quả tìm kiếm sản phẩm
// ============================================================
ob_start();
session_start();
require_once 'config/database.php';
require_once 'includes/product-card.php';

// ── Từ khoá ──────────────────────────────────────────────────
$q    = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'popular';

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
if (!empty($q)) {
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

// ── URL helper ────────────────────────────────────────────────
function search_url($q, $sort = 'popular') {
    $p = ['q' => $q];
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

/* ── Thanh sort ── */
.srp-toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    margin-bottom: 28px;
    gap: 16px;
    flex-wrap: wrap;
}

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

/* ── Section ── */
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

/* ── Grid sản phẩm ── */
.srp-products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

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

/* ── Gợi ý danh mục (khi q trống) ── */
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
</style>

<main class="srp-page">

    <?php if (empty($q)): ?>
    <!-- ======================================================
         TRẠNG THÁI TRỐNG — chưa nhập từ khoá
    ====================================================== -->
    <div class="srp-head">
        <div class="srp-eyebrow">Khám phá</div>
        <h1 class="srp-title">Tìm kiếm sản phẩm</h1>
        <p class="srp-count">Nhập từ khoá để tìm kiếm sản phẩm thủ công</p>
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

    <?php elseif ($total_prod === 0): ?>
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
            <a href="sale.php" class="btn-outline" style="border-color:var(--ink);color:var(--ink);">Xem ưu đãi</a>
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
            Kết quả cho <em>"<?= htmlspecialchars($q) ?>"</em>
        </h1>
        <p class="srp-count">Tìm thấy <?= $total_prod ?> sản phẩm</p>
    </div>

    <!-- Toolbar: Sort -->
    <div class="srp-toolbar">
        <div class="srp-sort-wrap">
            <button class="srp-sort-btn" id="srp-sort-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="9" y1="18" x2="15" y2="18"/></svg>
                <?= htmlspecialchars($sort_labels[$sort] ?? 'Sắp xếp') ?>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="srp-sort-menu" id="srp-sort-menu">
                <?php foreach ($sort_labels as $key => $label): ?>
                <a href="<?= htmlspecialchars(search_url($q, $key)) ?>"
                   class="srp-sort-item <?= $sort === $key ? 'active' : '' ?>">
                    <?= htmlspecialchars($label) ?>
                    <?php if ($sort === $key): ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ======================================================
         KẾT QUẢ SẢN PHẨM
    ====================================================== -->
    <div class="srp-section">
        <div class="srp-products-grid">
            <?php foreach ($products as $row):
                // Làm nổi từ khóa trong tên sản phẩm (đã escape trước khi chèn <mark>)
                $highlighted_name = preg_replace(
                    '/(' . preg_quote(htmlspecialchars($q), '/') . ')/iu',
                    '<mark style="background:#FAECE7;color:var(--rust);border-radius:3px;padding:0 2px;">$1</mark>',
                    htmlspecialchars($row['name'])
                );
                render_product_card($row, ['name_html' => $highlighted_name, 'show_category' => true]);
            endforeach; ?>
        </div>
    </div>

    <?php endif; // end has results ?>

</main>

<?php include 'includes/footer.php'; ?>