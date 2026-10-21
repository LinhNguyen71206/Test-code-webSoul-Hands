<?php
ob_start();          // Buffer toàn bộ output — tránh "headers already sent"
session_start();     // Phải trước mọi output và trước require
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    ob_end_clean();
    header('Location: index.php');
    exit;
}

$error   = '';
$success = '';
$form    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['full_name']        = trim($_POST['full_name']        ?? '');
    $form['email']            = trim($_POST['email']            ?? '');
    $form['phone']            = trim($_POST['phone']            ?? '');
    $form['password']         = $_POST['password']              ?? '';
    $form['password_confirm'] = $_POST['password_confirm']      ?? '';

    // Validate
    if (empty($form['full_name']) || empty($form['email']) || empty($form['password'])) {
        $error = 'Vui lòng điền đầy đủ các trường bắt buộc (*).';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ email không hợp lệ.';
    } elseif (!empty($form['phone']) && !preg_match('/^[0-9]{9,11}$/', $form['phone'])) {
        $error = 'Số điện thoại không hợp lệ (9–11 chữ số).';
    } elseif (strlen($form['password']) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($form['password'] !== $form['password_confirm']) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        // Kiểm tra email đã tồn tại
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param('s', $form['email']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email này đã được đăng ký. Vui lòng dùng email khác hoặc đăng nhập.';
        } else {
            // Lưu user mới
            $hashed = password_hash($form['password'], PASSWORD_DEFAULT);
            $stmt   = $conn->prepare(
                "INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, 'customer')"
            );
            $stmt->bind_param('ssss', $form['full_name'], $form['email'], $form['phone'], $hashed);
            if ($stmt->execute()) {
                // Tự động đăng nhập sau khi đăng ký
                $_SESSION['user_id']   = $conn->insert_id;
                $_SESSION['user_name'] = $form['full_name'];
                $_SESSION['user_role'] = 'customer';
                session_write_close();
                ob_end_clean();
                header('Location: index.php');
                exit;
            } else {
                $error = 'Có lỗi xảy ra, vui lòng thử lại.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký — Soul & Hands</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,600&family=DM+Mono:wght@300;400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: var(--ink); }

        .auth-wrap {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }

        /* ---- CỘT TRÁI: Ảnh ---- */
        .auth-visual {
            position: relative;
            overflow: hidden;
        }
        .auth-visual-img {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            object-fit: cover; object-position: center top;
        }
        .auth-visual-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(
                to bottom,
                rgba(44,44,42,0.25) 0%,
                rgba(44,44,42,0.80) 100%
            );
        }
        .auth-visual-content {
            position: absolute; inset: 0;
            display: flex; flex-direction: column;
            justify-content: space-between;
            padding: 48px 52px; z-index: 2;
        }
        .auth-logo {
            font-family: var(--font-display);
            font-size: 28px; font-weight: 500;
            letter-spacing: 4px; color: var(--cream);
            text-decoration: none; transition: color 0.2s;
        }
        .auth-logo em { font-style: normal; color: var(--amber); }
        .auth-logo:hover { color: var(--amber); }

        /* Checklist lợi ích */
        .auth-benefits {
            display: flex; flex-direction: column; gap: 16px;
        }
        .auth-benefit-title {
            font-family: var(--font-display);
            font-style: italic; font-size: 28px;
            color: var(--cream); margin-bottom: 8px;
            line-height: 1.3;
        }
        .auth-benefit-item {
            display: flex; align-items: flex-start; gap: 12px;
        }
        .benefit-check {
            width: 20px; height: 20px;
            background: var(--rust);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; margin-top: 1px;
        }
        .auth-benefit-text {
            font-family: var(--font-menu);
            font-size: 14px; color: rgba(255,255,255,0.8);
            line-height: 1.5;
        }

        /* ---- CỘT PHẢI: Form ---- */
        .auth-form-side {
            background: var(--page);
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            padding: 48px 80px;
            overflow-y: auto;
        }
        .auth-form-box {
            width: 100%; max-width: 440px;
        }

        .auth-eyebrow {
            font-family: var(--font-mono);
            font-size: 11px; letter-spacing: 2.5px;
            color: var(--rust); text-transform: uppercase;
            margin-bottom: 12px;
        }
        .auth-title {
            font-family: var(--font-display);
            font-size: 52px; font-weight: 500;
            color: var(--ink); line-height: 1.1;
            letter-spacing: -1px; margin-bottom: 8px;
        }
        .auth-sub {
            font-family: var(--font-menu);
            font-size: 14px; color: var(--ink2);
            margin-bottom: 36px; line-height: 1.6;
        }
        .auth-sub a { color: var(--rust); font-weight: 600; transition: color 0.2s; }
        .auth-sub a:hover { color: var(--rust-dark); }

        /* Form */
        .auth-form { display: flex; flex-direction: column; gap: 16px; }
        .auth-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        .auth-field { display: flex; flex-direction: column; gap: 7px; }
        .auth-label {
            font-family: var(--font-menu);
            font-size: 13px; font-weight: 600; color: var(--ink);
        }
        .required { color: var(--rust); }

        .auth-input {
            font-family: var(--font-menu);
            font-size: 15px; padding: 13px 18px;
            border: 1.5px solid var(--cream2);
            border-radius: 10px;
            background: #fff; color: var(--ink);
            outline: none; transition: 0.2s; width: 100%;
        }
        .auth-input::placeholder { color: var(--ink3); }
        .auth-input:focus {
            border-color: var(--rust);
            box-shadow: 0 0 0 3px rgba(216,90,48,0.1);
        }
        .auth-input.error { border-color: #F87171; }

        /* Password strength bar */
        .pw-strength-bar {
            height: 3px; border-radius: 3px;
            background: var(--cream2);
            overflow: hidden; margin-top: 4px;
        }
        .pw-strength-fill {
            height: 100%; width: 0%;
            border-radius: 3px;
            transition: width 0.3s, background 0.3s;
        }
        .pw-hint {
            font-family: var(--font-mono);
            font-size: 11px; color: var(--ink3);
            margin-top: 4px;
        }

        /* Password wrapper */
        .auth-pw-wrap { position: relative; }
        .auth-pw-wrap .auth-input { padding-right: 52px; }
        .auth-pw-toggle {
            position: absolute; right: 16px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--ink3); cursor: pointer;
            display: flex; align-items: center; transition: color 0.2s;
        }
        .auth-pw-toggle:hover { color: var(--ink); }

        /* Submit */
        .auth-submit {
            width: 100%; justify-content: center;
            padding: 15px 24px; font-size: 15px;
            border-radius: 10px; margin-top: 4px;
        }

        /* Divider */
        .auth-divider {
            display: flex; align-items: center; gap: 16px; margin: 2px 0;
        }
        .auth-divider-line { flex: 1; height: 1px; background: var(--cream2); }
        .auth-divider-text {
            font-family: var(--font-mono); font-size: 11px;
            color: var(--ink3); letter-spacing: 1px; white-space: nowrap;
        }

        .auth-login-btn {
            display: flex; align-items: center; justify-content: center;
            width: 100%; padding: 14px 24px;
            border: 1.5px solid var(--cream2); border-radius: 10px;
            background: #fff; font-family: var(--font-menu);
            font-size: 14px; font-weight: 600; color: var(--ink);
            transition: 0.2s; text-decoration: none;
        }
        .auth-login-btn:hover { border-color: var(--ink); }

        /* Error */
        .auth-error {
            background: #FEF2F2; border: 1px solid #FECACA;
            color: #B91C1C; border-radius: 8px;
            padding: 13px 16px;
            font-family: var(--font-menu); font-size: 14px;
            display: flex; align-items: center; gap: 10px;
        }

        .auth-footer-note {
            margin-top: 28px; text-align: center;
            font-family: var(--font-mono); font-size: 11px;
            color: var(--ink3); line-height: 1.7;
        }
        .auth-footer-note a {
            color: var(--ink2); text-decoration: underline;
            text-underline-offset: 3px;
        }
        .auth-footer-note a:hover { color: var(--rust); }
    </style>
</head>
<body>
<div class="auth-wrap">

    <!-- ===== CỘT TRÁI: Ảnh + lợi ích ===== -->
    <div class="auth-visual">
        <img src="https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?q=80&w=1200&auto=format&fit=crop"
             alt="Nghệ nhân sơn mài" class="auth-visual-img">
        <div class="auth-visual-overlay"></div>
        <div class="auth-visual-content">
            <a href="index.php" class="auth-logo">Soul <em>&</em> Hands</a>
            <div class="auth-benefits">
                <p class="auth-benefit-title">Tham gia cộng đồng<br>yêu thủ công Việt</p>
                <div class="auth-benefit-item">
                    <div class="benefit-check">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <span class="auth-benefit-text">Lưu sản phẩm yêu thích, theo dõi đơn hàng dễ dàng</span>
                </div>
                <div class="auth-benefit-item">
                    <div class="benefit-check">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <span class="auth-benefit-text">Đặt lịch workshop nhanh hơn, không cần nhập lại thông tin</span>
                </div>
                <div class="auth-benefit-item">
                    <div class="benefit-check">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <span class="auth-benefit-text">Nhận thông báo sớm về bộ sưu tập mới và ưu đãi độc quyền</span>
                </div>
                <div class="auth-benefit-item">
                    <div class="benefit-check">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <span class="auth-benefit-text">Hoàn toàn miễn phí — không quảng cáo, không chia sẻ dữ liệu</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== CỘT PHẢI: Form đăng ký ===== -->
    <div class="auth-form-side">
        <div class="auth-form-box">

            <div class="auth-eyebrow">Bắt đầu hành trình</div>
            <h1 class="auth-title">Tạo tài khoản</h1>
            <p class="auth-sub">
                Đã có tài khoản?
                <a href="index.php">Trang chủ →</a>
            </p>

            <form method="POST" class="auth-form" novalidate id="register-form">

                <?php if ($error): ?>
                <div class="auth-error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <!-- Họ tên & SĐT -->
                <div class="auth-form-row">
                    <div class="auth-field">
                        <label class="auth-label" for="full_name">Họ và tên <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="auth-input"
                               placeholder="Nguyễn Văn A"
                               value="<?= htmlspecialchars($form['full_name'] ?? '') ?>"
                               required autocomplete="name">
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="phone">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" class="auth-input"
                               placeholder="0901 234 567"
                               value="<?= htmlspecialchars($form['phone'] ?? '') ?>"
                               autocomplete="tel">
                    </div>
                </div>

                <!-- Email -->
                <div class="auth-field">
                    <label class="auth-label" for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="auth-input"
                           placeholder="email@example.com"
                           value="<?= htmlspecialchars($form['email'] ?? '') ?>"
                           required autocomplete="email">
                </div>

                <!-- Mật khẩu -->
                <div class="auth-field">
                    <label class="auth-label" for="password">Mật khẩu <span class="required">*</span></label>
                    <div class="auth-pw-wrap">
                        <input type="password" id="password" name="password" class="auth-input"
                               placeholder="Ít nhất 6 ký tự"
                               required autocomplete="new-password"
                               oninput="checkStrength(this.value)">
                        <button type="button" class="auth-pw-toggle" onclick="togglePw('password', 'eye1')" aria-label="Hiện/ẩn mật khẩu">
                            <svg id="eye1" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div class="pw-strength-bar"><div class="pw-strength-fill" id="pw-fill"></div></div>
                    <span class="pw-hint" id="pw-hint"></span>
                </div>

                <!-- Xác nhận mật khẩu -->
                <div class="auth-field">
                    <label class="auth-label" for="password_confirm">Xác nhận mật khẩu <span class="required">*</span></label>
                    <div class="auth-pw-wrap">
                        <input type="password" id="password_confirm" name="password_confirm" class="auth-input"
                               placeholder="Nhập lại mật khẩu"
                               required autocomplete="new-password">
                        <button type="button" class="auth-pw-toggle" onclick="togglePw('password_confirm', 'eye2')" aria-label="Hiện/ẩn mật khẩu">
                            <svg id="eye2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary auth-submit">
                    Tạo tài khoản
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>

                <div class="auth-divider">
                    <div class="auth-divider-line"></div>
                    <span class="auth-divider-text">đã có tài khoản</span>
                    <div class="auth-divider-line"></div>
                </div>

                <a href="login.php" class="auth-login-btn">
                    Đăng nhập
                </a>

            </form>

            <p class="auth-footer-note">
                Bằng cách đăng ký, bạn đồng ý với
                <a href="#">Điều khoản dịch vụ</a> và
                <a href="#">Chính sách bảo mật</a> của Soul & Hands.<br>
            </p>

        </div>
    </div>

</div>

<script>
// Hiện/ẩn mật khẩu
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}

// Thanh kiểm tra độ mạnh mật khẩu
function checkStrength(pw) {
    const fill = document.getElementById('pw-fill');
    const hint = document.getElementById('pw-hint');
    let score = 0;
    if (pw.length >= 6)  score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;

    const levels = [
        { pct: '0%',   color: 'var(--cream2)', text: 'Nhập mật khẩu' },
        { pct: '25%',  color: '#F87171',        text: 'Yếu — hãy thêm số hoặc ký tự đặc biệt' },
        { pct: '50%',  color: '#FBBF24',        text: 'Trung bình — thêm chữ hoa sẽ tốt hơn' },
        { pct: '75%',  color: '#34D399',        text: 'Khá mạnh — gần đạt yêu cầu tốt' },
        { pct: '90%',  color: '#10B981',        text: 'Mạnh — mật khẩu rất an toàn!' },
        { pct: '100%', color: '#059669',        text: 'Rất mạnh ' },
    ];
    const lv = levels[Math.min(score, 5)];
    fill.style.width      = pw.length === 0 ? '0%' : lv.pct;
    fill.style.background = lv.color;
    hint.textContent      = pw.length === 0 ? levels[0].text : lv.text;
    hint.style.color      = pw.length === 0 ? 'var(--ink3)' : lv.color;
}

// Validate confirm password real-time
document.getElementById('password_confirm').addEventListener('input', function() {
    const pw      = document.getElementById('password').value;
    const confirm = this.value;
    if (confirm && pw !== confirm) {
        this.style.borderColor = '#F87171';
    } else {
        this.style.borderColor = '';
    }
});
</script>
</body>
</html>