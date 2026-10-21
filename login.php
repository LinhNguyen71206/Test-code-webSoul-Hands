<?php
ob_start();          // Buffer toàn bộ output — tránh "headers already sent"
session_start();     // Phải trước mọi output và trước require
require_once 'config/database.php';

// Nếu đã đăng nhập thì redirect về trang chủ
if (isset($_SESSION['user_id'])) {
    ob_end_clean();
    header('Location: index.php');
    exit;
}

$error   = '';
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            session_write_close();
            ob_end_clean();
            // Admin → trang quản lý, khách hàng → trang chủ
            if ($user['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Email hoặc mật khẩu không đúng. Vui lòng thử lại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập — Soul & Hands</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,600&family=DM+Mono:wght@300;400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ============================================================
           AUTH PAGE — Layout 2 cột: Ảnh trái · Form phải
        ============================================================ */
        body { background: var(--ink); }

        .auth-wrap {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }

        /* ---- CỘT TRÁI: Ảnh + thông điệp ---- */
        .auth-visual {
            position: relative;
            overflow: hidden;
        }

        .auth-visual-img {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .auth-visual-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(
                to bottom,
                rgba(44,44,42,0.30) 0%,
                rgba(44,44,42,0.75) 100%
            );
        }

        .auth-visual-content {
            position: absolute; inset: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 52px;
            z-index: 2;
        }

        .auth-logo {
            font-family: var(--font-display);
            font-size: 28px;
            font-weight: 500;
            letter-spacing: 4px;
            color: var(--cream);
            text-decoration: none;
            transition: color 0.2s;
        }
        .auth-logo em { font-style: normal; color: var(--amber); }
        .auth-logo:hover { color: var(--amber); }

        .auth-visual-quote {
            font-family: var(--font-display);
            font-style: italic;
            font-size: 32px;
            font-weight: 400;
            color: var(--cream);
            line-height: 1.4;
            letter-spacing: -0.5px;
        }

        .auth-visual-caption {
            font-family: var(--font-mono);
            font-size: 11px;
            color: rgba(255,255,255,0.5);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 16px;
        }

        /* ---- CỘT PHẢI: Form ---- */
        .auth-form-side {
            background: var(--page);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 80px;
        }

        .auth-form-box {
            width: 100%;
            max-width: 420px;
        }

        .auth-eyebrow {
            font-family: var(--font-mono);
            font-size: 11px;
            letter-spacing: 2.5px;
            color: var(--rust);
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .auth-title {
            font-family: var(--font-display);
            font-size: 52px;
            font-weight: 500;
            color: var(--ink);
            line-height: 1.1;
            letter-spacing: -1px;
            margin-bottom: 8px;
        }

        .auth-sub {
            font-family: var(--font-menu);
            font-size: 14px;
            color: var(--ink2);
            margin-bottom: 40px;
            line-height: 1.6;
        }
        .auth-sub a {
            color: var(--rust);
            font-weight: 600;
            transition: color 0.2s;
        }
        .auth-sub a:hover { color: var(--rust-dark); }

        /* Form fields */
        .auth-form { display: flex; flex-direction: column; gap: 18px; }

        .auth-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .auth-label {
            font-family: var(--font-menu);
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
        }

        .auth-input {
            font-family: var(--font-menu);
            font-size: 15px;
            padding: 14px 18px;
            border: 1.5px solid var(--cream2);
            border-radius: 10px;
            background: #fff;
            color: var(--ink);
            outline: none;
            transition: 0.2s;
            width: 100%;
        }
        .auth-input::placeholder { color: var(--ink3); }
        .auth-input:focus {
            border-color: var(--rust);
            box-shadow: 0 0 0 3px rgba(216,90,48,0.1);
        }

        /* Password wrapper với nút hiện/ẩn */
        .auth-pw-wrap {
            position: relative;
        }
        .auth-pw-wrap .auth-input {
            padding-right: 52px;
        }
        .auth-pw-toggle {
            position: absolute;
            right: 16px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--ink3); cursor: pointer;
            display: flex; align-items: center;
            transition: color 0.2s;
        }
        .auth-pw-toggle:hover { color: var(--ink); }

        /* Hàng quên mật khẩu */
        .auth-row-forgot {
            display: flex;
            justify-content: flex-end;
        }
        .auth-forgot {
            font-family: var(--font-menu);
            font-size: 13px;
            color: var(--ink3);
            transition: color 0.2s;
        }
        .auth-forgot:hover { color: var(--rust); }

        /* Submit button */
        .auth-submit {
            width: 100%;
            justify-content: center;
            padding: 15px 24px;
            font-size: 15px;
            border-radius: 10px;
            margin-top: 4px;
            letter-spacing: 0.3px;
        }

        /* Divider "hoặc" */
        .auth-divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 4px 0;
        }
        .auth-divider-line {
            flex: 1; height: 1px;
            background: var(--cream2);
        }
        .auth-divider-text {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--ink3);
            letter-spacing: 1px;
            white-space: nowrap;
        }

        /* Nút đăng ký phụ */
        .auth-register-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 14px 24px;
            border: 1.5px solid var(--cream2);
            border-radius: 10px;
            background: #fff;
            font-family: var(--font-menu);
            font-size: 14px;
            font-weight: 600;
            color: var(--ink);
            transition: 0.2s;
            cursor: pointer;
            text-decoration: none;
        }
        .auth-register-btn:hover {
            border-color: var(--ink);
        }

        /* Error box */
        .auth-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #B91C1C;
            border-radius: 8px;
            padding: 13px 16px;
            font-family: var(--font-menu);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Footer nhỏ dưới form */
        .auth-footer-note {
            margin-top: 32px;
            text-align: center;
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--ink3);
            line-height: 1.7;
        }
        .auth-footer-note a {
            color: var(--ink2);
            text-decoration: underline;
            text-underline-offset: 3px;
        }
        .auth-footer-note a:hover { color: var(--rust); }
    </style>
