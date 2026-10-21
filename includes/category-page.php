<?php
/**
 * includes/category-page.php
 * ============================================================
 * Template dùng chung cho các trang danh mục sản phẩm.
 * Trước khi include file này, trang gọi phải định nghĩa $cat_config:
 *
 * $cat_config = [
 *   'category_id'     => 1,
 *   'hero_img'        => 'https://...',
 *   'hero_img_alt'    => 'Gốm sứ thủ công',
 *   'hero_bg'         => '',           // style inline, ví dụ: '#6B4F3A' — để trống = dùng màu mặc định
 *   'hero_title_color'=> '',           // để trống = màu mặc định
 *   'hero_desc_color' => '',
 *   'title'           => 'Gốm sứ',
 *   'desc'            => 'Mô tả...',
 *   'color_group_label'=> 'Màu sắc',  // nhãn nhóm filter màu (ví dụ: "Màu gỗ", "Màu men")
 *   'allowed_colors'  => ['trang', 'nau', ...],
 *   'allowed_types'   => ['binh-hoa', ...],
 *   'allowed_styles'  => ['hien-dai', ...],
 *   'color_labels'    => ['trang' => 'Trắng', ...],
 *   'type_labels'     => ['binh-hoa' => 'Bình hoa & Lọ', ...],
 *   'style_labels'    => ['hien-dai' => 'Hiện đại', ...],
 *   'color_options'   => [            // dùng cho swatch màu trong sidebar
 *       'trang' => ['label'=>'Trắng','hex'=>'#F0EFEB','border'=>'#ccc','check_dark'=>true],
 *       ...
 *   ],
 * ];
 *
 * ============================================================
 */

// ============================================================
// SORT
// ============================================================
$sort = $_GET['sort'] ?? 'popular';

$order_by_map = [
    'popular'    => 'p.id DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'newest'     => 'p.created_at DESC',
];
$order_by = $order_by_map[$sort] ?? $order_by_map['popular']; // nếu sort không hợp lệ, dùng mặc định 'popular'

$sort_labels = [
    'popular'    => 'Sắp Xếp',
    'price_asc'  => 'Giá: Từ Thấp Đến Cao',
    'price_desc' => 'Giá: Từ Cao Đến Thấp',
    'newest'     => 'Hàng Mới Về',
];
$current_label = $sort_labels[$sort] ?? 'Sắp Xếp'; // nếu sort không hợp lệ, hiển thị 'Sắp Xếp' mà không có active state trong dropdown

// ============================================================
// FILTER — đọc + whitelist từ config
// ============================================================
//
if (!isset($cat_config) || !is_array($cat_config)) {
    $cat_config = [];
}
// 
$cat_config += [
    'allowed_colors' => [],
    'allowed_types'  => [],
    'allowed_styles' => [],
    'category_id'    => 0,
    'color_options'  => [],
    'color_labels'   => [],
    'type_labels'    => [],
    'hero_img'       => '',
    'hero_img_alt'   => '',
    'hero_bg'        => '',
    'hero_title_color' => '',
    'hero_desc_color'  => '',
    'title'            => '',
    'desc'             => '',
    'color_group_label' => 'Màu sắc',
];

$filter_color = isset($_GET['color'])    ? (array)$_GET['color']    : []; //vì có thể là mảng (color[]=trang&color[]=nau) hoặc chuỗi đơn (color=trang), nên ép kiểu về mảng để dễ xử lý sau này
$filter_type  = isset($_GET['sub_type']) ? (array)$_GET['sub_type'] : [];
$filter_style = isset($_GET['style'])    ? (array)$_GET['style']    : [];

$filter_color = array_values(array_filter($filter_color, fn($v) => in_array($v, $cat_config['allowed_colors']))); //lọc bỏ giá trị không hợp lệ (không có trong cấu hình), đồng thời reset lại index về 0,1,2... để tránh lỗi khi bind_param sau này
$filter_type  = array_values(array_filter($filter_type,  fn($v) => in_array($v, $cat_config['allowed_types'])));
$filter_style = array_values(array_filter($filter_style, fn($v) => in_array($v, $cat_config['allowed_styles'])));

$has_active_filter = !empty($filter_color) || !empty($filter_type) || !empty($filter_style); // đánh dấu trạng thái active của nút filter và hiển thị số lượng filter đang áp dụng 

// ============================================================
// BUILD QUERY
// ============================================================
$where_clauses = ['p.category_id = ' . (int)$cat_config['category_id']]; // mặc định đã có điều kiện lọc theo category_id, sau đó sẽ thêm các điều kiện khác nếu có filter được áp dụng
$params        = [];
$param_types   = '';

if (!empty($filter_color)) { // nếu có filter màu sắc nào được áp dụng, thì thêm điều kiện WHERE và chuẩn bị tham số cho bind_param
    $ph = implode(',', array_fill(0, count($filter_color), '?'));
    $where_clauses[] = "p.color IN ($ph)";
    foreach ($filter_color as $v) { $params[] = $v; $param_types .= 's'; }
}
if (!empty($filter_type)) {
    $ph = implode(',', array_fill(0, count($filter_type), '?'));
    $where_clauses[] = "p.sub_type IN ($ph)";
    foreach ($filter_type as $v) { $params[] = $v; $param_types .= 's'; }
}
if (!empty($filter_style)) {
    $ph = implode(',', array_fill(0, count($filter_style), '?'));
    $where_clauses[] = "p.style IN ($ph)";
    foreach ($filter_style as $v) { $params[] = $v; $param_types .= 's'; }
}

