<?php
ob_start();
session_start();
require_once 'config/database.php';

// Lấy mã đơn từ URL
$order_code = trim($_GET['code'] ?? '');
$order      = null;
$items      = [];

if ($order_code) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_code = ? LIMIT 1");
    $stmt->bind_param('s', $order_code);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if ($order) {
        $i_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $i_stmt->bind_param('i', $order['id']);
        $i_stmt->execute();
        $items = $i_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$payment_labels = [
    'cod'  => 'Thanh toán khi nhận hàng (COD)',
    'bank' => 'Chuyển khoản ngân hàng',
    'momo' => 'Ví MoMo',
];
$status_labels = [
    'pending'   => ['label' => 'Chờ xác nhận', 'color' => '#F59E0B'],
    'confirmed' => ['label' => 'Đã xác nhận',  'color' => '#3B82F6'],
    'shipping'  => ['label' => 'Đang giao',     'color' => '#8B5CF6'],
    'delivered' => ['label' => 'Đã giao',       'color' => '#16A34A'],
    'cancelled' => ['label' => 'Đã hủy',        'color' => '#DC2626'],
];
?>
<?php include 'includes/header.php'; ?>

<main class="order-success-page">

<?php if (!$order): ?>
    <!-- Không tìm thấy đơn -->
    <div class="os-not-found">
        <div class="os-not-found-icon">🔍</div>
        <h2>Không tìm thấy đơn hàng</h2>
        <p>Mã đơn hàng không tồn tại hoặc đã hết hiệu lực.</p>
        <a href="index.php" class="btn-primary">Về trang chủ</a>
    </div>

<?php else: ?>

    <!-- ========== BANNER THÀNH CÔNG ========== -->
    <div class="os-banner">
        <div class="os-banner-inner">
            <div class="os-check-circle">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div>
                <h1 class="os-title">Đặt hàng thành công! 🎉</h1>
                <p class="os-subtitle">Cảm ơn bạn đã tin tưởng Soul & Hands. Chúng tôi sẽ liên hệ xác nhận trong vòng 24 giờ.</p>
            </div>
        </div>
        <div class="os-code-box">
            <span class="os-code-label">Mã đơn hàng</span>
            <span class="os-code-value"><?= htmlspecialchars($order['order_code']) ?></span>
            <button class="os-copy-btn" onclick="copyOrderCode('<?= htmlspecialchars($order['order_code']) ?>')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Sao chép
            </button>
        </div>
    </div>

    <div class="os-layout">

        <!-- Cột trái: Chi tiết đơn -->
        <div class="os-main-col">

            <!-- Sản phẩm đã đặt -->
            <div class="os-card">
                <h3 class="os-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    Sản phẩm đã đặt
                </h3>
                <div class="os-items">
                    <?php foreach ($items as $item): ?>
                    <div class="os-item">
                        <div class="os-item-img">
                            <?php if (!empty($item['product_image'])): ?>
                                <img src="<?= htmlspecialchars($item['product_image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                            <?php else: ?>
                                <div class="os-item-placeholder">🏺</div>
                            <?php endif; ?>
                        </div>
                        <div class="os-item-info">
                            <div class="os-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                            <div class="os-item-qty">x<?= $item['quantity'] ?></div>
                        </div>
                        <div class="os-item-price">
                            <?= number_format($item['subtotal'], 0, ',', '.') ?>đ
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Địa chỉ nhận hàng -->
            <div class="os-card">
                <h3 class="os-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Địa chỉ nhận hàng
                </h3>
                <div class="os-address-grid">
                    <div class="os-address-row">
                        <span class="os-address-label">Người nhận</span>
                        <span class="os-address-val"><strong><?= htmlspecialchars($order['receiver_name']) ?></strong></span>
                    </div>
                    <div class="os-address-row">
                        <span class="os-address-label">Số điện thoại</span>
                        <span class="os-address-val"><?= htmlspecialchars($order['receiver_phone']) ?></span>
                    </div>
                    <?php if ($order['receiver_email']): ?>
                    <div class="os-address-row">
                        <span class="os-address-label">Email</span>
                        <span class="os-address-val"><?= htmlspecialchars($order['receiver_email']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="os-address-row">
                        <span class="os-address-label">Địa chỉ</span>
                        <span class="os-address-val"><?= htmlspecialchars($order['full_address']) ?></span>
                    </div>
                    <?php if ($order['note']): ?>
                    <div class="os-address-row">
                        <span class="os-address-label">Ghi chú</span>
                        <span class="os-address-val"><?= htmlspecialchars($order['note']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Phương thức thanh toán -->
            <?php if ($order['payment_method'] === 'bank'): ?>
            <div class="os-card os-bank-card">
                <h3 class="os-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Thông tin chuyển khoản
                </h3>
                <div class="os-bank-info">
                    <div class="os-bank-row"><span>Ngân hàng</span><strong>Vietcombank</strong></div>
                    <div class="os-bank-row">
                        <span>Số tài khoản</span>
                        <strong>
                            1234 5678 9012
                            <button class="os-copy-inline" onclick="copyText('123456789012')">Sao chép</button>
                        </strong>
                    </div>
                    <div class="os-bank-row"><span>Chủ tài khoản</span><strong>NGUYEN HA LINH</strong></div>
                    <div class="os-bank-row">
                        <span>Nội dung CK</span>
                        <strong class="os-bank-highlight">SH<?= htmlspecialchars($order['receiver_phone']) ?></strong>
                    </div>
                </div>
                <p class="os-bank-note">⚠️ Vui lòng chuyển khoản trong vòng <strong>24 giờ</strong>. Đơn hàng sẽ tự động hủy nếu chưa nhận được thanh toán.</p>
            </div>
            <?php endif; ?>

        </div>

        <!-- Cột phải: Tóm tắt tiền + Timeline -->
        <div class="os-side-col">

            <!-- Tóm tắt tiền -->
            <div class="os-card">
                <h3 class="os-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Tóm tắt thanh toán
                </h3>
                <div class="os-money-rows">
                    <div class="os-money-row">
                        <span>Tạm tính</span>
                        <span><?= number_format($order['subtotal'], 0, ',', '.') ?>đ</span>
                    </div>
                    <div class="os-money-row">
                        <span>Phí vận chuyển</span>
                        <span><?= $order['shipping_fee'] == 0 ? '<span style="color:#16A34A">Miễn phí</span>' : number_format($order['shipping_fee'], 0, ',', '.') . 'đ' ?></span>
                    </div>
                    <?php if ($order['discount'] > 0): ?>
                    <div class="os-money-row">
                        <span>Giảm giá <?= $order['coupon_code'] ? '(' . htmlspecialchars($order['coupon_code']) . ')' : '' ?></span>
                        <span style="color:#16A34A">− <?= number_format($order['discount'], 0, ',', '.') ?>đ</span>
                    </div>
                    <?php endif; ?>
                    <div class="os-money-divider"></div>
                    <div class="os-money-total">
                        <span>Tổng cộng</span>
                        <span class="os-total-num"><?= number_format($order['total'], 0, ',', '.') ?>đ</span>
                    </div>
                    <div class="os-money-payment">
                        <?= htmlspecialchars($payment_labels[$order['payment_method']] ?? $order['payment_method']) ?>
                    </div>
                </div>
            </div>

            <!-- Trạng thái đơn hàng -->
            <div class="os-card">
                <h3 class="os-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Trạng thái đơn hàng
                </h3>
                <?php
                $current = $order['status'];
                $steps   = [
                    'pending'   => ['icon' => '📋', 'label' => 'Chờ xác nhận'],
                    'confirmed' => ['icon' => '✅', 'label' => 'Đã xác nhận'],
                    'shipping'  => ['icon' => '🚚', 'label' => 'Đang giao hàng'],
                    'delivered' => ['icon' => '🎉', 'label' => 'Đã giao thành công'],
                ];
                $step_keys = array_keys($steps);
                $current_idx = array_search($current, $step_keys);
                ?>
                <div class="os-timeline">
                    <?php foreach ($steps as $key => $step):
                        $idx      = array_search($key, $step_keys);
                        $is_done  = $idx < $current_idx;
                        $is_cur   = $idx === $current_idx;
                        $state    = $is_done ? 'done' : ($is_cur ? 'current' : 'future');
                    ?>
                    <div class="os-tl-item os-tl-<?= $state ?>">
                        <div class="os-tl-dot"><?= $is_done ? '✓' : $step['icon'] ?></div>
                        <div class="os-tl-label"><?= $step['label'] ?></div>
                    </div>
                    <?php if ($key !== 'delivered'): ?>
                    <div class="os-tl-line os-tl-line-<?= $is_done ? 'done' : 'future' ?>"></div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div class="os-status-note">
                    Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                </div>
            </div>

            <!-- Nút hành động -->
            <div class="os-actions">
                <a href="index.php" class="btn-primary os-action-btn">
                    Tiếp tục mua sắm
                </a>
                <a href="shop.php" class="os-action-link">
                    Xem thêm sản phẩm →
                </a>
            </div>
        </div>
    </div>

<?php endif; ?>
</main>

<style>
.order-success-page {
    max-width: 1100px; margin: 0 auto;
    padding: 32px 40px 100px;
}

/* Banner */
.os-banner {
    background: linear-gradient(135deg, #1A1C18 0%, #2C2C2A 100%);
    border-radius: 20px; padding: 40px;
    display: flex; justify-content: space-between;
    align-items: center; gap: 32px;
    margin-bottom: 40px; flex-wrap: wrap;
}
.os-banner-inner { display: flex; align-items: center; gap: 24px; flex: 1; }
.os-check-circle {
    width: 72px; height: 72px; border-radius: 50%;
    background: #16A34A; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 0 8px rgba(22,163,74,0.2);
}
.os-title {
    font-family: var(--font-display); font-size: 36px;
    font-weight: 500; color: var(--cream);
    line-height: 1.2; margin-bottom: 8px;
}
.os-subtitle {
    font-family: var(--font-menu); font-size: 14px;
    color: var(--ink3); line-height: 1.6; max-width: 480px;
}
.os-code-box {
    background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
    border-radius: 12px; padding: 20px 24px;
    display: flex; flex-direction: column; gap: 8px;
    align-items: flex-end; flex-shrink: 0;
}
.os-code-label {
    font-family: var(--font-mono); font-size: 11px;
    color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 1.5px;
}
.os-code-value {
    font-family: var(--font-mono); font-size: 22px;
    font-weight: 700; color: var(--amber); letter-spacing: 2px;
}
.os-copy-btn {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.1); border: none; color: rgba(255,255,255,0.6);
    font-family: var(--font-mono); font-size: 11px;
    padding: 6px 14px; border-radius: 20px; cursor: pointer;
    transition: 0.2s;
}
.os-copy-btn:hover { background: var(--rust); color: #fff; }

/* Not found */
.os-not-found { text-align: center; padding: 100px 20px; }
.os-not-found-icon { font-size: 60px; margin-bottom: 20px; }
.os-not-found h2 { font-family: var(--font-display); font-size: 36px; color: var(--ink); margin-bottom: 12px; }
.os-not-found p { font-family: var(--font-menu); color: var(--ink2); margin-bottom: 28px; }

/* Layout */
.os-layout {
    display: grid; grid-template-columns: 1fr 360px;
    gap: 28px; align-items: start;
}
.os-main-col, .os-side-col { display: flex; flex-direction: column; gap: 20px; }

/* Card chung */
.os-card {
    background: #fff; border: 1px solid var(--cream2);
    border-radius: 16px; padding: 28px 24px;
}
.os-card-title {
    font-family: var(--font-menu); font-size: 15px; font-weight: 700;
    color: var(--ink); margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
}

/* Items */
.os-items { display: flex; flex-direction: column; gap: 0; }
.os-item {
    display: flex; align-items: center; gap: 16px;
    padding: 16px 0; border-bottom: 1px solid var(--cream2);
}
.os-item:last-child { border-bottom: none; }
.os-item-img {
    width: 68px; height: 68px; border-radius: 10px;
    overflow: hidden; flex-shrink: 0;
    border: 1px solid var(--cream2);
}
.os-item-img img { width: 100%; height: 100%; object-fit: cover; }
.os-item-placeholder {
    width: 100%; height: 100%; display: flex;
    align-items: center; justify-content: center;
    font-size: 24px; background: var(--page);
}
.os-item-info { flex: 1; }
.os-item-name {
    font-family: var(--font-menu); font-size: 14px;
    font-weight: 600; color: var(--ink); margin-bottom: 4px;
}
.os-item-qty { font-family: var(--font-mono); font-size: 12px; color: var(--ink3); }
.os-item-price {
    font-family: var(--font-menu); font-size: 15px;
    font-weight: 700; color: var(--ink); white-space: nowrap;
}

/* Address */
.os-address-grid { display: flex; flex-direction: column; gap: 12px; }
.os-address-row {
    display: flex; gap: 16px;
    font-family: var(--font-menu); font-size: 14px;
}
.os-address-label { color: var(--ink3); width: 120px; flex-shrink: 0; }
.os-address-val { color: var(--ink); line-height: 1.5; }

/* Bank */
.os-bank-card { border-color: #FEF3C7; background: #FFFBEB; }
.os-bank-info { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
.os-bank-row {
    display: flex; justify-content: space-between; align-items: center;
    font-family: var(--font-menu); font-size: 14px; color: var(--ink2);
}
.os-bank-highlight { color: var(--rust); font-size: 16px; letter-spacing: 1px; }
.os-copy-inline {
    margin-left: 8px; padding: 3px 10px;
    background: var(--ink); color: #fff; border: none;
    border-radius: 4px; font-size: 11px; cursor: pointer;
    transition: background 0.2s;
}
.os-copy-inline:hover { background: var(--rust); }
.os-bank-note {
    font-family: var(--font-menu); font-size: 13px;
    color: #92400E; background: #FEF3C7;
    border-radius: 8px; padding: 12px 14px; line-height: 1.6;
    border: 1px solid #FDE68A;
}

/* Money summary */
.os-money-rows { display: flex; flex-direction: column; gap: 10px; }
.os-money-row {
    display: flex; justify-content: space-between;
    font-family: var(--font-menu); font-size: 14px; color: var(--ink2);
}
.os-money-divider { height: 1px; background: var(--cream2); margin: 6px 0; }
.os-money-total {
    display: flex; justify-content: space-between; align-items: baseline;
    font-family: var(--font-menu); font-size: 15px; font-weight: 700; color: var(--ink);
}
.os-total-num {
    font-family: var(--font-display); font-size: 32px;
    font-weight: 600; color: var(--rust);
}
.os-money-payment {
    font-family: var(--font-mono); font-size: 12px;
    color: var(--ink3); text-align: right; margin-top: 4px;
}

/* Timeline */
.os-timeline {
    display: flex; align-items: center;
    gap: 0; margin-bottom: 16px; flex-wrap: nowrap;
    overflow-x: auto; padding-bottom: 4px;
}
.os-tl-item {
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    flex-shrink: 0; min-width: 70px;
}
.os-tl-dot {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; border: 2px solid var(--cream2);
    background: #fff; transition: 0.2s;
}
.os-tl-done .os-tl-dot  { background: #16A34A; border-color: #16A34A; font-size: 14px; color: #fff; }
.os-tl-current .os-tl-dot { background: var(--rust); border-color: var(--rust); box-shadow: 0 0 0 4px rgba(216,90,48,0.2); }
.os-tl-future .os-tl-dot { background: var(--page); }
.os-tl-label {
    font-family: var(--font-mono); font-size: 10px; text-align: center;
    color: var(--ink3); line-height: 1.3; max-width: 68px;
}
.os-tl-current .os-tl-label { color: var(--rust); font-weight: 600; }
.os-tl-done .os-tl-label { color: #16A34A; }
.os-tl-line {
    flex: 1; height: 2px; min-width: 20px;
    background: var(--cream2); margin-bottom: 22px;
}
.os-tl-line-done { background: #16A34A; }
.os-status-note {
    font-family: var(--font-mono); font-size: 11px; color: var(--ink3);
    text-align: center;
}

/* Actions */
.os-actions { display: flex; flex-direction: column; gap: 12px; }
.os-action-btn { justify-content: center; width: 100%; padding: 14px; }
.os-action-link {
    font-family: var(--font-menu); font-size: 13px; font-weight: 600;
    color: var(--rust); text-align: center; transition: opacity 0.2s;
}
.os-action-link:hover { opacity: 0.7; }
</style>

<script>
function copyOrderCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        const btn = document.querySelector('.os-copy-btn');
        if (btn) {
            btn.innerHTML = '✓ Đã sao chép!';
            btn.style.background = '#16A34A';
            btn.style.color = '#fff';
            setTimeout(() => {
                btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Sao chép';
                btn.style.background = '';
                btn.style.color = '';
            }, 2000);
        }
    });
}
function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        event.target.textContent = '✓ Đã chép';
        setTimeout(() => event.target.textContent = 'Sao chép', 2000);
    });
}
</script>

<?php include 'includes/footer.php'; ?>