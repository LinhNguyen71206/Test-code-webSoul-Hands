<?php
require_once 'config/database.php';

// ============================================================
// XỬ LÝ FORM ĐẶT LỊCH (POST)
// ============================================================
$booking_success = false;
$booking_error   = '';
$form_data       = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Đọc & làm sạch dữ liệu
    $full_name    = trim($_POST['full_name']    ?? '');
    $phone        = trim($_POST['phone']        ?? '');
    $email        = trim($_POST['email']        ?? '');
    $workshop_id  = (int)($_POST['workshop_id']  ?? 0);
    $booking_date = trim($_POST['booking_date'] ?? '');
    $participants = max(1, (int)($_POST['participants'] ?? 1));
    $notes        = trim($_POST['notes']        ?? '');

    // Giữ lại dữ liệu để điền lại form nếu lỗi
    $form_data = compact('full_name', 'phone', 'email', 'workshop_id', 'booking_date', 'participants', 'notes');

    // Validate
    if (empty($full_name) || empty($phone) || !$workshop_id || empty($booking_date)) {
        $booking_error = 'Vui lòng điền đầy đủ các trường bắt buộc (*)';
    } elseif (!preg_match('/^[0-9]{9,11}$/', $phone)) {
        $booking_error = 'Số điện thoại không hợp lệ (9–11 chữ số)';
    } elseif (strtotime($booking_date) < strtotime('today')) {
        $booking_error = 'Ngày đặt lịch phải từ hôm nay trở đi';
    } else {
        // Lưu vào DB
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user_id_session = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

        $stmt = $conn->prepare(
            "INSERT INTO workshop_bookings 
             (user_id, full_name, phone, email, workshop_id, booking_date, participants, notes, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->bind_param('isssisss',
            $user_id_session, $full_name, $phone, $email,
            $workshop_id, $booking_date, $participants, $notes
        );
        if ($stmt->execute()) {
            $booking_success = true;
            $form_data = []; // Xóa form sau khi thành công
        } else {
            $booking_error = 'Có lỗi xảy ra, vui lòng thử lại.';
        }
    }
}

// ============================================================
// LẤY DANH SÁCH WORKSHOP
// ============================================================
$workshops    = [];
$result_ws    = $conn->query("SELECT * FROM workshops ORDER BY id ASC");
while ($ws = $result_ws->fetch_assoc()) {
    $workshops[$ws['id']] = $ws;
}

// Workshop được chọn trước từ URL ?ws_id=
$preselect_ws = isset($_GET['ws_id']) ? (int)$_GET['ws_id'] : 0;

// Ảnh fallback Unsplash
$ws_imgs = [
    1 => 'https://sf-static.upanhlaylink.com/img/image_20260510a80aac45cbed8b6d1060d0f71f954605.jpg',
    2 => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?q=80&w=900&auto=format&fit=crop',
    3 => 'https://sf-static.upanhlaylink.com/img/image_202605104bd5e177a488151c41e99481bd8069a9.jpg',
];
?>

<?php include 'includes/header.php'; ?>

<main class="ws-page">

    <!-- ========== HERO ========== -->
    <section class="ws-hero">
        <div class="ws-hero-text">
            <div class="section-eyebrow">Trải nghiệm thực tế</div>
            <h1 class="ws-hero-title">Workshop<br><em>thủ công</em></h1>
            <p class="ws-hero-sub">Nhóm nhỏ từ 5–10 người, nghệ nhân kèm trực tiếp. Bạn sẽ mang về không chỉ một sản phẩm — mà cả một câu chuyện tự kể bằng đôi tay mình.</p>
        </div>
        <div class="ws-hero-cards">
            <?php foreach ($workshops as $id => $ws):
                $img = !empty($ws['image_url']) ? $ws['image_url'] : ($ws_imgs[$id] ?? $ws_imgs[1]);
            ?>
            <div class="ws-hero-card" onclick="document.getElementById('booking-form').scrollIntoView({behavior:'smooth'}); document.getElementById('workshop_id').value=<?= $id ?>; highlightCard(<?= $id ?>);">
                <img src="<?= $img ?>" alt="<?= htmlspecialchars($ws['name']) ?>" class="ws-hero-card-img">
                <div class="ws-hero-card-overlay"></div>
                <div class="ws-hero-card-body">
                    
                    <h3 class="ws-hero-card-name"><?= htmlspecialchars($ws['name']) ?></h3>
                    <div class="ws-hero-card-meta"><?= htmlspecialchars($ws['duration_info']) ?></div>
                    <div class="ws-hero-card-price"><?= number_format($ws['price'], 0, ',', '.') ?>đ / người</div>
                    <span class="ws-hero-card-btn">Chọn workshop này →</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ========== THÔNG TIN & FORM ĐẶT LỊCH ========== -->
    <section class="ws-booking-section" id="booking-form">
        <div class="ws-booking-inner">

            <!-- Cột trái: Thông tin thêm -->
            <div class="ws-info-col">
                <h2 class="ws-info-title">Quy trình<br>đặt lịch</h2>

                <div class="ws-steps">
                    <div class="ws-step">
                        <div class="ws-step-num">01</div>
                        <div>
                            <div class="ws-step-title">Điền thông tin</div>
                            <div class="ws-step-desc">Chọn workshop, ngày và số người tham gia. Chúng tôi xác nhận trong 24h.</div>
                        </div>
                    </div>
                    <div class="ws-step">
                        <div class="ws-step-num">02</div>
                        <div>
                            <div class="ws-step-title">Nhận xác nhận</div>
                            <div class="ws-step-desc">Nhân viên liên hệ qua điện thoại/email để xác nhận lịch và hướng dẫn thanh toán.</div>
                        </div>
                    </div>
                    <div class="ws-step">
                        <div class="ws-step-num">03</div>
                        <div>
                            <div class="ws-step-title">Đến & trải nghiệm</div>
                            <div class="ws-step-desc">Đến đúng giờ, mặc thoải mái. Nghệ nhân sẽ hướng dẫn từng bước một.</div>
                        </div>
                    </div>
                </div>

                <div class="ws-info-note">
                    <div class="ws-note-icon">📍</div>
                    <div>
                        <div class="ws-note-title">Địa điểm</div>
                        <div class="ws-note-desc">Làng gốm Bát Tràng, Gia Lâm, Hà Nội<br>Cách trung tâm ~14km</div>
                    </div>
                </div>
                <div class="ws-info-note">
                    <div class="ws-note-icon">👥</div>
                    <div>
                        <div class="ws-note-title">Nhóm nhỏ</div>
                        <div class="ws-note-desc">Tối đa 10 người / buổi<br>Đặt trước ít nhất 2 ngày</div>
                    </div>
                </div>
                <div class="ws-info-note">
                    <div class="ws-note-icon">✅</div>
                    <div>
                        <div class="ws-note-title">Bao gồm</div>
                        <div class="ws-note-desc">Nguyên liệu, dụng cụ, nước uống<br>Phí nung và hoàn thiện sản phẩm</div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Form đặt lịch -->
            <div class="ws-form-col">
                <div class="ws-form-card">
                    <h2 class="ws-form-title">Đặt lịch ngay</h2>
                    <p class="ws-form-sub">Điền thông tin bên dưới, chúng tôi sẽ liên hệ xác nhận trong 24 giờ.</p>

                    <?php if ($booking_success): ?>
                    <!-- THÔNG BÁO THÀNH CÔNG -->
                    <div class="ws-success-box">
                        <div class="ws-success-icon">🎉</div>
                        <h3>Đặt lịch thành công!</h3>
                        <p>Chúng tôi sẽ liên hệ với bạn trong vòng 24 giờ để xác nhận lịch và hướng dẫn thanh toán.</p>
                        <a href="workshop.php" class="btn-primary" style="margin-top:16px;">Đặt thêm lịch khác</a>
                    </div>

                    <?php else: ?>

                    <?php if ($booking_error): ?>
                    <div class="ws-error-box">
                        ⚠️ <?= htmlspecialchars($booking_error) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="#booking-form" class="ws-booking-form" id="ws-form">

                        <!-- CHỌN WORKSHOP -->
                        <div class="ws-form-group">
                            <label class="ws-form-label">Workshop <span class="required">*</span></label>
                            <div class="ws-select-cards" id="ws-select-group">
                                <?php foreach ($workshops as $id => $ws):
                                    $selected = ($form_data['workshop_id'] ?? $preselect_ws) == $id;
                                ?>
                                <label class="ws-select-card <?= $selected ? 'selected' : '' ?>" data-ws-id="<?= $id ?>">
                                    <input type="radio" name="workshop_id" value="<?= $id ?>"
                                           id="ws_<?= $id ?>"
                                           <?= $selected ? 'checked' : '' ?>
                                           style="display:none">
                                    
                                    <span class="ws-sc-name"><?= htmlspecialchars($ws['name']) ?></span>
                                    <span class="ws-sc-meta"><?= htmlspecialchars($ws['duration_info']) ?></span>
                                    <span class="ws-sc-price"><?= number_format($ws['price'], 0, ',', '.') ?>đ</span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <!-- Hidden input để giữ giá trị workshop_id khi submit -->
                            <input type="hidden" name="workshop_id" id="workshop_id"
                                   value="<?= $form_data['workshop_id'] ?? $preselect_ws ?>">
                        </div>

                        <!-- THÔNG TIN CÁ NHÂN -->
                        <div class="ws-form-row">
                            <div class="ws-form-group">
                                <label class="ws-form-label" for="full_name">Họ và tên <span class="required">*</span></label>
                                <input type="text" id="full_name" name="full_name" class="ws-form-input"
                                       placeholder="Nguyễn Văn A"
                                       value="<?= htmlspecialchars($form_data['full_name'] ?? '') ?>" required>
                            </div>
                            <div class="ws-form-group">
                                <label class="ws-form-label" for="phone">Số điện thoại <span class="required">*</span></label>
                                <input type="tel" id="phone" name="phone" class="ws-form-input"
                                       placeholder="0901 234 567"
                                       value="<?= htmlspecialchars($form_data['phone'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="ws-form-group">
                            <label class="ws-form-label" for="email">Email</label>
                            <input type="email" id="email" name="email" class="ws-form-input"
                                   placeholder="email@example.com"
                                   value="<?= htmlspecialchars($form_data['email'] ?? '') ?>">
                        </div>

                        <!-- NGÀY & SỐ NGƯỜI -->
                        <div class="ws-form-row">
                            <div class="ws-form-group">
                                <label class="ws-form-label" for="booking_date">Ngày tham gia <span class="required">*</span></label>
                                <input type="date" id="booking_date" name="booking_date" class="ws-form-input"
                                       min="<?= date('Y-m-d') ?>"
                                       value="<?= htmlspecialchars($form_data['booking_date'] ?? '') ?>" required>
                            </div>
                            <div class="ws-form-group">
                                <label class="ws-form-label" for="participants">Số người tham gia <span class="required">*</span></label>
                                <div class="ws-counter">
                                    <button type="button" class="ws-counter-btn" id="btn-minus">−</button>
                                    <input type="number" id="participants" name="participants" class="ws-counter-input"
                                           min="1" max="10"
                                           value="<?= $form_data['participants'] ?? 1 ?>">
                                    <button type="button" class="ws-counter-btn" id="btn-plus">+</button>
                                </div>
                                <span class="ws-form-hint">Tối đa 10 người / buổi</span>
                            </div>
                        </div>

                        <!-- GHI CHÚ -->
                        <div class="ws-form-group">
                            <label class="ws-form-label" for="notes">Ghi chú thêm</label>
                            <textarea id="notes" name="notes" class="ws-form-input ws-textarea"
                                      placeholder="Ví dụ: nhóm có trẻ em, yêu cầu đặc biệt..."><?= htmlspecialchars($form_data['notes'] ?? '') ?></textarea>
                        </div>

                        <!-- HIỂN THỊ TÓM TẮT GIÁ -->
                        <div class="ws-price-summary" id="price-summary">
                            <div class="ws-ps-row">
                                <span id="ps-name">Chọn workshop để xem giá</span>
                                <span id="ps-total"></span>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary btn-block ws-submit-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.15 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.07 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16z"/></svg>
                            Gửi yêu cầu đặt lịch
                        </button>

                        <p class="ws-form-footer-note">Chúng tôi sẽ liên hệ xác nhận trong vòng 24 giờ · Miễn phí hủy trước 48 giờ</p>

                    </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </section>

</main>

<style>
/* ============================================================
   WORKSHOP PAGE — STYLES
============================================================ */
.ws-page { background: var(--page); min-height: 80vh; }

/* --- HERO --- */
.ws-hero {
    display: grid;
    grid-template-columns: 420px 1fr;
    gap: 60px;
    padding: 80px 40px;
    max-width: 1440px;
    margin: 0 auto;
    align-items: center;
}

.ws-hero-title {
    font-family: var(--font-display);
    font-size: 64px;
    font-weight: 500;
    color: var(--ink);
    line-height: 1.1;
    margin-bottom: 20px;
    letter-spacing: -1px;
}
.ws-hero-title em { font-style: italic; color: var(--rust); }

.ws-hero-sub {
    font-family: var(--font-menu);
    font-size: 15px;
    color: var(--ink2);
    line-height: 1.7;
    max-width: 360px;
}

/* Cards ảnh hero */
.ws-hero-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.ws-hero-card {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    aspect-ratio: 2/3;
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.ws-hero-card:hover { transform: translateY(-8px); box-shadow: 0 24px 56px rgba(0,0,0,0.15); }

.ws-hero-card-img {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}
.ws-hero-card:hover .ws-hero-card-img { transform: scale(1.06); }

.ws-hero-card-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.2) 50%, transparent 100%);
}

