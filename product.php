<?php
ob_start();
require_once 'config/database.php';

// ── Lấy ID sản phẩm ──────────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// ── Lấy thông tin sản phẩm ───────────────────────────────────
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: index.php');
    exit;
}

// ── Map slug danh mục sang file PHP ──────────────────────────
$cat_page_map = [
    'gom-su'       => 'gom-su.php',
    'go-dieu-khac' => 'go-dieu-khac.php',
    'may-tre'      => 'may-tre.php',
    'son-mai'      => 'son-mai.php',
];
$cat_page = $cat_page_map[$product['category_slug']] ?? 'index.php';

// ── Sản phẩm liên quan (cùng danh mục, khác ID, tối đa 4) ────
$rel_stmt = $conn->prepare("
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = ? AND p.id != ?
    ORDER BY RAND()
    LIMIT 4
");
$rel_stmt->bind_param('ii', $product['category_id'], $id);
$rel_stmt->execute();
$related = $rel_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Nhãn badge ───────────────────────────────────────────────
$is_sale   = !empty($product['badge']) && str_contains($product['badge'], '%');
$is_new    = !empty($product['badge']) && $product['badge'] === 'Mới';

// ── Nhãn màu / kiểu / phong cách ─────────────────────────────
$color_labels = [
    'trang'=>'Trắng','nau'=>'Nâu','xanh'=>'Xanh','den'=>'Đen','hong'=>'Hồng','kem'=>'Kem',
    'nau-sang'=>'Nâu sáng','nau-dam'=>'Nâu đậm','tu-nhien'=>'Tự nhiên','vang-gold'=>'Vàng gold',
    'do-son'=>'Đỏ son','den-son'=>'Đen sơn','vang-nhung'=>'Vàng nhũ','xanh-co-vit'=>'Xanh cổ vịt','nau-canh-gian'=>'Nâu cánh gián',
    'kem-vang'=>'Kem vàng','nau-tre'=>'Nâu tre','trang-moc'=>'Trắng mộc','xanh-la'=>'Xanh lá','den-bong'=>'Đen bóng',
];
$style_labels = [
    'hien-dai'=>'Hiện đại','truyen-thong'=>'Truyền thống','toi-gian'=>'Tối giản','dan-gian'=>'Dân gian',
    'co-dien'=>'Cổ điển','phong-dong'=>'Phương Đông','thien-nhien'=>'Thiên nhiên','bohemian'=>'Bohemian',
];
$type_labels = [
    'binh-hoa'=>'Bình hoa & Lọ','am-tra'=>'Bộ ấm trà','chen-bat'=>'Chén bát','tuong'=>'Tượng trang trí',
    'tuong-nghe-thuat'=>'Tượng nghệ thuật','khay-dia'=>'Khay & Đĩa gỗ','do-trang-tri'=>'Đồ trang trí','do-dung'=>'Đồ dùng hàng ngày',
    'tranh-tuong'=>'Tranh treo tường','hop-khay'=>'Hộp & Khay','trang-suc'=>'Trang sức','gio-ro'=>'Giỏ & Rổ đan tay',
    'khay-may'=>'Khay khảm mây','den-long'=>'Đèn lồng mây tre','do-noi-that'=>'Đồ nội thất nhỏ',
];

$color_label = $color_labels[$product['color'] ?? ''] ?? null;
$style_label = $style_labels[$product['style'] ?? ''] ?? null;
$type_label  = $type_labels[$product['sub_type'] ?? ''] ?? null;

// ── Tính % giảm giá ──────────────────────────────────────────
$discount_pct = null;
if (!empty($product['old_price']) && $product['old_price'] > 0) {
    $discount_pct = round((1 - $product['price'] / $product['old_price']) * 100);
}
?>
<?php include 'includes/header.php'; ?>

<link rel="stylesheet" href="assets/css/category.css">

<style>
/* ============================================================
   PRODUCT DETAIL PAGE
============================================================ */

/* Breadcrumb */
.product-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 18px 40px;
    font-family: var(--font-mono);
    font-size: 11px;
    letter-spacing: 1px;
    color: var(--ink3);
    border-bottom: 1px solid var(--cream2);
}
.product-breadcrumb a {
    color: var(--ink3);
    transition: color 0.2s;
}
.product-breadcrumb a:hover { color: var(--rust); }
.breadcrumb-sep { opacity: 0.4; }
.breadcrumb-current { color: var(--ink); font-weight: 500; }