$where_sql = implode(' AND ', $where_clauses);
$sql = "SELECT p.*, c.name as category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE $where_sql
        ORDER BY $order_by";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
$total_items = $result->num_rows;

// ============================================================
// URL HELPERS
// ============================================================
function cat_toggle_url($key, $value, $current_array, $sort, $filter_color, $filter_type, $filter_style) { // hàm này dùng để tạo URL khi người dùng click vào một filter option nào đó, nó sẽ tự động thêm hoặc bỏ giá trị filter đó trong query string, đồng thời giữ nguyên các filter khác và sort hiện tại
    $params  = [];
    if ($sort !== 'popular') $params[] = 'sort=' . urlencode($sort);
    $new_arr = in_array($value, $current_array)
        ? array_values(array_filter($current_array, fn($v) => $v !== $value))
        : array_merge($current_array, [$value]);
    $all = [
        'color'    => ($key === 'color')    ? $new_arr : $filter_color,
        'sub_type' => ($key === 'sub_type') ? $new_arr : $filter_type,
        'style'    => ($key === 'style')    ? $new_arr : $filter_style,
    ];
    foreach ($all as $k => $arr) {
        foreach ($arr as $v) $params[] = urlencode($k) . '[]=' . urlencode($v);
    }
    return '?' . implode('&', $params);
}

function cat_sort_url($sort_value, $filter_color, $filter_type, $filter_style) {
    $params = [];
    if ($sort_value !== 'popular') $params[] = 'sort=' . urlencode($sort_value);
    foreach ($filter_color as $v) $params[] = 'color[]='    . urlencode($v);
    foreach ($filter_type  as $v) $params[] = 'sub_type[]=' . urlencode($v);
    foreach ($filter_style as $v) $params[] = 'style[]='    . urlencode($v);
    return '?' . implode('&', $params);
}

$clear_url = $sort !== 'popular' ? '?sort=' . urlencode($sort) : '?';

// ============================================================
// RENDER
// ============================================================
include 'includes/header.php';

$hero_bg_style    = !empty($cat_config['hero_bg'])          ? 'background:' . $cat_config['hero_bg'] . ';'        : '';
$title_color_style= !empty($cat_config['hero_title_color']) ? 'color:' . $cat_config['hero_title_color'] . ';'    : '';
$desc_color_style = !empty($cat_config['hero_desc_color'])  ? 'color:' . $cat_config['hero_desc_color'] . ';'     : '';
$color_group_label= $cat_config['color_group_label'] ?? 'Màu sắc';
?>

