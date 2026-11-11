<?php
/**
 * includes/product-reviews.php
 * Khối "Đánh giá sản phẩm" — include vào product.php (cần có $conn và $product).
 */
require_once __DIR__ . '/shop_helpers.php';

if (!function_exists('rv_stars')) {
    // Hiển thị sao theo điểm (hỗ trợ số lẻ, ví dụ 4.3 sao)
    function rv_stars($rating) {
        $pct = max(0, min(100, ((float)$rating / 5) * 100));
        return '<span class="rv-stars" style="--pct:' . round($pct) . '%" aria-label="' . round((float)$rating, 1) . ' trên 5 sao">★★★★★</span>';
    }
}

$rv_pid       = (int)$product['id'];
$rv_user_id   = (int)($_SESSION['user_id'] ?? 0);
$rv_logged_in = $rv_user_id > 0;

// ── Thống kê điểm ─────────────────────────────────────────────
$rv_sum = ['cnt' => 0, 'avg' => 0, 'dist' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]];
$st = $conn->prepare("SELECT rating, COUNT(*) AS c FROM reviews WHERE product_id = ? AND status = 'visible' GROUP BY rating");
$st->bind_param('i', $rv_pid);
$st->execute();
$total_points = 0;
foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    $r = (int)$row['rating'];
    if ($r >= 1 && $r <= 5) {
        $rv_sum['dist'][$r] = (int)$row['c'];
        $rv_sum['cnt']     += (int)$row['c'];
        $total_points      += $r * (int)$row['c'];
    }
}
$rv_sum['avg'] = $rv_sum['cnt'] > 0 ? $total_points / $rv_sum['cnt'] : 0;