/* ── Main layout ── */
.product-detail-wrap {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 80vh;
    max-width: 1400px;
    margin: 0 auto;
    padding: 48px 40px 80px;
    gap: 80px;
    align-items: start;
}

/* ── Ảnh sản phẩm ── */
.product-gallery {
    position: sticky;
    top: 20px;
    align-self: start;
}

.product-main-img-wrap {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    background: #fff;
    aspect-ratio: 1 / 1;
    border: 1px solid var(--cream2);
}

.product-main-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.product-main-img-wrap:hover .product-main-img {
    transform: scale(1.04);
}

.product-main-img-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 96px;
    background: var(--cream3);
}

/* Badge góc ảnh */
.product-detail-badge {
    position: absolute;
    top: 20px;
    left: 20px;
    padding: 6px 14px;
    border-radius: 50px;
    font-family: var(--font-mono);
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
    z-index: 2;
}
.badge-sale {
    background: #E63946;
    color: #fff;
}
.badge-new {
    background: var(--ink);
    color: #fff;
}

/* Thẻ handmade góc ảnh */
.handmade-tag {
    position: absolute;
    bottom: 20px;
    right: 20px;
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(6px);
    border: 1px solid var(--cream2);
    border-radius: 50px;
    padding: 6px 14px;
    font-family: var(--font-mono);
    font-size: 10px;
    letter-spacing: 1.5px;
    color: var(--ink2);
    text-transform: uppercase;
    z-index: 2;
}
.handmade-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--rust);
    animation: pulse 2s ease-in-out infinite;
}

/* ── Info bên phải ── */
.product-info {
    padding-top: 8px;
}

.product-info-category {
    font-family: var(--font-mono);
    font-size: 11px;
    letter-spacing: 2.5px;
    color: var(--rust);
    text-transform: uppercase;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.product-info-category a {
    color: var(--rust);
    transition: color 0.2s;
}
.product-info-category a:hover { color: var(--rust-dark); }

.product-info-name {
    font-family: var(--font-display);
    font-size: 52px;
    font-weight: 500;
    color: var(--ink);
    line-height: 1.1;
    letter-spacing: -1px;
    margin-bottom: 24px;
}

/* Vùng giá */
.product-price-block {
    display: flex;
    align-items: baseline;
    gap: 16px;
    margin-bottom: 8px;
}
.product-price-current {
    font-family: var(--font-menu);
    font-size: 40px;
    font-weight: 700;
    color: var(--ink);
    letter-spacing: -1px;
}
.product-price-old {
    font-family: var(--font-menu);
    font-size: 20px;
    font-weight: 400;
    color: var(--ink3);
    text-decoration: line-through;
}
.product-save-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #FEF2F2;
    border: 1px solid #FECACA;
    color: #B91C1C;
    border-radius: 50px;
    padding: 4px 12px;
    font-family: var(--font-mono);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.product-save-note {
    font-family: var(--font-mono);
    font-size: 11px;
    color: var(--ink3);
    margin-bottom: 32px;
}

/* Divider */
.info-divider {
    height: 1px;
    background: var(--cream2);
    margin: 28px 0;
}

/* Tags đặc điểm */
.product-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 28px;
}
.product-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border: 1px solid var(--cream2);
    border-radius: 50px;
    font-family: var(--font-menu);
    font-size: 13px;
    color: var(--ink2);
    background: #fff;
}
.product-tag-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--cream2);
    flex-shrink: 0;
}
.tag-color .product-tag-dot { background: currentColor; border: 1px solid rgba(0,0,0,0.1); }

/* Mô tả */
.product-description {
    font-family: var(--font-menu);
    font-size: 15px;
    color: var(--ink2);
    line-height: 1.75;
    margin-bottom: 32px;
}
.product-description-placeholder {
    font-family: var(--font-menu);
    font-size: 15px;
    color: var(--ink3);
    line-height: 1.75;
    font-style: italic;
    margin-bottom: 32px;
}

/* Số lượng */
.qty-label {
    font-family: var(--font-menu);
    font-size: 13px;
    font-weight: 600;
    color: var(--ink);
    margin-bottom: 12px;
}

