<?php require_once 'config/database.php'; ?>
<?php include 'includes/header.php'; ?>

<main class="about-page">

    <!-- ============================================================
         1. HERO — Chữ lớn + ảnh full bleed
    ============================================================ -->
    <section class="about-hero">
        <div class="about-hero-inner">
            <div class="about-hero-text">
                <div class="section-eyebrow">Về chúng tôi</div>
                <h1 class="about-hero-title">
                    Nơi linh hồn<br>
                    <em>sống trong</em><br>
                    đôi bàn tay
                </h1>
            </div>
            <p class="about-hero-quote">
                "Chúng tôi không bán đồ vật. Chúng tôi trao đi những câu chuyện được nặn, khắc và vẽ bằng tất cả sự tận tâm của nghệ nhân Việt."
            </p>
        </div>

        <!-- Lưới ảnh mosaic -->
        <!-- Lưới ảnh 3 cột cao bằng nhau -->
        <div class="about-hero-mosaic">
            <div class="mosaic-cell">
                <img src="assets/images/gom.jpg" alt="Nặn gốm thủ công">
            </div>
            
            <div class="mosaic-cell">
                <img src="assets/images/maytre.jpg" alt="Mây tre đan">
            </div>
            
        </div>
    </section>

    <!-- ============================================================
         2. CON SỐ THỐNG KÊ
    ============================================================ -->
    <section class="about-stats">
        <div class="about-stats-inner">
            <div class="about-stat-item">
                <span class="stat-num">12+</span>
                <span class="stat-label">Năm kinh nghiệm</span>
            </div>
            <div class="about-stat-divider"></div>
            <div class="about-stat-item">
                <span class="stat-num">40+</span>
                <span class="stat-label">Nghệ nhân hợp tác</span>
            </div>
            <div class="about-stat-divider"></div>
            <div class="about-stat-item">
                <span class="stat-num">3.200+</span>
                <span class="stat-label">Tác phẩm đã trao tay</span>
            </div>
            <div class="about-stat-divider"></div>
            <div class="about-stat-item">
                <span class="stat-num">98%</span>
                <span class="stat-label">Khách hàng hài lòng</span>
            </div>
        </div>
    </section>

    <!-- ============================================================
         3. CÂU CHUYỆN THƯƠNG HIỆU — layout 2 cột xen kẽ
    ============================================================ -->
    <section class="about-story">

        <!-- Block 1: Ảnh trái · Chữ phải -->
        <div class="story-block">
            <div class="story-img-wrap">
                <img src="assets/images/battrang.jpg" alt="Làng gốm Bát Tràng" class="story-img">
                <div class="story-img-caption">Làng gốm Bát Tràng, Hà Nội · 2013</div>
            </div>
            <div class="story-text">
                <div class="section-eyebrow">Khởi đầu</div>
                <h2 class="story-title">Sinh ra từ<br>làng nghề Bát Tràng</h2>
                <p class="story-body">Soul & Hands ra đời năm 2013 tại làng gốm Bát Tràng — nơi những lò nung đỏ lửa hơn 700 năm chưa bao giờ tắt. Người sáng lập, nghệ nhân Nguyễn Hà Linh, muốn tạo ra một cầu nối giữa bàn tay thợ làng và những người yêu vẻ đẹp thủ công đích thực.</p>
                <p class="story-body">Từ một gian nhỏ trong ngõ Bát Tràng, chúng tôi đã dần mở rộng ra 4 dòng sản phẩm: gốm sứ, gỗ điêu khắc, mây tre và sơn mài — mỗi dòng là một thế giới riêng của kỹ thuật và cảm xúc.</p>
            </div>
        </div>

        <!-- Block 2: Chữ trái · Ảnh phải (reverse) -->
        <div class="story-block story-block-reverse">
            <div class="story-text">
                <div class="section-eyebrow">Triết lý</div>
                <h2 class="story-title">Mỗi sản phẩm là<br>một tác phẩm độc bản</h2>
                <p class="story-body">Chúng tôi không dùng khuôn đổ hàng loạt. Mỗi chiếc bình, mỗi bức tượng, mỗi tấm tranh đều được tạo ra bởi đôi bàn tay — với những đường nét không sản phẩm nào có thể giống hoàn toàn.</p>
                <p class="story-body">Chính sự không hoàn hảo nhất quán đó mới là dấu ấn thật sự của thủ công. Mỗi vết men rạn, mỗi thớ gỗ tự nhiên hay nếp đan mây đều nói lên rằng: <em>có một bàn tay người đã làm ra điều này.</em></p>
            </div>
            <div class="story-img-wrap">
                <img src="assets/images/sonmai.jpg" alt="Nghệ nhân sơn mài" class="story-img">
                <div class="story-img-caption">Xưởng sơn mài · Hà Đông, Hà Nội</div>
            </div>
        </div>

    </section>

    <!-- ============================================================
         4. GIÁ TRỊ CỐT LÕI — nền tối
    ============================================================ -->
    <section class="about-values">
        <div class="about-values-inner">
            <div class="values-header">
                <div class="section-eyebrow light">Cam kết</div>
                <h2 class="section-title light">Những điều chúng tôi<br>luôn giữ vững</h2>
            </div>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon"></div>
                    <h3 class="value-title">100% Thủ công</h3>
                    <p class="value-desc">Không có máy móc nào tham gia vào quá trình tạo hình. Từ nặn đất, khắc gỗ, đan mây đến phủ sơn — tất cả đều do bàn tay nghệ nhân làm.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"></div>
                    <h3 class="value-title">Nguyên liệu tự nhiên</h3>
                    <p class="value-desc">Đất sét Bát Tràng, gỗ mít — gỗ hương được khai thác hợp pháp, mây tre từ làng nghề Hà Tĩnh, nhựa sơn ta thuần túy từ Phú Thọ.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"></div>
                    <h3 class="value-title">Nghệ nhân được chứng nhận</h3>
                    <p class="value-desc">Mỗi nghệ nhân hợp tác với Soul & Hands đều có ít nhất 10 năm kinh nghiệm và được chứng nhận bởi Hội Mỹ nghệ Thủ công Việt Nam.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"></div>
                    <h3 class="value-title">Đóng gói cẩn thận</h3>
                    <p class="value-desc">Mỗi sản phẩm được bọc trong giấy lụa tái chế, hộp carton cứng và túi vải thô. Sẵn sàng để làm quà tặng không cần trang trí thêm.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         5. ĐỘI NGŨ NGHỆ NHÂN
    ============================================================ -->
    <section class="about-artisans">
        <div class="about-artisans-inner">
            <div class="artisans-header">
                <div class="section-eyebrow">Con người</div>
                <h2 class="artisans-title">Những đôi tay<br>đứng sau mỗi sản phẩm</h2>
                <p class="artisans-sub">Chúng tôi không gọi họ là "thợ". Họ là nghệ nhân — những người đã dành cả cuộc đời để hoàn thiện một kỹ năng.</p>
            </div>

            <div class="artisans-grid">

                <div class="artisan-card">
                    <div class="artisan-img-wrap">
                        <img src="assets/images/tvm.jpg" alt="Nghệ nhân Trần Văn Minh" class="artisan-img">
                    </div>
                    <div class="artisan-info">
                        <h3 class="artisan-name">Trần Văn Minh</h3>
                        <div class="artisan-craft"> Gốm sứ Bát Tràng · 28 năm</div>
                        <p class="artisan-quote">"Đất sét biết nghe. Nó sẽ cho ra hình dáng đúng nếu bàn tay bạn đủ kiên nhẫn."</p>
                    </div>
                </div>

                <div class="artisan-card">
                    <div class="artisan-img-wrap">
                        <img src="assets/images/lth.jpg" alt="Nghệ nhân Lê Thị Hương" class="artisan-img">
                    </div>
                    <div class="artisan-info">
                        <h3 class="artisan-name">Lê Thị Hương</h3>
                        <div class="artisan-craft"> Sơn mài Hà Đông · 22 năm</div>
                        <p class="artisan-quote">"Mỗi lớp sơn cần được đánh thức bằng cách mài. Vẻ đẹp thật sự chỉ lộ ra sau nhiều lần kiên nhẫn."</p>
                    </div>
                </div>

                <div class="artisan-card">
                    <div class="artisan-img-wrap">
                        <img src="assets/images/ndt.jpg " alt="Nghệ nhân Nguyễn Đức Thành" class="artisan-img">
                    </div>
                    <div class="artisan-info">
                        <h3 class="artisan-name">Nguyễn Đức Thành</h3>
                        <div class="artisan-craft"> Gỗ điêu khắc · 35 năm</div>
                        <p class="artisan-quote">"Tôi không tạo ra hình dáng — tôi tìm ra hình dáng đã có sẵn bên trong khúc gỗ."</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ============================================================
         6. DÒNG THỜI GIAN
    ============================================================ -->
    <section class="about-timeline">
        <div class="about-timeline-inner">
            <div class="timeline-header">
                <div class="section-eyebrow">Hành trình</div>
                <h2 class="timeline-title">Từng bước, từng năm</h2>
            </div>

            <div class="timeline-track">
                <div class="timeline-line"></div>

                <div class="timeline-item">
                    <div class="tl-dot"></div>
                    <div class="tl-year">2013</div>
                    <div class="tl-content">
                        <h4>Khai sinh tại Bát Tràng</h4>
                        <p>Gian nhỏ đầu tiên trong ngõ làng gốm. 3 nghệ nhân, 12 sản phẩm, và một niềm tin vào vẻ đẹp thủ công.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="tl-dot"></div>
                    <div class="tl-year">2015</div>
                    <div class="tl-content">
                        <h4>Mở rộng sang Gỗ & Mây tre</h4>
                        <p>Hợp tác với xưởng gỗ mít ở Hà Tĩnh và làng đan mây Nga Sơn, Thanh Hóa. Danh mục sản phẩm tăng gấp 4 lần.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="tl-dot"></div>
                    <div class="tl-year">2018</div>
                    <div class="tl-content">
                        <h4>Ra mắt Workshop đầu tiên</h4>
                        <p>Buổi workshop nặn gốm đầu tiên với 8 khách. Đặt nền móng cho chuỗi trải nghiệm thủ công trực tiếp tại xưởng.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="tl-dot"></div>
                    <div class="tl-year">2021</div>
                    <div class="tl-content">
                        <h4>Thêm dòng Sơn mài</h4>
                        <p>Kết nối với nghệ nhân sơn mài làng Hà Thái, Hà Đông. Dòng sơn mài đoạt giải "Sản phẩm làng nghề tiêu biểu" năm 2022.</p>
                    </div>
                </div>

                <div class="timeline-item tl-now">
                    <div class="tl-dot tl-dot-active"></div>
                    <div class="tl-year tl-year-active">2026</div>
                    <div class="tl-content">
                        <h4>Soul & Hands hôm nay</h4>
                        <p>40+ nghệ nhân, 4 dòng sản phẩm, hơn 3.200 tác phẩm đã trao tay. Và vẫn còn rất nhiều câu chuyện chưa được kể.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ============================================================
         7. CTA CUỐI — Mời khám phá
    ============================================================ -->
    <section class="about-cta">
        <div class="about-cta-bg">
            <img src="https://images.unsplash.com/photo-1610701596007-11502861dcfa?q=80&w=1600&auto=format&fit=crop" alt="Gốm sứ Soul & Hands" class="about-cta-img">
            <div class="about-cta-overlay"></div>
        </div>
        <div class="about-cta-content">
            <div class="section-eyebrow light">Bắt đầu hành trình</div>
            <h2 class="about-cta-title">Sở hữu một tác phẩm —<br>hoặc tự tay tạo ra</h2>
            <p class="about-cta-sub">Mỗi sản phẩm là độc bản. Mỗi buổi workshop là ký ức không lặp lại.</p>
            <div class="about-cta-btns">
                <a href="index.php" class="btn-primary btn-lg">Khám phá cửa hàng</a>
                <a href="workshop.php" class="about-cta-link">Đặt lịch workshop →</a>
            </div>
        </div>
    </section>