.ws-hero-card-body {
    position: absolute; bottom: 0; left: 0; right: 0;
    padding: 20px 18px 24px;
    z-index: 2;
}
.ws-hero-card-icon { font-size: 24px; margin-bottom: 6px; }
.ws-hero-card-name {
    font-family: var(--font-menu);
    font-size: 16px; font-weight: 700;
    color: #fff; margin-bottom: 4px;
}
.ws-hero-card-meta {
    font-family: var(--font-mono);
    font-size: 11px; color: rgba(255,255,255,0.6); margin-bottom: 4px;
}
.ws-hero-card-price {
    font-family: var(--font-menu);
    font-size: 14px; font-weight: 600; color: var(--amber);
    margin-bottom: 12px;
}
.ws-hero-card-btn {
    display: inline-flex;
    background: var(--rust); color: #FAECE7;
    font-family: var(--font-menu); font-size: 12px; font-weight: 600;
    padding: 7px 16px; border-radius: 20px;
    opacity: 0; transform: translateY(8px);
    transition: opacity 0.25s, transform 0.25s;
}
.ws-hero-card:hover .ws-hero-card-btn { opacity: 1; transform: translateY(0); }

/* --- BOOKING SECTION --- */
.ws-booking-section {
    background: #fff;
    border-top: 1px solid var(--cream2);
    padding: 80px 40px 100px;
}

