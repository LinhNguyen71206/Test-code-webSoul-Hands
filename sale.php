<?php 
require_once 'config/database.php'; 
?>

<?php include 'includes/header.php'; ?>

<link rel="stylesheet" href="assets/css/category.css">

<main class="category-page">
    <section class="category-hero" style="background: #E63946;"> <div class="category-image-wrap">
            <img src="https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?q=80&w=1200&auto=format&fit=crop" alt="Ưu đãi độc bản" class="category-hero-img">
        </div>

        <div class="category-hero-content">
            <h1 class="category-title" style="color: #ffffff;">Ưu đãi độc bản</h1>
            <p class="category-desc" style="color: rgba(255,255,255,0.9);">Cơ hội sở hữu những tác phẩm mỹ nghệ tinh xảo với mức giá đặc biệt. Mỗi sản phẩm vẫn giữ trọn linh hồn và sự tâm huyết của nghệ nhân.</p>
        </div>
    </section>

    <section class="products-section">
        <div class="products-grid sale-products-grid">
            <?php
            // Truy vấn SQL: Lấy sản phẩm có badge chứa dấu "-" và "%" (ví dụ: -20%)
            $sql_sale = "SELECT p.*, c.name as category_name 
                         FROM products p 
                         JOIN categories c ON p.category_id = c.id 
                         WHERE p.badge LIKE '-%' 
                         ORDER BY p.id DESC";
            
            $result = $conn->query($sql_sale);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    ?>
                    <div class="product-card">
                        <div class="product-img" onclick="location.href='product.php?id=<?= $row['id'] ?>'">
                            <?php if(!empty($row['image_url'])): ?>
                                <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="real-product-img">
                            <?php else: ?>
                                <div class="fallback-img <?= htmlspecialchars($row['image_class'] ?? '') ?>"></div>
                            <?php endif; ?>

                            <div class="product-badge sale-badge"><?= htmlspecialchars($row['badge']) ?></div>

                            <?php if (!empty($row['old_price'])): ?>
                                <div class="sale-old-price-badge">
                                    <s><?= number_format($row['old_price'], 0, ',', '.') ?>đ</s>
                                </div>
                            <?php endif; ?>

                            <div class="price-floating-badge">
                                <?= number_format($row['price'], 0, ',', '.') ?>đ
                            </div>
                        </div>

                        <div class="product-body">
                            <h3 class="product-name" onclick="location.href='product.php?id=<?= $row['id'] ?>'"><?= htmlspecialchars($row['name']) ?></h3>
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
                    <?php
                }
            } else {
                echo "<div class='no-results' style='grid-column: 1/-1;'>
                        <div class='no-results-icon'>🎁</div>
                        <p>Hiện chưa có chương trình ưu đãi nào mới. Hãy quay lại sau nhé!</p>
                      </div>";
            }
            ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>