.qty-selector {
    display: inline-flex;
    align-items: center;
    border: 1.5px solid var(--cream2);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 24px;
    background: #fff;
}
.qty-btn {
    width: 44px;
    height: 44px;
    border: none;
    background: none;
    font-size: 20px;
    font-weight: 300;
    color: var(--ink);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
}
.qty-btn:hover { background: #F6F3EE; }
.qty-input {
    width: 52px;
    text-align: center;
    border: none;
    border-left: 1px solid var(--cream2);
    border-right: 1px solid var(--cream2);
    background: none;
    font-family: var(--font-menu);
    font-size: 16px;
    font-weight: 600;
    color: var(--ink);
    outline: none;
    height: 44px;
    -moz-appearance: textfield;
}
.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button { -webkit-appearance: none; }

/* CTA Buttons */
.product-cta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 24px;
}
.btn-detail-cart {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 16px 24px;
    border-radius: 12px;
    font-family: var(--font-menu);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: 1.5px solid var(--ink);
    background: #fff;
    color: var(--ink);
}
.btn-detail-cart:hover {
    background: var(--ink);
    color: #fff;
    transform: translateY(-1px);
}
.btn-detail-cart.added {
    background: #10B981;
    border-color: #10B981;
    color: #fff;
}

.btn-detail-buy {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 16px 24px;
    border-radius: 12px;
    font-family: var(--font-menu);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    background: var(--rust);
    color: #fff;
}
.btn-detail-buy:hover {
    background: var(--rust-dark);
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(216, 90, 48, 0.3);
}


/* ── Phần sản phẩm liên quan ── */
.related-section {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px 80px;
    border-top: 1px solid var(--cream2);
}

.related-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding-top: 56px;
    margin-bottom: 36px;
}
.related-title {
    font-family: var(--font-display);
    font-size: 40px;
    font-weight: 500;
    color: var(--ink);
    letter-spacing: -1px;
}
.related-see-all {
    font-family: var(--font-menu);
    font-size: 14px;
    color: var(--rust);
    font-weight: 600;
    transition: color 0.2s;
}
.related-see-all:hover { color: var(--rust-dark); }

.related-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 28px;
}

/* ── Thông tin kỹ thuật ── */
.product-specs {
    background: #fff;
    border: 1px solid var(--cream2);
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 28px;
}
.specs-title {
    font-family: var(--font-menu);
    font-size: 13px;
    font-weight: 700;
    color: var(--ink);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.specs-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid var(--cream2);
    font-family: var(--font-menu);
    font-size: 14px;
}
.specs-row:last-child { border-bottom: none; }
.specs-key { color: var(--ink3); }
.specs-val { color: var(--ink); font-weight: 500; }

/* Animate vào */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.product-gallery  { animation: fadeInUp 0.5s ease both; }
.product-info     { animation: fadeInUp 0.5s ease 0.1s both; }
</style>

<?php
// Tính lại session nếu cần
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<!-- ── Breadcrumb ─────────────────────────────────────────── -->
<nav class="product-breadcrumb">
    <a href="index.php">Trang chủ</a>
    <span class="breadcrumb-sep">›</span>
    <a href="<?= $cat_page ?>"><?= htmlspecialchars($product['category_name']) ?></a>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-current"><?= htmlspecialchars($product['name']) ?></span>
</nav>