</main>

<style>
/* ============================================================
   ABOUT PAGE — VỀ CHÚNG TÔI
============================================================ */
.about-page { background: var(--page); }

/* ======== 1. HERO ======== */
.about-hero {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 88vh;
    border-bottom: 1px solid var(--cream2);
}

.about-hero-inner {
    padding: 100px 60px 100px 40px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border-right: 1px solid var(--cream2);
}

.about-hero-title {
    font-family: var(--font-display);
    font-size: 80px;
    font-weight: 500;
    color: var(--ink);
    line-height: 1.05;
    letter-spacing: -2px;
    margin-top: 20px;
    margin-bottom: auto;
}
.about-hero-title em {
    font-style: italic;
    color: var(--rust);
}

.about-hero-quote {
    font-family: var(--font-display);
    font-style: italic;
    font-size: 20px;
    color: var(--ink2);
    line-height: 1.6;
    max-width: 480px;
    border-left: 3px solid var(--rust);
    padding-left: 20px;
    margin-top: 60px;
}

/* Mosaic ảnh bên phải */
.about-hero-mosaic {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
}

.mosaic-cell { overflow: hidden; }
.mosaic-cell img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.6s ease;
}
.mosaic-cell:hover img { transform: scale(1.04); }

.mosaic-tall { grid-row: span 2; }

