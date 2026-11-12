<?php
/**
 * includes/product-card.php — Thẻ sản phẩm dùng chung
 *
 * Trước đây đoạn HTML này bị chép lại ở 6 nơi (index, search, shop, sale,
 * category-page, product). Giờ chỉ viết một lần ở đây.
 *
 * Cách dùng:
 *   require_once 'includes/product-card.php';
 *   render_product_card($row);
 *   render_product_card($row, ['name_html' => $html_da_escape, 'show_category' => true]);
 *   render_product_card($row, ['sale' => true]);   // trang SALE: badge đỏ + giá cũ gạch ngang
 *
 * $row cần có: id, name, price, image_url, image_class, badge, old_price (và category_name nếu show_category).
 */
if (!function_exists('render_product_card')) {

    function render_product_card(array $p, array $opts = []) {
        $id       = (int)$p['id'];
        $name     = (string)$p['name'];
        $image    = (string)($p['image_url'] ?? '');
        $badge    = (string)($p['badge'] ?? '');
        $is_sale  = !empty($opts['sale']);
        // name_html: tên đã được escape sẵn (ví dụ có <mark> làm nổi từ khóa tìm kiếm)
        $name_html = $opts['name_html'] ?? htmlspecialchars($name);
        $data_attrs = 'data-id="' . $id . '"'
            . ' data-name="' . htmlspecialchars($name, ENT_QUOTES) . '"'
            . ' data-price="' . (int)$p['price'] . '"'
            . ' data-image="' . htmlspecialchars($image, ENT_QUOTES) . '"';
        ?>
        <div class="product-card">
            <div class="product-img" onclick="location.href='product.php?id=<?= $id ?>'">
                <?php if ($image !== ''): ?>
                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($name) ?>" class="real-product-img">
                <?php else: ?>
                    <div class="fallback-img <?= htmlspecialchars((string)($p['image_class'] ?? '')) ?>"></div>
                <?php endif; ?>

                <?php if ($badge !== ''): ?>
                    <div class="product-badge <?= $is_sale ? 'sale-badge' : ($badge === 'Mới' ? 'new-badge' : '') ?>">
                        <?= htmlspecialchars($badge) ?>
                    </div>
                <?php endif; ?>

                <?php if ($is_sale && !empty($p['old_price'])): ?>
                    <div class="sale-old-price-badge"><s><?= number_format($p['old_price'], 0, ',', '.') ?>đ</s></div>
                <?php endif; ?>

                <div class="price-floating-badge"><?= number_format($p['price'], 0, ',', '.') ?>đ</div>
            </div>

            <div class="product-body">
                <h3 class="product-name" onclick="location.href='product.php?id=<?= $id ?>'"><?= $name_html ?></h3>

                <?php if (!empty($opts['show_category']) && !empty($p['category_name'])): ?>
                <div class="product-cat-label"><?= htmlspecialchars($p['category_name']) ?></div>
                <?php endif; ?>

                <div class="product-actions">
                    <button class="btn-add-cart" <?= $data_attrs ?>>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        Giỏ hàng
                    </button>
                    <button class="btn-buy-now" <?= $data_attrs ?>>Mua ngay</button>
                </div>
            </div>
        </div>
        <?php
    }
}