<?php include 'includes/header.php'; ?>

<main class="cart-page">

    <!-- ========== BREADCRUMB ========== -->
    <div class="cart-breadcrumb">
        <a href="index.php">Trang chủ</a>
        <span>›</span>
        <span>Giỏ hàng</span>
    </div>

    <!-- ========== TIÊU ĐỀ ========== -->
    <div class="cart-header">
        <h1 class="cart-title">Giỏ hàng</h1>
        <span class="cart-header-count" id="cart-header-count"></span>
    </div>

    <!-- ========== NỘI DUNG ========== -->
    <div class="cart-body" id="cart-body">

        <!-- Trạng thái loading -->
        <div class="cart-loading" id="cart-loading">
            <div class="cart-spinner"></div>
        </div>

        <!-- Giỏ rỗng -->
        <div class="cart-empty" id="cart-empty" style="display:none;">
            <div class="cart-empty-icon">🛒</div>
            <h2 class="cart-empty-title">Giỏ hàng đang trống</h2>
            <p class="cart-empty-desc">Bạn chưa thêm sản phẩm nào vào giỏ. Khám phá các tác phẩm thủ công độc đáo của chúng tôi.</p>
            <div class="cart-empty-btns">
                <a href="shop.php" class="btn-primary">Khám phá cửa hàng</a>
                <a href="gom-su.php" class="cart-cat-link">Gốm sứ →</a>
                <a href="go-dieu-khac.php" class="cart-cat-link">Gỗ điêu khắc →</a>
            </div>
        </div>

        <!-- Có sản phẩm -->
        <div class="cart-layout" id="cart-layout" style="display:none;">

            <!-- ========== CỘT TRÁI: Sản phẩm + Thông tin nhận hàng ========== -->
            <div class="cart-main-col">

                <!-- Danh sách sản phẩm -->
                <div class="cart-items-col">
                    <div class="cart-items-header">
                        <span>Sản phẩm</span>
                        <span>Đơn giá</span>
                        <span>Số lượng</span>
                        <span>Thành tiền</span>
                    </div>

                    <div id="cart-items-list"></div>

                    <div class="cart-items-footer">
                        <button class="cart-clear-btn" id="cart-clear-btn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                            Xóa toàn bộ giỏ hàng
                        </button>
                        <a href="shop.php" class="cart-continue-link">← Tiếp tục mua sắm</a>
                    </div>
                </div>

                <!-- ========== THÔNG TIN NHẬN HÀNG ========== -->
                <div class="shipping-info-card" id="shipping-info-card">
                    <div class="sic-header">
                        <div class="sic-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <h3 class="sic-title">Thông tin nhận hàng</h3>
                            <p class="sic-sub">Điền thông tin để chúng tôi giao hàng đến bạn</p>
                        </div>
                        <!-- Tag đã điền -->
                        <div class="sic-filled-tag" id="sic-filled-tag" style="display:none;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            Đã điền
                        </div>
                    </div>

                    <div class="sic-body" id="sic-body">

                        <div class="sic-row">
                            <div class="sic-field">
                                <label class="sic-label">
                                    Họ và tên
                                    <span class="sic-required">*</span>
                                </label>
                                <input type="text" id="ship-name" class="sic-input" placeholder="Nguyễn Văn A" autocomplete="name">
                            </div>
                            <div class="sic-field">
                                <label class="sic-label">
                                    Số điện thoại
                                    <span class="sic-required">*</span>
                                </label>
                                <input type="tel" id="ship-phone" class="sic-input" placeholder="0901 234 567" autocomplete="tel">
                            </div>
                        </div>

                        <div class="sic-field">
                            <label class="sic-label">
                                Email
                            </label>
                            <input type="email" id="ship-email" class="sic-input" placeholder="email@example.com" autocomplete="email">
                        </div>

                        <!-- Địa chỉ: Tỉnh / Quận / Phường -->
                        <div class="sic-row sic-row-3">
                            <div class="sic-field">
                                <label class="sic-label">Tỉnh / Thành phố <span class="sic-required">*</span></label>
                                <div class="sic-select-wrap">
                                    <select id="ship-province" class="sic-select">
                                        <option value="">Chọn tỉnh/thành</option>
                                    </select>
                                    <svg class="sic-select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                            </div>
                            <div class="sic-field">
                                <label class="sic-label">Quận / Huyện <span class="sic-required">*</span></label>
                                <div class="sic-select-wrap">
                                    <select id="ship-district" class="sic-select" disabled>
                                        <option value="">Chọn quận/huyện</option>
                                    </select>
                                    <svg class="sic-select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                            </div>
                            <div class="sic-field">
                                <label class="sic-label">Phường / Xã <span class="sic-required">*</span></label>
                                <div class="sic-select-wrap">
                                    <select id="ship-ward" class="sic-select" disabled>
                                        <option value="">Chọn phường/xã</option>
                                    </select>
                                    <svg class="sic-select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                            </div>
                        </div>

                        <div class="sic-field">
                            <label class="sic-label">
                                Địa chỉ cụ thể
                                <span class="sic-required">*</span>
                            </label>
                            <input type="text" id="ship-address" class="sic-input"
                                   placeholder="Số nhà, tên đường, tòa nhà..." autocomplete="street-address">
                        </div>

                        <div class="sic-field">
                            <label class="sic-label">Ghi chú đơn hàng</label>
                            <textarea id="ship-note" class="sic-input sic-textarea"
                                      placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao, đóng gói kỹ..."></textarea>
                        </div>

                        <!-- Phương thức thanh toán -->
                        <div class="sic-field">
                            <label class="sic-label">Phương thức thanh toán <span class="sic-required">*</span></label>
                            <div class="sic-payment-opts">
                                <label class="sic-payment-opt">
                                    <input type="radio" name="payment" value="cod" checked>
                                    <span class="sic-payment-box">
                                        <span class="sic-pay-icon"></span>
                                        <span class="sic-pay-label">Thanh toán khi nhận hàng (COD)</span>
                                    </span>
                                </label>
                                
                            </div>
                        </div>

                        <!-- Thông tin chuyển khoản (hiện khi chọn bank) -->
                        <div class="sic-bank-info" id="sic-bank-info" style="display:none;">
                            <div class="sic-bank-row">
                                <span class="sic-bank-label">Ngân hàng</span>
                                <span class="sic-bank-value">Vietcombank</span>
                            </div>
                            <div class="sic-bank-row">
                                <span class="sic-bank-label">Số tài khoản</span>
                                <span class="sic-bank-value">
                                    1234 5678 9012
                                    <button class="sic-copy-btn" onclick="copyText('1234567890 12')">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                        Sao chép
                                    </button>
                                </span>
                            </div>
                            <div class="sic-bank-row">
                                <span class="sic-bank-label">Chủ tài khoản</span>
                                <span class="sic-bank-value">NGUYEN HA LINH</span>
                            </div>
                            <div class="sic-bank-row">
                                <span class="sic-bank-label">Nội dung CK</span>
                                <span class="sic-bank-value sic-bank-note" id="bank-transfer-note">SH + SĐT của bạn</span>
                            </div>
                        </div>

                        <!-- Lỗi validate -->
                        <div class="sic-error" id="sic-error" style="display:none;"></div>

                    </div>
                </div>

            </div>
            <!-- /cart-main-col -->

            <!-- ========== CỘT PHẢI: Summary ========== -->
            <div class="cart-summary-col">
                <div class="cart-summary-card">
                    <h3 class="cart-summary-title">Tóm tắt đơn hàng</h3>

                    <div class="cart-summary-rows">
                        <div class="cart-summary-row">
                            <span>Tạm tính (<span id="sum-count">0</span> sản phẩm)</span>
                            <span id="sum-subtotal">0đ</span>
                        </div>
                        <div class="cart-summary-row">
                            <span>Phí vận chuyển</span>
                            <span id="sum-shipping">—</span>
                        </div>
                        <div class="cart-summary-row cart-summary-discount" id="discount-row" style="display:none;">
                            <span>Giảm giá</span>
                            <span id="sum-discount" style="color:#16A34A;">—</span>
                        </div>
                    </div>

                    <!-- Mã giảm giá -->
                    <div class="cart-coupon">
                        <input type="text" id="coupon-input" class="cart-coupon-input" placeholder="Nhập mã giảm giá...">
                        <button class="cart-coupon-btn" id="coupon-btn">Áp dụng</button>
                    </div>
                    <div class="cart-coupon-msg" id="coupon-msg"></div>

                    <div class="cart-summary-divider"></div>

                    <div class="cart-summary-total">
                        <span>Tổng cộng</span>
                        <span id="sum-total" class="cart-total-price">0đ</span>
                    </div>

                    <div class="cart-summary-note">
                        Đã bao gồm VAT · Phí ship tính khi thanh toán
                    </div>

                    <!-- Nút thanh toán -->
                    <button class="cart-checkout-btn btn-primary btn-block" id="checkout-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Tiến hành thanh toán
                    </button>

                    <div class="cart-trust-badges">
                        <div class="trust-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Thanh toán bảo mật
                        </div>
                        <div class="trust-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><polyline points="16 21 16 13 8 13 8 21"/><polyline points="8 7 8 3 16 3 16 7"/></svg>
                            Đổi trả trong 7 ngày
                        </div>
                        <div class="trust-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Giao hàng 3–5 ngày
                        </div>
                    </div>
                </div>

                <!-- Sản phẩm đề xuất -->
               
            </div>

        </div>
    </div>