.ws-booking-inner {
    max-width: 1200px; margin: 0 auto;
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 80px;
    align-items: start;
}

/* Cột trái — Info */
.ws-info-title {
    font-family: var(--font-display);
    font-size: 40px; font-weight: 500;
    color: var(--ink); line-height: 1.2;
    margin-bottom: 40px;
}

.ws-steps { display: flex; flex-direction: column; gap: 28px; margin-bottom: 40px; }

.ws-step {
    display: flex; gap: 16px; align-items: flex-start;
}
.ws-step-num {
    font-family: var(--font-mono); font-size: 11px;
    color: var(--rust); font-weight: 600; letter-spacing: 1px;
    padding-top: 2px; flex-shrink: 0; width: 24px;
}
.ws-step-title {
    font-family: var(--font-menu); font-size: 15px;
    font-weight: 600; color: var(--ink); margin-bottom: 4px;
}
.ws-step-desc {
    font-family: var(--font-menu); font-size: 13px;
    color: var(--ink2); line-height: 1.6;
}

.ws-info-note {
    display: flex; gap: 14px; align-items: flex-start;
    padding: 16px; border-radius: 10px;
    background: var(--page); margin-bottom: 10px;
}
.ws-note-icon { font-size: 20px; flex-shrink: 0; }
.ws-note-title {
    font-family: var(--font-menu); font-size: 13px;
    font-weight: 700; color: var(--ink); margin-bottom: 3px;
}
.ws-note-desc {
    font-family: var(--font-menu); font-size: 13px;
    color: var(--ink2); line-height: 1.5;
}