</head>
<body>
<div class="auth-wrap">

    <!-- ===== CỘT TRÁI: Ảnh nghệ nhân ===== -->
    <div class="auth-visual">
        <img src="https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?q=80&w=1200&auto=format&fit=crop"
             alt="Nghệ nhân nặn gốm" class="auth-visual-img">
        <div class="auth-visual-overlay"></div>
        <div class="auth-visual-content">
            <a href="index.php" class="auth-logo">Soul <em>&</em> Hands</a>
            <div>
                <p class="auth-visual-quote">"Mỗi lần đăng nhập là một lần trở về với những tác phẩm độc bản."</p>
                <p class="auth-visual-caption">Bát Tràng · Hà Nội · Việt Nam</p>
            </div>
        </div>
    </div>

    <!-- ===== CỘT PHẢI: Form đăng nhập ===== -->
    <div class="auth-form-side">
        <div class="auth-form-box">

            <div class="auth-eyebrow">Chào mừng trở lại</div>
            <h1 class="auth-title">Đăng nhập</h1>
            <p class="auth-sub">
                Chưa có tài khoản?
                <a href="register.php">Đăng ký ngay →</a>
            </p>

            <form method="POST" class="auth-form" novalidate>

                <?php if ($error): ?>
                <div class="auth-error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <div class="auth-field">
                    <label class="auth-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="auth-input"
                           placeholder="email@example.com"
                           value="<?= htmlspecialchars($email) ?>" required autocomplete="email">
                </div>

                <div class="auth-field">
                    <label class="auth-label" for="password">Mật khẩu</label>
                    <div class="auth-pw-wrap">
                        <input type="password" id="password" name="password" class="auth-input"
                               placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="auth-pw-toggle" id="toggle-pw" aria-label="Hiện/ẩn mật khẩu">
                            <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="auth-row-forgot">
                    <a href="#" class="auth-forgot">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn-primary auth-submit">
                    Đăng nhập
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>

                <div class="auth-divider">
                    <div class="auth-divider-line"></div>
                    <span class="auth-divider-text">hoặc</span>
                    <div class="auth-divider-line"></div>
                </div>

                <a href="register.php" class="auth-register-btn">
                    Tạo tài khoản mới
                </a>

            </form>

            <p class="auth-footer-note">
                Bằng cách đăng nhập, bạn đồng ý với
                <a href="#">Điều khoản dịch vụ</a> và
                <a href="#">Chính sách bảo mật</a> của Soul & Hands.
            </p>

        </div>
    </div>

</div>

<script>
// Toggle hiện/ẩn mật khẩu
document.getElementById('toggle-pw').addEventListener('click', function() {
    const pw = document.getElementById('password');
    const icon = document.getElementById('eye-icon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        pw.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
});
</script>
</body>
</html>