<!-- ── Chi tiết sản phẩm ─────────────────────────────────── -->
<div class="product-detail-wrap">

    <!-- CỘT TRÁI: Ảnh -->
    <div class="product-gallery">
        <div class="product-main-img-wrap">
            <?php if (!empty($product['image_url'])): ?>
                <img src="<?= htmlspecialchars($product['image_url']) ?>"
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     class="product-main-img">
            <?php else: ?>
                <div class="product-main-img-placeholder">🏺</div>
            <?php endif; ?>

            <?php if ($is_sale): ?>
                <span class="product-detail-badge badge-sale"><?= htmlspecialchars($product['badge']) ?></span>
            <?php elseif ($is_new): ?>
                <span class="product-detail-badge badge-new">Mới về</span>
            <?php endif; ?>

            <div class="handmade-tag">
                <span class="handmade-dot"></span>
                Thủ công 100%
            </div>
        </div>
    </div>

    <!-- CỘT PHẢI: Thông tin -->
    <div class="product-info">

        <!-- Danh mục -->
        <div class="product-info-category">
            <a href="<?= $cat_page ?>"><?= htmlspecialchars($product['category_name']) ?></a>
            <span style="opacity:.3">·</span>
            <span>Nghệ nhân Bát Tràng</span>
        </div>

        <!-- Tên sản phẩm -->
        <h1 class="product-info-name"><?= htmlspecialchars($product['name']) ?></h1>

        <!-- Giá -->
        <div class="product-price-block">
            <span class="product-price-current"><?= number_format($product['price'], 0, ',', '.') ?>đ</span>
            <?php if (!empty($product['old_price']) && $product['old_price'] > 0): ?>
                <span class="product-price-old"><?= number_format($product['old_price'], 0, ',', '.') ?>đ</span>
                <?php if ($discount_pct): ?>
                    <span class="product-save-tag">-<?= $discount_pct ?>%</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php if (!empty($product['old_price']) && $product['old_price'] > 0): ?>
        <p class="product-save-note">
            Tiết kiệm <?= number_format($product['old_price'] - $product['price'], 0, ',', '.') ?>đ
        </p>
        <?php endif; ?>

        <div class="info-divider"></div>

        <!-- Tags -->
        <?php if ($color_label || $style_label || $type_label): ?>
        <div class="product-tags">
            <?php if ($type_label): ?>
            <span class="product-tag">
                <span class="product-tag-dot" style="background: var(--amber);"></span>
                <?= htmlspecialchars($type_label) ?>
            </span>
            <?php endif; ?>
            <?php if ($style_label): ?>
            <span class="product-tag">
                <span class="product-tag-dot" style="background: var(--rust);"></span>
                <?= htmlspecialchars($style_label) ?>
            </span>
            <?php endif; ?>
            <?php if ($color_label): ?>
            <span class="product-tag tag-color">
                <span class="product-tag-dot"></span>
                <?= htmlspecialchars($color_label) ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Mô tả -->
        <?php if (!empty($product['description'])): ?>
            <p class="product-description"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        <?php else: ?>
            <p class="product-description-placeholder">
                Sản phẩm thủ công độc bản, được tạo ra từ đôi bàn tay khéo léo của nghệ nhân làng nghề Việt Nam. Mỗi tác phẩm mang theo hồn riêng, là kết tinh của kỹ thuật truyền thống và tâm huyết người thợ.
            </p>
        <?php endif; ?>

        <!-- Thông số kỹ thuật -->
        <div class="product-specs">
            <div class="specs-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Thông tin sản phẩm
            </div>
            <div class="specs-row">
                <span class="specs-key">Danh mục</span>
                <span class="specs-val"><?= htmlspecialchars($product['category_name']) ?></span>
            </div>
            <?php if ($type_label): ?>
            <div class="specs-row">
                <span class="specs-key">Loại</span>
                <span class="specs-val"><?= htmlspecialchars($type_label) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($style_label): ?>
            <div class="specs-row">
                <span class="specs-key">Phong cách</span>
                <span class="specs-val"><?= htmlspecialchars($style_label) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($color_label): ?>
            <div class="specs-row">
                <span class="specs-key">Màu sắc</span>
                <span class="specs-val"><?= htmlspecialchars($color_label) ?></span>
            </div>
            <?php endif; ?>
            <div class="specs-row">
                <span class="specs-key">Nguồn gốc</span>
                <span class="specs-val">Bát Tràng, Hà Nội</span>
            </div>
            <div class="specs-row">
                <span class="specs-key">Chất liệu</span>
                <span class="specs-val">Thủ công truyền thống</span>
            </div>
        </div>

        <!-- Số lượng -->
        <div class="qty-label">Số lượng</div>
        <div class="qty-selector">
            <button class="qty-btn" id="qty-minus" type="button">−</button>
            <input class="qty-input" id="qty-input" type="number" value="1" min="1" max="99">
            <button class="qty-btn" id="qty-plus" type="button">+</button>
        </div>

        <!-- Nút CTA -->
        <div class="product-cta">
            <button class="btn-detail-cart" id="btn-add-cart-detail"
                data-id="<?= $product['id'] ?>"
                data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>"
                data-price="<?= (int)$product['price'] ?>"
                data-image="<?= htmlspecialchars($product['image_url'] ?? '', ENT_QUOTES) ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Thêm vào giỏ
            </button>
            <button class="btn-detail-buy" id="btn-buy-now-detail"
                data-id="<?= $product['id'] ?>"
                data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>"
                data-price="<?= (int)$product['price'] ?>"
                data-image="<?= htmlspecialchars($product['image_url'] ?? '', ENT_QUOTES) ?>">
                Mua ngay
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </div>

       

    </div><!-- /product-info -->
</div>

