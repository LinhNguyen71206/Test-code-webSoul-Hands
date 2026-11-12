<?php 
// 1. GỌI FILE KẾT NỐI DATABASE Ở ĐẦU TRANG
require_once 'config/database.php'; 
require_once 'includes/product-card.php';
?>

<?php include 'includes/header.php'; ?>

<section class="hero">
  
  <div class="hero-content">
    <div class="hero-tag"><span class="dot"></span> Thủ công · Bát Tràng · Việt Nam</div>
    <h1 class="hero-title">Nơi đôi tay<br><em>kể chuyện</em><br>linh hồn</h1>
    <p class="hero-sub">Mỗi sản phẩm là một tác phẩm độc bản — mua về để trân trọng, hoặc tặng người thân như một món quà đầy tâm huyết.</p>
    <div class="hero-btns">
      <a href="gom-su.php" class="btn-primary btn-lg">Khám phá cửa hàng</a>
      <a href="sale.php" class="btn-outline btn-lg">Xem ưu đãi</a>
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
    // Lấy 8 sản phẩm đầu tiên từ DB
    $sql_products = "SELECT p.*, c.name as category_name 
                     FROM products p 
                     JOIN categories c ON p.category_id = c.id 
                     ORDER BY p.id ASC LIMIT 8";
    $result_products = $conn->query($sql_products);

    if ($result_products && $result_products->num_rows > 0) {
        while ($row = $result_products->fetch_assoc()) {
            render_product_card($row);
        }
    } else {
        echo "<p>Chưa có sản phẩm nào.</p>";
    }
    ?>
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
      <p>"Mua làm quà tặng, người nhận rất thích. Từng chi tiết đều thấy sự tỉ mỉ và tận tâm của nghệ nhân."</p>
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