/* Cột phải — Form card */
.ws-form-card {
    background: var(--page);
    border: 1px solid var(--cream2);
    border-radius: 20px;
    padding: 48px 44px;
    position: sticky;
    top: 120px;
}

.ws-form-title {
    font-family: var(--font-display);
    font-size: 36px; font-weight: 500;
    color: var(--ink); margin-bottom: 6px;
}
.ws-form-sub {
    font-family: var(--font-menu);
    font-size: 14px; color: var(--ink2);
    margin-bottom: 32px; line-height: 1.6;
}

/* Chọn workshop dạng card radio */
.ws-select-cards {
    display: flex; flex-direction: column; gap: 10px;
    margin-bottom: 4px;
}

.ws-select-card {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px;
    border: 1.5px solid var(--cream2);
    border-radius: 12px;
    cursor: pointer;
    transition: 0.2s;
    background: #fff;
}
.ws-select-card:hover { border-color: var(--ink2); }
.ws-select-card.selected {
    border-color: var(--rust);
    background: #FAECE7;
}

.ws-sc-icon { font-size: 20px; }
.ws-sc-name {
    font-family: var(--font-menu); font-size: 14px; font-weight: 600;
    color: var(--ink); flex: 1;
}
.ws-sc-meta {
    font-family: var(--font-mono); font-size: 11px; color: var(--ink3);
}
.ws-sc-price {
    font-family: var(--font-menu); font-size: 14px; font-weight: 700;
    color: var(--rust); white-space: nowrap;
}