<!-- ── Sản phẩm liên quan ─────────────────────────────────── -->
<?php if (!empty($related)): ?>
<section class="related-section">
    <div class="related-header">
        <h2 class="related-title">Có thể bạn thích</h2>
        <a href="<?= $cat_page ?>" class="related-see-all">Xem tất cả <?= htmlspecialchars($product['category_name']) ?> →</a>
    </div>
    <div class="related-grid products-grid">
        <?php foreach ($related as $r): ?>
        <div class="product-card">
            <div class="product-img" onclick="location.href='product.php?id=<?= $r['id'] ?>'">
                <?php if (!empty($r['image_url'])): ?>
                    <img src="<?= htmlspecialchars($r['image_url']) ?>"
                         alt="<?= htmlspecialchars($r['name']) ?>"
                         class="real-product-img">
                <?php else: ?>
                    <div class="fallback-img <?= htmlspecialchars($r['image_class'] ?? '') ?>"></div>
                <?php endif; ?>

                <?php if (!empty($r['badge'])): ?>
                    <div class="product-badge <?= $r['badge'] === 'Mới' ? 'new-badge' : '' ?>">
                        <?= htmlspecialchars($r['badge']) ?>
                    </div>
                <?php endif; ?>

                <div class="price-floating-badge">
                    <?= number_format($r['price'], 0, ',', '.') ?>đ
                </div>
            </div>
            <div class="product-body">
                <h3 class="product-name" onclick="location.href='product.php?id=<?= $r['id'] ?>'">
                    <?= htmlspecialchars($r['name']) ?>
                </h3>
                <div class="product-actions">
                    <button class="btn-add-cart"
                        data-id="<?= $r['id'] ?>"
                        data-name="<?= htmlspecialchars($r['name'], ENT_QUOTES) ?>"
                        data-price="<?= (int)$r['price'] ?>"
                        data-image="<?= htmlspecialchars($r['image_url'] ?? '', ENT_QUOTES) ?>">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        Giỏ hàng
                    </button>
                    <button class="btn-buy-now"
                        data-id="<?= $r['id'] ?>"
                        data-name="<?= htmlspecialchars($r['name'], ENT_QUOTES) ?>"
                        data-price="<?= (int)$r['price'] ?>"
                        data-image="<?= htmlspecialchars($r['image_url'] ?? '', ENT_QUOTES) ?>">
                        Mua ngay
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<script>
// ── Số lượng ──────────────────────────────────────────────────
(function() {
    const minusBtn = document.getElementById('qty-minus');
    const plusBtn  = document.getElementById('qty-plus');
    const qtyInput = document.getElementById('qty-input');

    minusBtn.addEventListener('click', function() {
        const v = parseInt(qtyInput.value) || 1;
        if (v > 1) qtyInput.value = v - 1;
    });
    plusBtn.addEventListener('click', function() {
        const v = parseInt(qtyInput.value) || 1;
        if (v < 99) qtyInput.value = v + 1;
    });
    qtyInput.addEventListener('change', function() {
        let v = parseInt(this.value) || 1;
        v = Math.max(1, Math.min(99, v));
        this.value = v;
    });
})();

// ── Thêm giỏ + Mua ngay (tích hợp cart.js) ───────────────────
document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.getElementById('btn-add-cart-detail');
    const buyBtn = document.getElementById('btn-buy-now-detail');
    const qtyInput = document.getElementById('qty-input');

    function getQty() { return Math.max(1, parseInt(qtyInput.value) || 1); }

    if (addBtn) {
        addBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!isLoggedIn()) { showLoginModal(); return; }

            const qty = getQty();
            for (let i = 0; i < qty; i++) {
                addToCart(addBtn.dataset.id, addBtn.dataset.name, parseInt(addBtn.dataset.price), addBtn.dataset.image || '');
            }

            const orig = addBtn.innerHTML;
            addBtn.classList.add('added');
            addBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Đã thêm!';
            setTimeout(function() { addBtn.classList.remove('added'); addBtn.innerHTML = orig; }, 2000);
        });
    }

    if (buyBtn) {
        buyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!isLoggedIn()) { showLoginModal(); return; }

            const qty = getQty();
            for (let i = 0; i < qty; i++) {
                addToCart(buyBtn.dataset.id, buyBtn.dataset.name, parseInt(buyBtn.dataset.price), buyBtn.dataset.image || '');
            }
            setTimeout(function() { window.location.href = 'cart.php'; }, 150);
        });
    }
});
</script>

<script>
// ── Sticky gallery: đo header và set top động ──────────────
(function() {
    var gallery = document.querySelector('.product-gallery');
    if (!gallery) return;

    function updateStickyTop() {
        var nav = document.querySelector('.header-nav');
        if (!nav) return;
        var h = nav.getBoundingClientRect().height;
        gallery.style.top = (h + 20) + 'px';
    }

    updateStickyTop();
    window.addEventListener('resize', updateStickyTop);
})();
</script>

<?php include 'includes/footer.php'; ?>