.mosaic-right {
    display: flex;
    flex-direction: column;
}
.mosaic-wide { flex: 1; overflow: hidden; }
.mosaic-wide img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.6s ease; }
.mosaic-wide:hover img { transform: scale(1.04); }

/* ======== 2. STATS ======== */
.about-stats {
    background: var(--ink);
    border-bottom: 0.5px solid #444441;
}

.about-stats-inner {
    max-width: var(--max-w);
    margin: 0 auto;
    padding: 56px 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
}

.about-stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 0 64px;
    flex: 1;
}

.stat-num {
    font-family: var(--font-display);
    font-size: 56px;
    font-weight: 500;
    color: var(--cream);
    line-height: 1;
    letter-spacing: -1px;
}

.stat-label {
    font-family: var(--font-mono);
    font-size: 11px;
    color: var(--ink3);
    text-transform: uppercase;
    letter-spacing: 1.5px;
}

.about-stat-divider {
    width: 1px;
    height: 60px;
    background: #444441;
    flex-shrink: 0;
}

/* ======== 3. STORY ======== */
.about-story {
    max-width: 1440px;
    margin: 0 auto;
}

.story-block {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 600px;
    border-bottom: 1px solid var(--cream2);
}

.story-block-reverse {
    direction: rtl; /* Đảo cột */
}
.story-block-reverse > * { direction: ltr; }

