<?php 
// 1. GỌI FILE KẾT NỐI DATABASE Ở ĐẦU TRANG
require_once 'config/database.php'; 
?>

<?php include 'includes/header.php'; ?>

<section class="hero">
  
  <div class="hero-content">
    <div class="hero-tag"><span class="dot"></span> Thủ công · Bát Tràng · Việt Nam</div>
    <h1 class="hero-title">Nơi đôi tay<br><em>kể chuyện</em><br>linh hồn</h1>
    <p class="hero-sub">Mỗi sản phẩm là một tác phẩm độc bản — mua về để trân trọng, hoặc đến để tự tay tạo ra cái của riêng mình.</p>
    <div class="hero-btns">
      <a href="gom-su.php" class="btn-primary btn-lg">Khám phá cửa hàng</a>
      <a href="workshop.php" class="btn-outline btn-lg">Xem lịch workshop</a>
    </div>    
  </div>
  
  <div class="hero-visual">
    <div class="hero-video-wrap">
      <video class="hero-video" autoplay loop muted playsinline poster="assets/images/video-placeholder.jpg">
        <source src="assets/video/soul&hands.mp4" type="video/mp4">
        Trình duyệt của bạn không hỗ trợ thẻ video.
      </video>
    </div>
  </div>
</section>

<div class="ticker-wrap">
  <div class="ticker">
    <span>Nung thủ công 100%</span><span class="sep">◆</span>
    <span>Giao hàng toàn quốc</span><span class="sep">◆</span>
    <span>Nghệ nhân được chứng nhận</span><span class="sep">◆</span>
    <span>Đổi trả trong 7 ngày</span><span class="sep">◆</span>
  </div>
  <div class="ticker">
    <span>Nung thủ công 100%</span><span class="sep">◆</span>
    <span>Giao hàng toàn quốc</span><span class="sep">◆</span>
    <span>Nghệ nhân được chứng nhận</span><span class="sep">◆</span>
    <span>Đổi trả trong 7 ngày</span><span class="sep">◆</span>
  </div>
</div>

<section class="products-section">
  <div class="section-header">
    <div class="section-eyebrow">Bộ sưu tập</div>
    <h2 class="section-title">Sản phẩm nổi bật</h2>
    <a href="gom-su.php" class="section-link">Xem tất cả →</a>
  </div>
  
  <div class="products-grid">
    <?php
    // Lấy 8 sản phẩm mới nhất từ DB
    $sql_products = "SELECT p.*, c.name as category_name 
                     FROM products p 
                     JOIN categories c ON p.category_id = c.id 
                     ORDER BY p.id ASC LIMIT 8";
    $result_products = $conn->query($sql_products);

    if ($result_products->num_rows > 0) {
        while($row = $result_products->fetch_assoc()) {
    ?>
    <div class="product-card">
        <div class="product-img" onclick="location.href='product.php?id=<?= $row['id'] ?>'">
            <?php if(!empty($row['image_url'])): ?>
                <img src="<?= $row['image_url'] ?>" alt="<?= $row['name'] ?>" class="real-product-img">
            <?php else: ?>
                <div class="fallback-img <?= !empty($row['image_class']) ? $row['image_class'] : '' ?>"></div>
            <?php endif; ?>

            <?php if(!empty($row['badge'])): ?>
                <div class="product-badge <?= ($row['badge'] == 'Mới') ? 'new-badge' : '' ?>"><?= $row['badge'] ?></div>
            <?php endif; ?>

            <div class="price-floating-badge">
                <?= number_format($row['price'], 0, ',', '.') ?>đ
            </div>
        </div>

        <div class="product-body">
            <h3 class="product-name" onclick="location.href='product.php?id=<?= $row['id'] ?>'"><?= $row['name'] ?></h3>
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
        echo "<p>Chưa có sản phẩm nào.</p>";
    }
    ?>
  </div>
</section>

<section class="ws-feature">
  <!-- BÊN TRÁI: Chữ giới thiệu -->
  <div class="ws-feature-left">
    <div class="section-eyebrow light">Trải nghiệm thực tế</div>
    <h2 class="section-title light">Tự tay tạo nên<br>dấu ấn riêng</h2>
    <p class="ws-feature-desc">Tham gia các buổi workshop cùng nghệ nhân để hiểu về quy trình và tự tay hoàn thiện sản phẩm của chính mình.</p>
    <a href="workshop.php" class="btn-outline">Khám phá tất cả workshop</a>
  </div>

  <!-- BÊN PHẢI: Lưới ảnh workshop với tên overlay -->
  <div class="ws-feature-right">
    <div class="ws-img-grid">
      <?php
      $ws_fallback_imgs = [
          1 => 'https://sf-static.upanhlaylink.com/img/image_20260510a80aac45cbed8b6d1060d0f71f954605.jpg',
          2 => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?q=80&w=800&auto=format&fit=crop',
          3 => 'https://sf-static.upanhlaylink.com/img/image_202605104bd5e177a488151c41e99481bd8069a9.jpg',
      ];

      $sql_ws = "SELECT * FROM workshops ORDER BY id ASC LIMIT 3";
      $res_ws = $conn->query($sql_ws);
      if ($res_ws && $res_ws->num_rows > 0):
          while($ws = $res_ws->fetch_assoc()):
              $img_src = !empty($ws['image_url'])
                  ? $ws['image_url']
                  : ($ws_fallback_imgs[$ws['id']] ?? $ws_fallback_imgs[1]);
      ?>
        <div class="ws-img-card" onclick="location.href='workshop.php?ws_id=<?= $ws['id'] ?>'">
          <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($ws['name']) ?>" class="ws-img-bg">
          <div class="ws-img-overlay"></div>
          <div class="ws-img-content">

            <h3 class="ws-img-name"><?= htmlspecialchars($ws['name']) ?></h3>
            <div class="ws-img-meta"><?= htmlspecialchars($ws['duration_info']) ?> · <?= number_format($ws['price'], 0, ',', '.') ?>đ</div>
            <span class="ws-img-btn">Đặt lịch →</span>
          </div>
        </div>
      <?php
          endwhile;
      endif;
      ?>
    </div>
  </div>
</section>

<section class="testimonial-section">
  <div class="section-eyebrow">Khách hàng nói gì</div>
  <div class="testimonial-grid">
    <div class="testimonial-card">
      <div class="stars">★★★★★</div>
      <p>"Bình gốm đẹp hơn hình nhiều, men rạn cực kỳ tinh tế. Sẽ mua thêm lần nữa!"</p>
      <div class="testimonial-author">— Minh Châu, Hà Nội</div>
    </div>
    <div class="testimonial-card featured">
      <div class="stars">★★★★★</div>
      <p>"Workshop nặn gốm là trải nghiệm tuyệt vời nhất tôi từng có. Nghệ nhân rất kiên nhẫn và tận tâm."</p>
      <div class="testimonial-author">— Thanh Tùng, TP.HCM</div>
    </div>
    <div class="testimonial-card">
      <div class="stars">★★★★☆</div>
      <p>"Đóng gói cẩn thận, hàng nguyên vẹn. Chất lượng thủ công thực sự khác biệt."</p>
      <div class="testimonial-author">— Bảo Ngọc, Đà Nẵng</div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>