// ── Danh sách đánh giá (đang hiển thị) ────────────────────────
$st = $conn->prepare("
    SELECT r.id, r.rating, r.comment, r.created_at, u.full_name,
           EXISTS(
               SELECT 1 FROM orders o
               JOIN order_items oi ON oi.order_id = o.id
               WHERE o.user_id = r.user_id AND oi.product_id = r.product_id AND o.status = 'delivered'
           ) AS verified
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.product_id = ? AND r.status = 'visible'
    ORDER BY r.created_at DESC
    LIMIT 30
");
$st->bind_param('i', $rv_pid);
$st->execute();
$rv_list = $st->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Đánh giá của chính người đang đăng nhập ───────────────────
$rv_mine = null;
$rv_can  = false;
if ($rv_logged_in) {
    $st = $conn->prepare("SELECT rating, comment, status FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
    $st->bind_param('ii', $rv_user_id, $rv_pid);
    $st->execute();
    $rv_mine = $st->get_result()->fetch_assoc();
    $rv_can  = $rv_mine ? true : user_can_review($conn, $rv_user_id, $rv_pid);
}
?>
<style>
/* ============================================================
   ĐÁNH GIÁ SẢN PHẨM
============================================================ */
.rv-section {
    max-width: 1400px;
    margin: 0 auto;
    padding: 56px 40px 72px;
    border-top: 1px solid var(--cream2);
}
.rv-title {
    font-family: var(--font-display);
    font-size: 40px;
    font-weight: 500;
    color: var(--ink);
    letter-spacing: -1px;
    margin-bottom: 32px;
}
.rv-layout {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 56px;
    align-items: start;
}

/* Sao: lớp nền xám + lớp vàng cắt theo % */
.rv-stars {
    position: relative;
    display: inline-block;
    color: var(--cream2);
    letter-spacing: 2px;
    white-space: nowrap;
    line-height: 1;
}
.rv-stars::before {
    content: '★★★★★';
    position: absolute;
    left: 0; top: 0;
    width: var(--pct, 0%);
    overflow: hidden;
    color: var(--amber);
}

/* Tóm tắt */
.rv-summary {
    background: #fff;
    border: 1px solid var(--cream2);
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 20px;
}
.rv-avg-row { display: flex; align-items: center; gap: 18px; margin-bottom: 20px; }
.rv-avg-num {
    font-family: var(--font-display);
    font-size: 64px;
    font-weight: 600;
    color: var(--ink);
    line-height: 1;
}
.rv-avg-meta .rv-stars { font-size: 20px; }
.rv-avg-count {
    margin-top: 6px;
    font-family: var(--font-menu);
    font-size: 13px;
    color: var(--ink3);
}
.rv-bar-row {
    display: flex; align-items: center; gap: 10px;
    font-family: var(--font-mono);
    font-size: 11px;
    color: var(--ink3);
    margin-bottom: 7px;
}
.rv-bar-row span:first-child { width: 22px; }
.rv-bar-row span:last-child  { width: 26px; text-align: right; }
.rv-bar { flex: 1; height: 6px; border-radius: 3px; background: var(--cream3); overflow: hidden; }
.rv-bar-fill { height: 100%; background: var(--amber); border-radius: 3px; }

/* Form */
.rv-form-card {
    background: #fff;
    border: 1px solid var(--cream2);
    border-radius: 16px;
    padding: 24px 28px;
}
.rv-form-title {
    font-family: var(--font-menu);
    font-size: 15px;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 14px;
}
.rv-picker { display: flex; gap: 4px; margin-bottom: 14px; }
.rv-pick {
    background: none; border: none; cursor: pointer;
    font-size: 32px; line-height: 1; padding: 0 2px;
    color: var(--cream2);
    transition: color 0.15s, transform 0.15s;
}
.rv-pick.on { color: var(--amber); }
.rv-pick:hover { transform: scale(1.15); }
.rv-textarea {
    width: 100%;
    min-height: 100px;
    resize: vertical;
    padding: 12px 14px;
    border: 1.5px solid var(--cream2);
    border-radius: 10px;
    font-family: var(--font-menu);
    font-size: 14px;
    color: var(--ink);
    outline: none;
    transition: 0.2s;
}
.rv-textarea:focus { border-color: var(--rust); box-shadow: 0 0 0 3px rgba(216,90,48,0.1); }
.rv-form-foot {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 12px; gap: 12px;
}
.rv-hint { font-family: var(--font-mono); font-size: 11px; color: var(--ink3); }
.rv-submit {
    padding: 11px 24px;
    border-radius: 10px;
    border: none;
    background: var(--rust);
    color: #fff;
    font-family: var(--font-menu);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.rv-submit:hover { background: #B84A20; }
.rv-submit:disabled { opacity: 0.6; cursor: not-allowed; }
.rv-msg {
    margin-top: 12px;
    font-family: var(--font-menu);
    font-size: 13px;
    min-height: 18px;
}
.rv-msg.ok  { color: #16A34A; }
.rv-msg.err { color: #DC2626; }
.rv-note {
    background: var(--page);
    border-radius: 10px;
    padding: 16px 18px;
    font-family: var(--font-menu);
    font-size: 14px;
    color: var(--ink2);
    line-height: 1.6;
}
.rv-note a { color: var(--rust); font-weight: 600; }
.rv-hidden-note {
    background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E;
    border-radius: 8px; padding: 10px 14px; margin-bottom: 14px;
    font-family: var(--font-menu); font-size: 13px;
}

/* Danh sách */
.rv-item {
    padding: 22px 0;
    border-bottom: 1px solid var(--cream2);
}
.rv-item:first-child { padding-top: 0; }
.rv-item:last-child { border-bottom: none; }
.rv-item-head { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; flex-wrap: wrap; }
.rv-avatar {
    width: 38px; height: 38px; border-radius: 50%;
    background: var(--rust); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-family: var(--font-menu); font-size: 15px; font-weight: 700;
    flex-shrink: 0;
}
.rv-name { font-family: var(--font-menu); font-size: 14px; font-weight: 600; color: var(--ink); }
.rv-verified {
    display: inline-flex; align-items: center; gap: 4px;
    font-family: var(--font-mono); font-size: 10px;
    color: #16A34A; background: #F0FDF4; border: 1px solid #BBF7D0;
    border-radius: 20px; padding: 2px 9px;
}
.rv-date { margin-left: auto; font-family: var(--font-mono); font-size: 11px; color: var(--ink3); }
.rv-item .rv-stars { font-size: 15px; margin-bottom: 8px; }
.rv-comment {
    font-family: var(--font-menu);
    font-size: 14px;
    color: var(--ink2);
    line-height: 1.75;
    white-space: pre-line;
    word-break: break-word;
}
.rv-empty {
    text-align: center; padding: 56px 20px;
    background: #fff; border: 1.5px dashed var(--cream2); border-radius: 16px;
    font-family: var(--font-menu); font-size: 15px; color: var(--ink3);
}
</style>

<section class="rv-section" id="reviews">
    <h2 class="rv-title">Đánh giá sản phẩm</h2>

    <div class="rv-layout">

        <!-- ===== CỘT TRÁI: Tóm tắt + form ===== -->
        <div>
            <div class="rv-summary">
                <div class="rv-avg-row">
                    <div class="rv-avg-num"><?= $rv_sum['cnt'] > 0 ? number_format($rv_sum['avg'], 1) : '—' ?></div>
                    <div class="rv-avg-meta">
                        <?= rv_stars($rv_sum['avg']) ?>
                        <div class="rv-avg-count"><?= $rv_sum['cnt'] ?> đánh giá</div>
                    </div>
                </div>
                <?php for ($s = 5; $s >= 1; $s--):
                    $w = $rv_sum['cnt'] > 0 ? round($rv_sum['dist'][$s] / $rv_sum['cnt'] * 100) : 0;
                ?>
                <div class="rv-bar-row">
                    <span><?= $s ?>★</span>
                    <div class="rv-bar"><div class="rv-bar-fill" style="width:<?= $w ?>%"></div></div>
                    <span><?= $rv_sum['dist'][$s] ?></span>
                </div>
                <?php endfor; ?>
            </div>

            <div class="rv-form-card">
                <?php if (!$rv_logged_in): ?>
                    <div class="rv-note">
                        Vui lòng <a href="login.php">đăng nhập</a> để viết đánh giá cho sản phẩm này.
                    </div>

                <?php elseif (!$rv_can): ?>
                    <div class="rv-note">
                        Chỉ khách hàng đã <strong>nhận được sản phẩm này</strong> mới có thể đánh giá. Sau khi đơn hàng được giao thành công, bạn quay lại đây để chia sẻ cảm nhận nhé.
                    </div>

                <?php else: ?>
                    <div class="rv-form-title"><?= $rv_mine ? 'Đánh giá của bạn' : 'Viết đánh giá của bạn' ?></div>

                    <?php if ($rv_mine && $rv_mine['status'] === 'hidden'): ?>
                    <div class="rv-hidden-note">Đánh giá này hiện đang bị ẩn bởi quản trị viên nên khách khác chưa nhìn thấy.</div>
                    <?php endif; ?>

                    <div class="rv-picker" id="rv-picker" role="radiogroup" aria-label="Chọn số sao">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="rv-pick" data-value="<?= $i ?>" aria-label="<?= $i ?> sao">★</button>
                        <?php endfor; ?>
                    </div>
                    <textarea class="rv-textarea" id="rv-comment" maxlength="1000"
                              placeholder="Chia sẻ cảm nhận của bạn về chất lượng, đóng gói, độ tinh xảo..."><?= htmlspecialchars($rv_mine['comment'] ?? '') ?></textarea>
                    <div class="rv-form-foot">
                        <span class="rv-hint" id="rv-count">0/1000</span>
                        <button type="button" class="rv-submit" id="rv-submit"><?= $rv_mine ? 'Cập nhật đánh giá' : 'Gửi đánh giá' ?></button>
                    </div>
                    <div class="rv-msg" id="rv-msg"></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== CỘT PHẢI: Danh sách ===== -->
        <div>
            <?php if (empty($rv_list)): ?>
                <div class="rv-empty">Sản phẩm chưa có đánh giá nào. Hãy là người đầu tiên chia sẻ cảm nhận!</div>
            <?php else: foreach ($rv_list as $rv): ?>
                <div class="rv-item">
                    <div class="rv-item-head">
                        <div class="rv-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($rv['full_name'], 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
                        <div class="rv-name"><?= htmlspecialchars($rv['full_name']) ?></div>
                        <?php if ((int)$rv['verified'] === 1): ?>
                        <span class="rv-verified">✓ Đã mua hàng</span>
                        <?php endif; ?>
                        <div class="rv-date"><?= date('d/m/Y', strtotime($rv['created_at'])) ?></div>
                    </div>
                    <?= rv_stars((int)$rv['rating']) ?>
                    <?php if (trim((string)$rv['comment']) !== ''): ?>
                    <div class="rv-comment"><?= htmlspecialchars($rv['comment']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>

    </div>
</section>

<?php if ($rv_logged_in && $rv_can): ?>
<script>
(function() {
    var productId = <?= (int)$rv_pid ?>;
    var rating    = <?= (int)($rv_mine['rating'] ?? 0) ?>;
    var picks     = document.querySelectorAll('#rv-picker .rv-pick');
    var textarea  = document.getElementById('rv-comment');
    var counter   = document.getElementById('rv-count');
    var submit    = document.getElementById('rv-submit');
    var msg       = document.getElementById('rv-msg');

    function paint(n) {
        picks.forEach(function(b) {
            b.classList.toggle('on', parseInt(b.dataset.value, 10) <= n);
        });
    }
    function updateCount() { counter.textContent = textarea.value.length + '/1000'; }

    picks.forEach(function(b) {
        b.addEventListener('click', function() { rating = parseInt(b.dataset.value, 10); paint(rating); });
        b.addEventListener('mouseenter', function() { paint(parseInt(b.dataset.value, 10)); });
    });
    document.getElementById('rv-picker').addEventListener('mouseleave', function() { paint(rating); });
    textarea.addEventListener('input', updateCount);
    paint(rating);
    updateCount();

    function show(text, ok) {
        msg.textContent = text;           // textContent: an toàn, không chèn HTML
        msg.className = 'rv-msg ' + (ok ? 'ok' : 'err');
    }

    submit.addEventListener('click', function() {
        if (rating < 1) { show('Vui lòng chọn số sao.', false); return; }
        submit.disabled = true;
        show('Đang gửi...', true);

        fetch('api/submit_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, rating: rating, comment: textarea.value })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                show(data.message, true);
                setTimeout(function() { window.location.hash = 'reviews'; window.location.reload(); }, 900);
            } else {
                show(data.message || 'Có lỗi xảy ra.', false);
                submit.disabled = false;
            }
        })
        .catch(function() {
            show('Lỗi kết nối, vui lòng thử lại.', false);
            submit.disabled = false;
        });
    });
})();
</script>
<?php endif; ?>