/* Form fields */
.ws-booking-form { display: flex; flex-direction: column; gap: 20px; }
.ws-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.ws-form-group { display: flex; flex-direction: column; gap: 8px; }

.ws-form-label {
    font-family: var(--font-menu); font-size: 13px; font-weight: 600; color: var(--ink);
}
.required { color: var(--rust); }

.ws-form-input {
    font-family: var(--font-menu); font-size: 15px;
    padding: 13px 16px;
    border: 1.5px solid var(--cream2);
    border-radius: 10px;
    background: #fff; color: var(--ink);
    outline: none; transition: 0.2s;
    width: 100%;
}
.ws-form-input::placeholder { color: var(--ink3); }
.ws-form-input:focus {
    border-color: var(--rust);
    box-shadow: 0 0 0 3px rgba(216, 90, 48, 0.1);
}
.ws-textarea { resize: vertical; min-height: 90px; }
.ws-form-hint { font-family: var(--font-mono); font-size: 11px; color: var(--ink3); }

/* Counter số người */
.ws-counter {
    display: flex; align-items: center;
    border: 1.5px solid var(--cream2);
    border-radius: 10px; overflow: hidden;
    background: #fff;
}
.ws-counter-btn {
    width: 44px; height: 48px;
    background: none; border: none;
    font-size: 20px; color: var(--ink);
    cursor: pointer; transition: 0.15s;
    display: flex; align-items: center; justify-content: center;
}
.ws-counter-btn:hover { background: var(--page); color: var(--rust); }
.ws-counter-input {
    flex: 1; border: none;
    text-align: center;
    font-family: var(--font-menu); font-size: 16px; font-weight: 600;
    color: var(--ink); background: #fff;
    outline: none; padding: 0;
}
/* Ẩn spinner của input number */
.ws-counter-input::-webkit-inner-spin-button,
.ws-counter-input::-webkit-outer-spin-button { -webkit-appearance: none; }

/* Tóm tắt giá */
.ws-price-summary {
    background: var(--ink);
    border-radius: 10px;
    padding: 16px 20px;
}
.ws-ps-row {
    display: flex; justify-content: space-between; align-items: center;
    font-family: var(--font-menu); font-size: 14px; color: var(--cream2);
}
#ps-total { font-weight: 700; color: var(--amber); font-size: 16px; }

/* Nút submit */
.ws-submit-btn {
    padding: 16px 24px; font-size: 15px;
    border-radius: 10px; justify-content: center;
    gap: 10px;
}

.ws-form-footer-note {
    font-family: var(--font-mono); font-size: 11px;
    color: var(--ink3); text-align: center; line-height: 1.6;
}