.story-img-wrap {
    position: relative;
    overflow: hidden;
}

.story-img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.7s ease;
}
.story-img-wrap:hover .story-img { transform: scale(1.03); }

.story-img-caption {
    position: absolute;
    bottom: 20px; left: 20px;
    font-family: var(--font-mono);
    font-size: 11px;
    color: rgba(255,255,255,0.75);
    background: rgba(0,0,0,0.4);
    padding: 6px 12px;
    border-radius: 20px;
    backdrop-filter: blur(4px);
}

.story-text {
    padding: 80px 80px 80px 60px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.story-title {
    font-family: var(--font-display);
    font-size: 52px;
    font-weight: 500;
    color: var(--ink);
    line-height: 1.15;
    letter-spacing: -1px;
    margin-bottom: 28px;
    margin-top: 12px;
}

.story-body {
    font-family: var(--font-menu);
    font-size: 16px;
    color: var(--ink2);
    line-height: 1.8;
    margin-bottom: 16px;
    max-width: 480px;
}
.story-body em { color: var(--rust); font-style: italic; }

/* ======== 4. GIÁ TRỊ CỐT LÕI ======== */
.about-values {
    background: var(--ink);
    padding: 100px 40px;
    border-top: 0.5px solid #444441;
    border-bottom: 0.5px solid #444441;
}

.about-values-inner {
    max-width: var(--max-w);
    margin: 0 auto;
}

.values-header {
    margin-bottom: 60px;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

.value-card {
    background: #3A3A38;
    border: 0.5px solid #5F5E5A;
    border-radius: 16px;
    padding: 36px 28px;
    transition: 0.25s;
}
.value-card:hover {
    border-color: var(--rust);
    background: #444441;
    transform: translateY(-4px);
}

.value-icon {
    font-size: 36px;
    margin-bottom: 20px;
}

.value-title {
    font-family: var(--font-menu);
    font-size: 16px;
    font-weight: 700;
    color: var(--cream);
    margin-bottom: 12px;
}

.value-desc {
    font-family: var(--font-menu);
    font-size: 14px;
    color: var(--ink3);
    line-height: 1.7;
}

/* ======== 5. NGHỆ NHÂN ======== */
.about-artisans {
    padding: 100px 40px;
    border-bottom: 1px solid var(--cream2);
}

.about-artisans-inner {
    max-width: var(--max-w);
    margin: 0 auto;
}

.artisans-header {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    align-items: end;
    margin-bottom: 60px;
}

.artisans-title {
    font-family: var(--font-display);
    font-size: 52px;
    font-weight: 500;
    color: var(--ink);
    line-height: 1.15;
    letter-spacing: -1px;
}

.artisans-sub {
    font-family: var(--font-menu);
    font-size: 15px;
    color: var(--ink2);
    line-height: 1.7;
    max-width: 400px;
    align-self: end;
}

.artisans-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 32px;
}

.artisan-card {
    background: #fff;
    border: 1px solid var(--cream2);
    border-radius: 16px;
    overflow: hidden;
    transition: 0.3s;
}
.artisan-card:hover {
    box-shadow: 0 16px 48px rgba(44,44,42,0.1);
    transform: translateY(-6px);
}

.artisan-img-wrap {
    height: 280px;
    overflow: hidden;
}
.artisan-img {
    width: 100%; height: 100%;
    object-fit: cover;
    object-position: center top;
    display: block;
    transition: transform 0.5s ease;
}
.artisan-card:hover .artisan-img { transform: scale(1.05); }

.artisan-info { padding: 28px 24px; }

.artisan-name {
    font-family: var(--font-display);
    font-size: 26px;
    font-weight: 500;
    color: var(--ink);
    margin-bottom: 6px;
}

.artisan-craft {
    font-family: var(--font-mono);
    font-size: 11px;
    color: var(--rust);
    letter-spacing: 0.5px;
    margin-bottom: 16px;
}

.artisan-quote {
    font-family: var(--font-menu);
    font-style: italic;
    font-size: 14px;
    color: var(--ink2);
    line-height: 1.7;
    border-left: 2px solid var(--cream2);
    padding-left: 14px;
}

/* ======== 6. TIMELINE ======== */
.about-timeline {
    background: var(--page);
    padding: 100px 40px;
    border-bottom: 1px solid var(--cream2);
}

.about-timeline-inner {
    max-width: 800px;
    margin: 0 auto;
}

.timeline-header { margin-bottom: 60px; }

.timeline-title {
    font-family: var(--font-display);
    font-size: 48px;
    font-weight: 500;
    color: var(--ink);
    letter-spacing: -1px;
    margin-top: 8px;
}

.timeline-track {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 0;
}

.timeline-line {
    position: absolute;
    left: 80px;
    top: 8px; bottom: 8px;
    width: 1px;
    background: var(--cream2);
}

.timeline-item {
    display: grid;
    grid-template-columns: 80px 40px 1fr;
    gap: 0 24px;
    align-items: start;
    padding: 36px 0;
    border-bottom: 1px solid var(--cream2);
    position: relative;
    transition: 0.2s;
}
.timeline-item:last-child { border-bottom: none; }
.timeline-item:hover { padding-left: 8px; }

.tl-year {
    font-family: var(--font-mono);
    font-size: 13px;
    font-weight: 600;
    color: var(--ink3);
    padding-top: 2px;
    letter-spacing: 0.5px;
    text-align: right;
}
.tl-year-active { color: var(--rust); }

.tl-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--cream2);
    border: 2px solid var(--page);
    box-shadow: 0 0 0 1px var(--cream2);
    margin: 4px auto 0;
    flex-shrink: 0;
    transition: 0.2s;
}
.tl-dot-active {
    background: var(--rust);
    box-shadow: 0 0 0 1px var(--rust), 0 0 12px rgba(216,90,48,0.35);
}
.timeline-item:hover .tl-dot { background: var(--rust); box-shadow: 0 0 0 1px var(--rust); }