<main class="category-page">

    <!-- ========== HERO BANNER ========== -->
    <section class="category-hero">
        <div class="category-image-wrap">
            <img src="<?= htmlspecialchars($cat_config['hero_img']) ?>"
                 alt="<?= htmlspecialchars($cat_config['hero_img_alt']) ?>"
                 class="category-hero-img">
        </div>
        <div class="category-hero-content" style="<?= $hero_bg_style ?>">
            <h1 class="category-title" style="<?= $title_color_style ?>"><?= htmlspecialchars($cat_config['title']) ?></h1>
            <p class="category-desc" style="<?= $desc_color_style ?>"><?= htmlspecialchars($cat_config['desc']) ?></p>
        </div>
    </section>

    <!-- ========== KHU VỰC SẢN PHẨM ========== -->
    <section class="products-section">

        <!-- TOOLBAR -->
        <div class="products-toolbar">
            <div class="toolbar-left">
                <span class="item-count"><?= $total_items ?> mặt hàng</span>

                <button class="toolbar-btn btn-filter <?= $has_active_filter ? 'active' : '' ?>" id="filter-toggle-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
                    Bộ lọc
                    <?php if ($has_active_filter): ?>
                        <span class="filter-badge-count"><?= count($filter_color) + count($filter_type) + count($filter_style) ?></span>
                    <?php endif; ?>
                </button>

                <!-- Active filter tags -->
                <?php if ($has_active_filter): ?>
                <div class="active-filter-tags">
                    <?php foreach ($filter_color as $v):
                        $url = cat_toggle_url('color', $v, $filter_color, $sort, $filter_color, $filter_type, $filter_style);
                        $hex = $cat_config['color_options'][$v]['hex'] ?? '#ccc';
                    ?>
                        <a href="<?= $url ?>" class="filter-tag">
                            <span class="tag-dot" style="background:<?= $hex ?>"></span>
                            <?= htmlspecialchars($cat_config['color_labels'][$v] ?? $v) ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </a>
                    <?php endforeach; ?>
                    <?php foreach ($filter_type as $v):
                        $url = cat_toggle_url('sub_type', $v, $filter_type, $sort, $filter_color, $filter_type, $filter_style);
                    ?>
                        <a href="<?= $url ?>" class="filter-tag">
                            <?= htmlspecialchars($cat_config['type_labels'][$v] ?? $v) ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </a>
                    <?php endforeach; ?>
                    <?php foreach ($filter_style as $v):
                        $url = cat_toggle_url('style', $v, $filter_style, $sort, $filter_color, $filter_type, $filter_style);
                    ?>
                        <a href="<?= $url ?>" class="filter-tag">
                            <?= htmlspecialchars($cat_config['style_labels'][$v] ?? $v) ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </a>
                    <?php endforeach; ?>
                    <a href="<?= $clear_url ?>" class="filter-clear-all">Xóa tất cả</a>
                </div>
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
                        <a href="<?= cat_sort_url($val, $filter_color, $filter_type, $filter_style) ?>"
                           class="sort-item <?= ($sort === $val) ? 'active' : '' ?>">
                            <?= $label ?>
                            <?php if ($sort === $val): ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- LAYOUT: Sidebar + Grid -->
        <div class="filter-product-layout">

            <!-- ========== FILTER PANEL ========== -->
            <aside class="filter-panel" id="filter-panel">
                <div class="filter-panel-header">
                    <span class="filter-panel-title">Bộ lọc</span>
                    <?php if ($has_active_filter): ?>
                        <a href="<?= $clear_url ?>" class="filter-panel-clear">Xóa tất cả</a>
                    <?php endif; ?>
                </div>

                <!-- NHÓM 1: Loại sản phẩm -->
                <div class="filter-group">
                    <button class="filter-group-title" data-target="group-type">
                        Loại sản phẩm
                        <svg class="fg-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="filter-group-body open" id="group-type">
                        <?php foreach ($cat_config['type_labels'] as $val => $label):
                            $is_checked = in_array($val, $filter_type);
                            $href = cat_toggle_url('sub_type', $val, $filter_type, $sort, $filter_color, $filter_type, $filter_style);
                        ?>
                        <a href="<?= $href ?>" class="filter-option <?= $is_checked ? 'checked' : '' ?>">
                            <span class="filter-checkbox">
                                <?php if ($is_checked): ?>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                <?php endif; ?>
                            </span>
                            <?= htmlspecialchars($label) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-divider"></div>

                <!-- NHÓM 2: Phong cách -->
                <div class="filter-group">
                    <button class="filter-group-title" data-target="group-style">
                        Phong cách
                        <svg class="fg-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="filter-group-body open" id="group-style">
                        <?php foreach ($cat_config['style_labels'] as $val => $label):
                            $is_checked = in_array($val, $filter_style);
                            $href = cat_toggle_url('style', $val, $filter_style, $sort, $filter_color, $filter_type, $filter_style);
                        ?>
                        <a href="<?= $href ?>" class="filter-option <?= $is_checked ? 'checked' : '' ?>">
                            <span class="filter-checkbox">
                                <?php if ($is_checked): ?>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                <?php endif; ?>
                            </span>
                            <?= htmlspecialchars($label) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-divider"></div>

                <!-- NHÓM 3: Màu sắc -->
                <div class="filter-group">
                    <button class="filter-group-title" data-target="group-color">
                        <?= htmlspecialchars($color_group_label) ?>
                        <svg class="fg-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="filter-group-body open" id="group-color">
                        <div class="color-swatches">
                            <?php foreach ($cat_config['color_options'] as $val => $info):
                                $is_checked  = in_array($val, $filter_color);
                                $href        = cat_toggle_url('color', $val, $filter_color, $sort, $filter_color, $filter_type, $filter_style);
                                $check_color = !empty($info['check_dark']) ? '#2C2C2A' : '#fff';
                            ?>
                            <a href="<?= $href ?>" class="color-swatch-item <?= $is_checked ? 'checked' : '' ?>" title="<?= htmlspecialchars($info['label']) ?>">
                                <span class="color-swatch" style="background:<?= $info['hex'] ?>;border-color:<?= $info['border'] ?>;">
                                    <?php if ($is_checked): ?>
                                        <svg class="swatch-check" width="12" height="12" viewBox="0 0 24 24" fill="none"
                                             stroke="<?= $check_color ?>" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    <?php endif; ?>
                                </span>
                                <span class="color-label"><?= htmlspecialchars($info['label']) ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </aside>

            <!-- ========== LƯỚI SẢN PHẨM ========== -->
            <div class="products-grid-wrapper">
                <div class="products-grid">
                    <?php if ($total_items > 0):
                        while ($row = $result->fetch_assoc()): ?>
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
                                    <?= htmlspecialchars($row['name']) ?>
                                </h3>
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
                    <?php endwhile;
                    else: ?>
                        <div class="no-results">
                            <div class="no-results-icon">🔍</div>
                            <p>Không tìm thấy sản phẩm phù hợp với bộ lọc.</p>
                            <a href="<?= $clear_url ?>" class="btn-primary" style="margin-top:16px;display:inline-flex;">Xóa bộ lọc</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <!-- /filter-product-layout -->

    </section>
</main>

<?php include 'includes/footer.php'; ?>