/* Success & Error */
.ws-success-box {
    text-align: center; padding: 40px 20px;
}
.ws-success-icon { font-size: 56px; margin-bottom: 16px; }
.ws-success-box h3 {
    font-family: var(--font-display); font-size: 28px;
    color: var(--ink); margin-bottom: 12px;
}
.ws-success-box p {
    font-family: var(--font-menu); font-size: 15px;
    color: var(--ink2); line-height: 1.6; max-width: 340px; margin: 0 auto;
}

.ws-error-box {
    background: #FEF2F2; border: 1px solid #FECACA;
    color: #B91C1C; border-radius: 8px;
    padding: 14px 18px;
    font-family: var(--font-menu); font-size: 14px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // =========================================
    // 1. Radio card workshop (chọn bằng click)
    // =========================================
    const wsCards      = document.querySelectorAll('.ws-select-card');
    const hiddenWsId   = document.getElementById('workshop_id');
    const priceSummary = document.getElementById('price-summary');
    const psName       = document.getElementById('ps-name');
    const psTotal      = document.getElementById('ps-total');
    const partInput    = document.getElementById('participants');

    // Giá workshop — lấy từ PHP
    const wsPrices = {
        <?php foreach ($workshops as $id => $ws): ?>
        <?= $id ?>: { name: "<?= addslashes($ws['name']) ?>", price: <?= (int)$ws['price'] ?> },
        <?php endforeach; ?>
    };

    function updatePriceSummary() {
        const wsId = parseInt(hiddenWsId.value);
        const qty  = parseInt(partInput ? partInput.value : 1) || 1;
        if (wsId && wsPrices[wsId]) {
            const ws    = wsPrices[wsId];
            const total = ws.price * qty;
            psName.textContent  = ws.name + ' × ' + qty + ' người';
            psTotal.textContent = total.toLocaleString('vi-VN') + 'đ';
        } else {
            psName.textContent  = 'Chọn workshop để xem giá';
            psTotal.textContent = '';
        }
    }

    wsCards.forEach(function (card) {
        card.addEventListener('click', function () {
            wsCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            const radio = card.querySelector('input[type=radio]');
            if (radio) { radio.checked = true; }
            const wsId = card.getAttribute('data-ws-id');
            if (hiddenWsId) hiddenWsId.value = wsId;
            updatePriceSummary();
        });
    });

    if (partInput) {
        partInput.addEventListener('input', updatePriceSummary);
    }

    // Cập nhật ngay khi load nếu đã có giá trị
    updatePriceSummary();

    // =========================================
    // 2. Nút tăng / giảm số người
    // =========================================
    const btnMinus = document.getElementById('btn-minus');
    const btnPlus  = document.getElementById('btn-plus');

    if (btnMinus && btnPlus && partInput) {
        btnMinus.addEventListener('click', function () {
            const v = parseInt(partInput.value) || 1;
            if (v > 1) { partInput.value = v - 1; updatePriceSummary(); }
        });
        btnPlus.addEventListener('click', function () {
            const v = parseInt(partInput.value) || 1;
            if (v < 10) { partInput.value = v + 1; updatePriceSummary(); }
        });
    }

    // =========================================
    // 3. Ngăn submit nếu chưa chọn workshop
    // =========================================
    const wsForm = document.getElementById('ws-form');
    if (wsForm) {
        wsForm.addEventListener('submit', function (e) {
            if (!hiddenWsId || !hiddenWsId.value) {
                e.preventDefault();
                alert('Vui lòng chọn một workshop trước khi đặt lịch.');
            }
        });
    }
});

// Hàm highlight card từ hero section
function highlightCard(wsId) {
    const cards = document.querySelectorAll('.ws-select-card');
    cards.forEach(c => c.classList.remove('selected'));
    const target = document.querySelector('.ws-select-card[data-ws-id="' + wsId + '"]');
    if (target) {
        target.classList.add('selected');
        const radio = target.querySelector('input[type=radio]');
        if (radio) radio.checked = true;
        const hidden = document.getElementById('workshop_id');
        if (hidden) hidden.value = wsId;
    }
}
</script>

<?php include 'includes/footer.php'; ?>