.tl-content h4 {
    font-family: var(--font-menu);
    font-size: 17px;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 8px;
}

.tl-content p {
    font-family: var(--font-menu);
    font-size: 14px;
    color: var(--ink2);
    line-height: 1.7;
}

/* ======== 7. CTA ======== */
.about-cta {
    position: relative;
    height: 520px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.about-cta-bg {
    position: absolute;
    inset: 0;
}

.about-cta-img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
}

.about-cta-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(44,44,42,0.88) 0%, rgba(44,44,42,0.6) 100%);
}

.about-cta-content {
    position: relative;
    z-index: 2;
    text-align: center;
    padding: 0 40px;
}

.about-cta-title {
    font-family: var(--font-display);
    font-size: 60px;
    font-weight: 500;
    color: var(--cream);
    line-height: 1.1;
    letter-spacing: -1px;
    margin: 12px 0 16px;
}

.about-cta-sub {
    font-family: var(--font-menu);
    font-size: 16px;
    color: var(--cream2);
    margin-bottom: 36px;
}

.about-cta-btns {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
}

.about-cta-link {
    font-family: var(--font-menu);
    font-size: 15px;
    font-weight: 600;
    color: var(--cream2);
    transition: color 0.2s;
    text-decoration: underline;
    text-underline-offset: 4px;
}
.about-cta-link:hover { color: var(--amber); }
</style>

<?php include 'includes/footer.php'; ?>