</main>

<!-- ========== MODAL XÁC NHẬN XÓA ========== -->
<div class="cart-confirm-overlay" id="confirm-overlay" style="display:none;">
    <div class="cart-confirm-box">
        <div class="cart-confirm-icon">🗑️</div>
        <h3>Xóa toàn bộ giỏ hàng?</h3>
        <p>Tất cả sản phẩm sẽ bị xóa. Hành động này không thể hoàn tác.</p>
        <div class="cart-confirm-btns">
            <button class="cart-confirm-cancel" id="confirm-cancel">Hủy</button>
            <button class="cart-confirm-ok btn-primary" id="confirm-ok">Xóa tất cả</button>
        </div>
    </div>
</div>

<style>
/* ============================================================
   CART PAGE
============================================================ */
.cart-page {
    max-width: 1280px;
    margin: 0 auto;
    padding: 32px 40px 100px;
    min-height: 70vh;
}

/* Breadcrumb */
.cart-breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-family: var(--font-mono); font-size: 12px;
    color: var(--ink3); margin-bottom: 32px;
}
.cart-breadcrumb a { color: var(--ink3); transition: color 0.2s; }
.cart-breadcrumb a:hover { color: var(--rust); }

/* Header */
.cart-header {
    display: flex; align-items: baseline; gap: 16px;
    margin-bottom: 40px;
    border-bottom: 1px solid var(--cream2);
    padding-bottom: 20px;
}
.cart-title {
    font-family: var(--font-display);
    font-size: 56px; font-weight: 500;
    color: var(--ink); letter-spacing: -1px;
}
.cart-header-count {
    font-family: var(--font-mono); font-size: 13px;
    color: var(--ink3); letter-spacing: 0.5px;
}

/* Loading spinner */
.cart-loading { display: flex; justify-content: center; padding: 80px 0; }
.cart-spinner {
    width: 36px; height: 36px;
    border: 3px solid var(--cream2);
    border-top-color: var(--rust);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Empty state */
.cart-empty { text-align: center; padding: 80px 20px; }
.cart-empty-icon { font-size: 64px; margin-bottom: 24px; }
.cart-empty-title {
    font-family: var(--font-display); font-size: 40px;
    font-weight: 500; color: var(--ink); margin-bottom: 12px;
}
.cart-empty-desc {
    font-family: var(--font-menu); font-size: 15px;
    color: var(--ink2); max-width: 420px; margin: 0 auto 32px; line-height: 1.7;
}
.cart-empty-btns { display: flex; align-items: center; justify-content: center; gap: 20px; flex-wrap: wrap; }
.cart-cat-link { font-family: var(--font-menu); font-size: 14px; color: var(--rust); font-weight: 600; transition: opacity 0.2s; }
.cart-cat-link:hover { opacity: 0.7; }

/* ---- Layout chính: cột trái rộng + cột phải summary ---- */
.cart-layout {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 32px;
    align-items: start;
}

/* Cột trái chứa cả items + shipping info */
.cart-main-col {
    display: flex;
    flex-direction: column;
    gap: 24px;
    min-width: 0;
}

/* ---- Danh sách sản phẩm ---- */
.cart-items-col {
    background: #fff;
    border: 1px solid var(--cream2);
    border-radius: 16px;
    overflow: hidden;
}

.cart-items-header {
    display: grid;
    grid-template-columns: 1fr 120px 140px 120px;
    gap: 16px;
    padding: 16px 20px;
    font-family: var(--font-mono); font-size: 11px;
    color: var(--ink3); text-transform: uppercase; letter-spacing: 1px;
    background: var(--page);
    border-bottom: 1px solid var(--cream2);
}

.cart-item {
    display: grid;
    grid-template-columns: 1fr 120px 140px 120px;
    gap: 16px; align-items: center;
    padding: 20px 20px;
    border-bottom: 1px solid var(--cream2);
    transition: background 0.15s;
}
.cart-item:last-child { border-bottom: none; }
.cart-item:hover { background: #FAFAF8; }

.cart-item-product { display: flex; align-items: center; gap: 14px; }
.cart-item-img {
    width: 80px; height: 80px; border-radius: 10px; overflow: hidden;
    flex-shrink: 0; background: var(--cream2); border: 1px solid var(--cream2);
}
.cart-item-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.cart-item-img-placeholder {
    width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
    font-size: 28px; background: linear-gradient(135deg, #E8E4DC, #D4CFC4);
}
.cart-item-name {
    font-family: var(--font-menu); font-size: 14px; font-weight: 600;
    color: var(--ink); line-height: 1.4; margin-bottom: 6px;
    cursor: pointer; transition: color 0.2s;
}
.cart-item-name:hover { color: var(--rust); }
.cart-item-remove {
    display: inline-flex; align-items: center; gap: 4px;
    font-family: var(--font-mono); font-size: 11px;
    color: var(--ink3); cursor: pointer; background: none; border: none; padding: 0;
    transition: color 0.2s;
}
.cart-item-remove:hover { color: #DC2626; }
.cart-item-price { font-family: var(--font-menu); font-size: 14px; color: var(--ink2); }
.cart-item-qty {
    display: flex; align-items: center;
    border: 1.5px solid var(--cream2); border-radius: 8px;
    overflow: hidden; width: fit-content; background: #fff;
}
.qty-btn {
    width: 36px; height: 36px; background: none; border: none;
    font-size: 18px; color: var(--ink); cursor: pointer;
    display: flex; align-items: center; justify-content: center; transition: 0.15s;
}
.qty-btn:hover { background: var(--page); color: var(--rust); }
.qty-num {
    width: 40px; text-align: center;
    font-family: var(--font-menu); font-size: 14px; font-weight: 600;
    color: var(--ink); border: none; background: #fff; outline: none;
}
.cart-item-total { font-family: var(--font-menu); font-size: 15px; font-weight: 700; color: var(--ink); }

.cart-items-footer {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 20px; background: var(--page);
    border-top: 1px solid var(--cream2);
}
.cart-clear-btn {
    display: flex; align-items: center; gap: 6px;
    background: none; border: none; font-family: var(--font-menu);
    font-size: 13px; color: var(--ink3); cursor: pointer; transition: color 0.2s;
}
.cart-clear-btn:hover { color: #DC2626; }
.cart-continue-link {
    font-family: var(--font-menu); font-size: 13px; color: var(--rust);
    font-weight: 600; transition: opacity 0.2s;
}
.cart-continue-link:hover { opacity: 0.7; }

/* ================================================================
   THÔNG TIN NHẬN HÀNG — Shipping Info Card
================================================================ */
.shipping-info-card {
    background: #fff;
    border: 1px solid var(--cream2);
    border-radius: 16px;
    overflow: hidden;
}

.sic-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 22px 24px;
    background: var(--page);
    border-bottom: 1px solid var(--cream2);
}

.sic-icon {
    width: 40px; height: 40px;
    background: var(--rust);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; flex-shrink: 0;
}

.sic-title {
    font-family: var(--font-display);
    font-size: 22px; font-weight: 500;
    color: var(--ink); margin-bottom: 2px;
}
.sic-sub {
    font-family: var(--font-menu); font-size: 13px; color: var(--ink3);
}

.sic-filled-tag {
    margin-left: auto;
    display: inline-flex; align-items: center; gap: 6px;
    background: #F0FDF4; color: #16A34A;
    font-family: var(--font-mono); font-size: 11px; font-weight: 600;
    padding: 6px 12px; border-radius: 20px;
    border: 1px solid #BBF7D0;
}

.sic-body {
    padding: 28px 24px;
    display: flex; flex-direction: column; gap: 18px;
}

.sic-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.sic-row-3 { grid-template-columns: 1fr 1fr 1fr; }

.sic-field { display: flex; flex-direction: column; gap: 7px; }

.sic-label {
    font-family: var(--font-menu); font-size: 13px;
    font-weight: 600; color: var(--ink);
    display: flex; align-items: center; gap: 4px;
}
.sic-required { color: var(--rust); }

.sic-input {
    font-family: var(--font-menu); font-size: 14px;
    padding: 12px 16px;
    border: 1.5px solid var(--cream2);
    border-radius: 10px;
    background: #fff; color: var(--ink);
    outline: none; transition: 0.2s; width: 100%;
}
.sic-input::placeholder { color: var(--ink3); }
.sic-input:focus { border-color: var(--rust); box-shadow: 0 0 0 3px rgba(216,90,48,0.1); }
.sic-input.valid { border-color: #16A34A; }
.sic-input.invalid { border-color: #DC2626; }

.sic-textarea { resize: vertical; min-height: 80px; }

/* Select dropdown */
.sic-select-wrap { position: relative; }
.sic-select {
    width: 100%; padding: 12px 40px 12px 16px;
    border: 1.5px solid var(--cream2); border-radius: 10px;
    background: #fff; color: var(--ink);
    font-family: var(--font-menu); font-size: 14px;
    outline: none; appearance: none; cursor: pointer;
    transition: 0.2s;
}
.sic-select:focus { border-color: var(--rust); box-shadow: 0 0 0 3px rgba(216,90,48,0.1); }
.sic-select:disabled { background: var(--page); color: var(--ink3); cursor: not-allowed; }
.sic-select-arrow {
    position: absolute; right: 14px; top: 50%;
    transform: translateY(-50%);
    pointer-events: none; color: var(--ink3);
}

/* Phương thức thanh toán */
.sic-payment-opts { display: flex; flex-direction: column; gap: 10px; }

.sic-payment-opt { cursor: pointer; }
.sic-payment-opt input[type=radio] { display: none; }

.sic-payment-box {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 18px;
    border: 1.5px solid var(--cream2);
    border-radius: 10px; background: #fff;
    transition: 0.2s; cursor: pointer;
}
.sic-payment-opt input:checked + .sic-payment-box {
    border-color: var(--rust);
    background: #FEF7F4;
    box-shadow: 0 0 0 3px rgba(216,90,48,0.08);
}
.sic-payment-box:hover { border-color: var(--ink2); }

.sic-pay-icon { font-size: 20px; }
.sic-pay-label {
    font-family: var(--font-menu); font-size: 14px;
    font-weight: 500; color: var(--ink);
}

/* Thông tin chuyển khoản */
.sic-bank-info {
    background: #F8F6F0;
    border: 1px solid var(--cream2);
    border-radius: 10px;
    padding: 16px 20px;
    display: flex; flex-direction: column; gap: 10px;
}
.sic-bank-row {
    display: flex; justify-content: space-between; align-items: center;
    font-family: var(--font-menu); font-size: 13px;
}
.sic-bank-label { color: var(--ink3); }
.sic-bank-value { font-weight: 600; color: var(--ink); display: flex; align-items: center; gap: 8px; }
.sic-bank-note { color: var(--rust); }
.sic-copy-btn {
    display: inline-flex; align-items: center; gap: 4px;
    background: var(--ink); color: #fff;
    border: none; border-radius: 6px;
    padding: 4px 10px; font-size: 11px; font-weight: 600;
    cursor: pointer; font-family: var(--font-mono);
    transition: background 0.2s;
}
.sic-copy-btn:hover { background: var(--rust); }

/* Error */
.sic-error {
    background: #FEF2F2; border: 1px solid #FECACA;
    color: #B91C1C; border-radius: 8px;
    padding: 12px 16px; font-family: var(--font-menu); font-size: 13px;
    display: flex; align-items: center; gap: 8px;
}

/* ---- Cột phải: Summary ---- */
.cart-summary-col { position: relative; }
.cart-summary-card {
    background: #fff; border: 1px solid var(--cream2);
    border-radius: 16px; padding: 28px 24px;
    position: sticky; top: 100px;
}
.cart-summary-title {
    font-family: var(--font-display); font-size: 26px; font-weight: 500;
    color: var(--ink); margin-bottom: 20px;
}
.cart-summary-rows { display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; }
.cart-summary-row {
    display: flex; justify-content: space-between;
    font-family: var(--font-menu); font-size: 14px; color: var(--ink2);
}
.cart-coupon { display: flex; gap: 8px; margin-bottom: 6px; }
.cart-coupon-input {
    flex: 1; padding: 10px 14px;
    border: 1.5px solid var(--cream2); border-radius: 8px;
    font-family: var(--font-menu); font-size: 13px; color: var(--ink);
    outline: none; transition: border-color 0.2s;
}
.cart-coupon-input:focus { border-color: var(--rust); }
.cart-coupon-input::placeholder { color: var(--ink3); }
.cart-coupon-btn {
    padding: 10px 16px; background: var(--ink); color: #fff;
    border: none; border-radius: 8px; font-family: var(--font-menu);
    font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;
    transition: background 0.2s;
}
.cart-coupon-btn:hover { background: var(--rust); }
.cart-coupon-msg { font-family: var(--font-mono); font-size: 12px; min-height: 18px; margin-bottom: 8px; }
.cart-summary-divider { height: 1px; background: var(--cream2); margin: 16px 0; }
.cart-summary-total {
    display: flex; justify-content: space-between; align-items: baseline;
    margin-bottom: 4px; font-family: var(--font-menu); font-size: 15px;
    font-weight: 700; color: var(--ink);
}
.cart-total-price {
    font-family: var(--font-display); font-size: 32px; font-weight: 600;
    color: var(--rust); letter-spacing: -0.5px;
}
.cart-summary-note {
    font-family: var(--font-mono); font-size: 11px; color: var(--ink3); margin-bottom: 20px;
}
.cart-checkout-btn {
    width: 100%; justify-content: center; padding: 15px;
    font-size: 15px; border-radius: 10px; gap: 10px; margin-bottom: 16px;
}
.cart-trust-badges { display: flex; flex-direction: column; gap: 8px; }
.trust-item {
    display: flex; align-items: center; gap: 8px;
    font-family: var(--font-mono); font-size: 11px; color: var(--ink3);
}
.cart-suggest {
    margin-top: 16px; background: #fff;
    border: 1px solid var(--cream2); border-radius: 12px; padding: 20px;
}
.cart-suggest-title {
    font-family: var(--font-mono); font-size: 11px; color: var(--ink3);
    text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px;
}
.cart-suggest-link {
    display: block; padding: 10px 0; font-family: var(--font-menu);
    font-size: 13px; font-weight: 600; color: var(--ink2);
    border-bottom: 1px solid var(--cream2); transition: color 0.2s;
}
.cart-suggest-link:last-child { border-bottom: none; }
.cart-suggest-link:hover { color: var(--rust); }

/* Modal */
.cart-confirm-overlay {
    position: fixed; inset: 0; background: rgba(44,44,42,0.6);
    backdrop-filter: blur(4px); z-index: 9999;
    display: flex; align-items: center; justify-content: center;
}
.cart-confirm-box {
    background: #fff; border-radius: 20px; padding: 48px 40px;
    text-align: center; max-width: 380px; width: 90%;
    box-shadow: 0 24px 64px rgba(0,0,0,0.15);
}
.cart-confirm-icon { font-size: 48px; margin-bottom: 16px; }
.cart-confirm-box h3 {
    font-family: var(--font-display); font-size: 28px;
    color: var(--ink); margin-bottom: 10px;
}
.cart-confirm-box p {
    font-family: var(--font-menu); font-size: 14px; color: var(--ink2);
    line-height: 1.6; margin-bottom: 28px;
}
.cart-confirm-btns { display: flex; gap: 12px; justify-content: center; }
.cart-confirm-cancel {
    flex: 1; padding: 13px; border: 1.5px solid var(--cream2);
    border-radius: 10px; background: #fff; font-family: var(--font-menu);
    font-size: 14px; font-weight: 600; color: var(--ink); cursor: pointer; transition: 0.2s;
}
.cart-confirm-cancel:hover { border-color: var(--ink); }
.cart-confirm-ok { flex: 1; padding: 13px; border-radius: 10px; font-size: 14px; justify-content: center; }
</style>

<script>
const COUPONS = {
    "HANDMADE10": { type: "percent", value: 10,    label: "Giảm 10%" },
    "SOUL20":     { type: "percent", value: 20,    label: "Giảm 20%" },
    "FREESHIP":   { type: "flat",    value: 30000, label: "Giảm 30.000đ" },
};
const SHIP_FREE_THRESHOLD = 500000;
const SHIP_FEE = 35000;
let activeCoupon = null;

/* ---- Helpers ---- */
function getCart() {
    try { return JSON.parse(localStorage.getItem("sh_cart") || "[]"); }
    catch(e) { return []; }
}
function saveCart(cart) {
    localStorage.setItem("sh_cart", JSON.stringify(cart));
    updateBadge();
}
function updateBadge() {
    const count = getCart().reduce(function(s,i){ return s + (i.qty||1); }, 0);
    const badge = document.getElementById("cart-count-badge");
    if (!badge) return;
    badge.textContent = count > 99 ? "99+" : count;
    badge.style.display = count > 0 ? "flex" : "none";
}
function fmt(n) { return Number(n).toLocaleString("vi-VN") + "đ"; }
function escHtml(s) {
    return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;")
                    .replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

/* ---- Render giỏ hàng ---- */
function renderCart() {
    var cart    = getCart();
    var loading = document.getElementById("cart-loading");
    var empty   = document.getElementById("cart-empty");
    var layout  = document.getElementById("cart-layout");
    var counter = document.getElementById("cart-header-count");

    if (loading) loading.style.display = "none";

    if (cart.length === 0) {
        if (empty)   empty.style.display   = "block";
        if (layout)  layout.style.display  = "none";
        if (counter) counter.textContent   = "";
        return;
    }
    if (empty)   empty.style.display   = "none";
    if (layout)  layout.style.display  = "grid";

    var totalItems = cart.reduce(function(s,i){ return s + (i.qty||1); }, 0);
    if (counter) counter.textContent = "(" + totalItems + " sản phẩm)";

    var list = document.getElementById("cart-items-list");
    if (!list) return;

    list.innerHTML = cart.map(function(item, idx) {
        var imgHTML = item.imageUrl
            ? '<img src="' + escHtml(item.imageUrl) + '" alt="' + escHtml(item.name) + '" onerror="this.parentElement.innerHTML=\'<div class=cart-item-img-placeholder>\u{1F3FA}</div>\'">'
            : '<div class="cart-item-img-placeholder">🏺</div>';
        return '<div class="cart-item">'
            + '<div class="cart-item-product">'
            +   '<div class="cart-item-img">' + imgHTML + '</div>'
            +   '<div>'
            +     '<div class="cart-item-name" onclick="location.href=\'product.php?id=' + item.id + '\'">' + escHtml(item.name) + '</div>'
            +     '<button class="cart-item-remove" onclick="removeItem(' + idx + ')">'
            +       '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
            +       ' Xóa</button>'
            +   '</div>'
            + '</div>'
            + '<div class="cart-item-price">' + fmt(item.price) + '</div>'
            + '<div class="cart-item-qty">'
            +   '<button class="qty-btn" onclick="changeQty(' + idx + ',-1)">−</button>'
            +   '<span class="qty-num">' + (item.qty||1) + '</span>'
            +   '<button class="qty-btn" onclick="changeQty(' + idx + ',1)">+</button>'
            + '</div>'
            + '<div class="cart-item-total">' + fmt(item.price * (item.qty||1)) + '</div>'
            + '</div>';
    }).join("");

    updateSummary(cart);
}

function updateSummary(cart) {
    var subtotal = cart.reduce(function(s,i){ return s + i.price*(i.qty||1); }, 0);
    var shipping = subtotal >= SHIP_FREE_THRESHOLD ? 0 : SHIP_FEE;
    var discount = 0;
    if (activeCoupon) {
        discount = activeCoupon.type === "percent"
            ? Math.round(subtotal * activeCoupon.value / 100)
            : activeCoupon.value;
    }
    var total = Math.max(0, subtotal + shipping - discount);

    function el(id){ return document.getElementById(id); }
    if (el("sum-count"))    el("sum-count").textContent    = cart.reduce(function(s,i){return s+(i.qty||1);},0);
    if (el("sum-subtotal")) el("sum-subtotal").textContent = fmt(subtotal);
    if (el("sum-shipping")) el("sum-shipping").innerHTML   = shipping === 0
        ? '<span style="color:#16A34A;font-weight:600;">Miễn phí 🎉</span>' : fmt(shipping);
    if (discount > 0) {
        if (el("discount-row")) el("discount-row").style.display = "flex";
        if (el("sum-discount")) el("sum-discount").textContent   = "− " + fmt(discount);
    } else {
        if (el("discount-row")) el("discount-row").style.display = "none";
    }
    if (el("sum-total")) el("sum-total").textContent = fmt(total);

    var noteEl = document.querySelector(".cart-summary-note");
    if (noteEl && shipping > 0) {
        var remaining = SHIP_FREE_THRESHOLD - subtotal;
        if (remaining > 0 && remaining < 200000)
            noteEl.innerHTML = "Thêm <strong>" + fmt(remaining) + "</strong> để được miễn phí vận chuyển 🚚";
    }
}

function changeQty(idx, delta) {
    var cart = getCart();
    if (!cart[idx]) return;
    cart[idx].qty = Math.max(1, (cart[idx].qty||1) + delta);
    saveCart(cart); renderCart();
}
function removeItem(idx) {
    var cart = getCart();
    cart.splice(idx, 1);
    saveCart(cart); renderCart();
}

/* ---- Địa chỉ ---- */
var provinceElG, districtElG, wardElG;

function getSavedShip() {
    try { return JSON.parse(localStorage.getItem("sh_ship_draft") || "null"); }
    catch(e) { return null; }
}
function saveDraftShip() {
    if (!provinceElG) return;
    var draft = {
        name:          (document.getElementById("ship-name")||{}).value || "",
        phone:         (document.getElementById("ship-phone")||{}).value || "",
        email:         (document.getElementById("ship-email")||{}).value || "",
        province_code: provinceElG.value,
        district_code: districtElG ? districtElG.value : "",
        ward_code:     wardElG ? wardElG.value : "",
        address:       (document.getElementById("ship-address")||{}).value || "",
        note:          (document.getElementById("ship-note")||{}).value || "",
    };
    localStorage.setItem("sh_ship_draft", JSON.stringify(draft));
    var tag = document.getElementById("sic-filled-tag");
    if (tag) tag.style.display = (draft.name && draft.phone && draft.province_code && draft.address) ? "inline-flex" : "none";
}
function restoreDraft() {
    var saved = getSavedShip();
    if (!saved) return;
    ["name","phone","email","address","note"].forEach(function(f) {
        var el = document.getElementById("ship-" + f);
        if (el && saved[f]) el.value = saved[f];
    });
}
function loadProvinces() {
    fetch("https://provinces.open-api.vn/api/p/")
    .then(function(r){ return r.json(); })
    .then(function(data) {
        data.sort(function(a,b){ return a.name.localeCompare(b.name,"vi"); });
        data.forEach(function(p) {
            var opt = document.createElement("option");
            opt.value = p.code; opt.textContent = p.name;
            provinceElG.appendChild(opt);
        });
        var saved = getSavedShip();
        if (saved && saved.province_code) {
            provinceElG.value = saved.province_code;
            loadDistricts(saved.province_code, saved.district_code, saved.ward_code);
        }
    })
    .catch(function(e){ console.warn("Không tải được tỉnh/thành:", e); });
}
function loadDistricts(pCode, selDistrict, selWard) {
    if (!districtElG) return;
    districtElG.disabled = true;
    districtElG.innerHTML = '<option value="">Đang tải...</option>';
    wardElG.innerHTML = '<option value="">Chọn phường/xã</option>';
    wardElG.disabled = true;
    fetch("https://provinces.open-api.vn/api/p/" + pCode + "?depth=2")
    .then(function(r){ return r.json(); })
    .then(function(data) {
        districtElG.innerHTML = '<option value="">Chọn quận/huyện</option>';
        data.districts.forEach(function(d) {
            var opt = document.createElement("option");
            opt.value = d.code; opt.textContent = d.name;
            districtElG.appendChild(opt);
        });
        districtElG.disabled = false;
        if (selDistrict) { districtElG.value = selDistrict; loadWards(selDistrict, selWard); }
    })
    .catch(function(){ districtElG.innerHTML = '<option value="">Lỗi tải dữ liệu</option>'; });
}
function loadWards(dCode, selWard) {
    if (!wardElG) return;
    wardElG.disabled = true;
    wardElG.innerHTML = '<option value="">Đang tải...</option>';
    fetch("https://provinces.open-api.vn/api/d/" + dCode + "?depth=2")
    .then(function(r){ return r.json(); })
    .then(function(data) {
        wardElG.innerHTML = '<option value="">Chọn phường/xã</option>';
        data.wards.forEach(function(w) {
            var opt = document.createElement("option");
            opt.value = w.code; opt.textContent = w.name;
            wardElG.appendChild(opt);
        });
        wardElG.disabled = false;
        if (selWard) wardElG.value = selWard;
    })
    .catch(function(){ wardElG.innerHTML = '<option value="">Lỗi tải dữ liệu</option>'; });
}

function showSicError(msg, focusId) {
    var errEl = document.getElementById("sic-error");
    if (!errEl) return;
    errEl.innerHTML = "⚠️ " + msg;
    errEl.style.display = "flex";
    errEl.scrollIntoView({ behavior: "smooth", block: "center" });
    var el = document.getElementById(focusId);
    if (el) { el.focus(); el.classList.add("invalid"); }
}

/* ================================================================
   DOMContentLoaded — tất cả event listener tại đây
================================================================ */
document.addEventListener("DOMContentLoaded", function() {

    renderCart();
    updateBadge();

    /* Địa chỉ */
    provinceElG = document.getElementById("ship-province");
    districtElG = document.getElementById("ship-district");
    wardElG     = document.getElementById("ship-ward");
    restoreDraft();
    if (provinceElG) loadProvinces();

    if (provinceElG) {
        provinceElG.addEventListener("change", function() {
            districtElG.innerHTML = '<option value="">Chọn quận/huyện</option>';
            wardElG.innerHTML     = '<option value="">Chọn phường/xã</option>';
            districtElG.disabled  = true;
            wardElG.disabled      = true;
            if (this.value) loadDistricts(this.value);
            saveDraftShip();
        });
    }
    if (districtElG) {
        districtElG.addEventListener("change", function() {
            wardElG.innerHTML = '<option value="">Chọn phường/xã</option>';
            wardElG.disabled  = true;
            if (this.value) loadWards(this.value);
            saveDraftShip();
        });
    }
    if (wardElG) wardElG.addEventListener("change", saveDraftShip);

    document.querySelectorAll(".sic-input").forEach(function(el) {
        el.addEventListener("input", saveDraftShip);
    });
    document.querySelectorAll(".sic-input, .sic-select").forEach(function(el) {
        el.addEventListener("input",  function(){ el.classList.remove("invalid"); });
        el.addEventListener("change", function(){ el.classList.remove("invalid"); });
    });

    /* Xóa giỏ */
    var clearBtn = document.getElementById("cart-clear-btn");
    var overlay  = document.getElementById("confirm-overlay");
    if (clearBtn)  clearBtn.addEventListener("click", function(){ if(overlay) overlay.style.display="flex"; });
    var cancelBtn = document.getElementById("confirm-cancel");
    if (cancelBtn) cancelBtn.addEventListener("click", function(){ if(overlay) overlay.style.display="none"; });
    var okBtn = document.getElementById("confirm-ok");
    if (okBtn) okBtn.addEventListener("click", function(){
        saveCart([]);
        if(overlay) overlay.style.display="none";
        renderCart();
    });

    /* Mã giảm giá */
    var couponBtn = document.getElementById("coupon-btn");
    if (couponBtn) couponBtn.addEventListener("click", function() {
        var code   = document.getElementById("coupon-input").value.trim().toUpperCase();
        var msgEl  = document.getElementById("coupon-msg");
        var coupon = COUPONS[code];
        if (!code) { msgEl.textContent = "Vui lòng nhập mã giảm giá."; msgEl.style.color = "#DC2626"; return; }
        if (!coupon) {
            msgEl.textContent = "Mã không hợp lệ hoặc đã hết hạn."; msgEl.style.color = "#DC2626";
            activeCoupon = null;
        } else {
            activeCoupon = coupon;
            msgEl.textContent = "✓ Áp dụng thành công: " + coupon.label; msgEl.style.color = "#16A34A";
        }
        updateSummary(getCart());
    });

    /* Thanh toán */
    document.querySelectorAll("input[name=payment]").forEach(function(radio) {
        radio.addEventListener("change", function() {
            var bankInfo = document.getElementById("sic-bank-info");
            if (bankInfo) bankInfo.style.display = this.value === "bank" ? "flex" : "none";
        });
    });
    var phoneEl = document.getElementById("ship-phone");
    if (phoneEl) phoneEl.addEventListener("input", function() {
        var noteEl = document.getElementById("bank-transfer-note");
        if (noteEl && this.value) noteEl.textContent = "SH" + this.value;
    });

    /* Checkout */
    var checkoutBtn = document.getElementById("checkout-btn");
    if (checkoutBtn) checkoutBtn.addEventListener("click", function() {
        var cart = getCart();
        if (cart.length === 0) return;

        var name     = (document.getElementById("ship-name")||{value:""}).value.trim();
        var phone    = (document.getElementById("ship-phone")||{value:""}).value.trim();
        var province = provinceElG ? provinceElG.value : "";
        var district = districtElG ? districtElG.value : "";
        var ward     = wardElG ? wardElG.value : "";
        var address  = (document.getElementById("ship-address")||{value:""}).value.trim();
        var errEl    = document.getElementById("sic-error");
        if (errEl) errEl.style.display = "none";

        if (!name)    { showSicError("Vui lòng nhập họ và tên người nhận.", "ship-name"); return; }
        if (!phone || !/^[0-9]{9,11}$/.test(phone)) { showSicError("Số điện thoại không hợp lệ (9–11 chữ số).", "ship-phone"); return; }
        if (!province){ showSicError("Vui lòng chọn tỉnh / thành phố.", "ship-province"); return; }
        if (!district){ showSicError("Vui lòng chọn quận / huyện.", "ship-district"); return; }
        if (!ward)    { showSicError("Vui lòng chọn phường / xã.", "ship-ward"); return; }
        if (!address) { showSicError("Vui lòng nhập địa chỉ cụ thể.", "ship-address"); return; }

        var provinceName = provinceElG.options[provinceElG.selectedIndex] ? provinceElG.options[provinceElG.selectedIndex].text : "";
        var districtName = districtElG.options[districtElG.selectedIndex] ? districtElG.options[districtElG.selectedIndex].text : "";
        var wardName     = wardElG.options[wardElG.selectedIndex] ? wardElG.options[wardElG.selectedIndex].text : "";

        var subtotal = cart.reduce(function(s,i){ return s+i.price*(i.qty||1); }, 0);
        var shipping = subtotal >= SHIP_FREE_THRESHOLD ? 0 : SHIP_FEE;
        var discountAmt = activeCoupon
            ? (activeCoupon.type === "percent" ? Math.round(subtotal*activeCoupon.value/100) : activeCoupon.value)
            : 0;
        var total = Math.max(0, subtotal + shipping - discountAmt);
        var paymentMethod = document.querySelector("input[name=payment]:checked");

        var payload = {
            receiver_name:  name,
            receiver_phone: phone,
            receiver_email: (document.getElementById("ship-email")||{value:""}).value.trim(),
            province: provinceName, district: districtName, ward: wardName,
            address:  address,
            note:     (document.getElementById("ship-note")||{value:""}).value.trim(),
            payment_method: paymentMethod ? paymentMethod.value : "cod",
            coupon_code: (document.getElementById("coupon-input")||{value:""}).value.trim().toUpperCase(),
            subtotal: subtotal, shipping_fee: shipping, discount: discountAmt, total: total,
            items: cart,
        };

        var btn      = document.getElementById("checkout-btn");
        var origHTML = btn.innerHTML;
        btn.disabled  = true;
        btn.innerHTML = "Đang xử lý...";

        fetch("api/place_order.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.success) {
                localStorage.removeItem("sh_cart");
                localStorage.removeItem("sh_ship_draft");
                window.location.href = "order-success.php?code=" + data.order_code;
            } else {
                btn.disabled = false; btn.innerHTML = origHTML;
                if (errEl) { errEl.innerHTML = "⚠️ " + data.message; errEl.style.display = "flex"; errEl.scrollIntoView({behavior:"smooth",block:"center"}); }
            }
        })
        .catch(function() {
            btn.disabled = false; btn.innerHTML = origHTML;
            alert("Lỗi kết nối. Vui lòng thử lại.");
        });
    });

}); // end DOMContentLoaded
</script>

<?php include 'includes/footer.php'; ?>