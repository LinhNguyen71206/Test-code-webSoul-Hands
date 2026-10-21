/* ============================================================
   SOUL & HANDS — Cart JS
   Yêu cầu đăng nhập trước khi thêm giỏ hàng / mua ngay
============================================================ */

function getCart() {
    try { return JSON.parse(localStorage.getItem('sh_cart') || '[]'); }
    catch(e) { return []; }
}
function saveCart(cart) {
    localStorage.setItem('sh_cart', JSON.stringify(cart));
    updateCartBadge();
}
function getCartCount() {
    return getCart().reduce(function(s,i){ return s+(i.qty||1); }, 0);
}

/* ---- Kiểm tra đăng nhập ---- */
function isLoggedIn() {
    return window.SH_USER && window.SH_USER.loggedIn === true;
}

/* ---- Modal yêu cầu đăng nhập ---- */
function showLoginModal() {
    var existing = document.getElementById('sh-login-modal');
    if (existing) { existing.style.display = 'flex'; return; }

    var style = document.createElement('style');
    style.textContent = '#sh-login-modal{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center}'
        +'.sh-modal-backdrop{position:absolute;inset:0;background:rgba(44,44,42,.65);backdrop-filter:blur(4px)}'
        +'.sh-modal-box{position:relative;z-index:1;background:#fff;border-radius:20px;padding:48px 40px;max-width:400px;width:90%;text-align:center;box-shadow:0 24px 64px rgba(0,0,0,.18);animation:sh-in .28s cubic-bezier(.34,1.56,.64,1)}'
        +'@keyframes sh-in{from{opacity:0;transform:scale(.88) translateY(16px)}to{opacity:1;transform:scale(1) translateY(0)}}'
        +'.sh-modal-close{position:absolute;top:14px;right:14px;background:none;border:none;cursor:pointer;color:#9CA3AF;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:.2s}'
        +'.sh-modal-close:hover{color:#374151;background:#F3F4F6}'
        +'.sh-modal-icon{font-size:52px;margin-bottom:16px}'
        +'.sh-modal-title{font-family:var(--font-display,serif);font-size:28px;font-weight:500;color:#1A1C18;margin-bottom:10px;letter-spacing:-.5px}'
        +'.sh-modal-desc{font-family:var(--font-menu,sans-serif);font-size:14px;color:#6B7280;line-height:1.7;margin-bottom:28px}'
        +'.sh-modal-btns{display:flex;flex-direction:column;gap:10px;margin-bottom:16px}'
        +'.sh-modal-btn-primary{display:flex;align-items:center;justify-content:center;padding:14px 24px;border-radius:10px;background:#D85A30;color:#fff;font-family:var(--font-menu,sans-serif);font-size:15px;font-weight:600;text-decoration:none;transition:background .2s}'
        +'.sh-modal-btn-primary:hover{background:#B84A20}'
        +'.sh-modal-btn-secondary{display:flex;align-items:center;justify-content:center;padding:13px 24px;border-radius:10px;background:#fff;color:#1A1C18;border:1.5px solid #E5E2D9;font-family:var(--font-menu,sans-serif);font-size:15px;font-weight:600;text-decoration:none;transition:border-color .2s}'
        +'.sh-modal-btn-secondary:hover{border-color:#1A1C18}'
        +'.sh-modal-note{font-family:var(--font-mono,monospace);font-size:11px;color:#9CA3AF}';
    document.head.appendChild(style);

    var modal = document.createElement('div');
    modal.id = 'sh-login-modal';
    modal.innerHTML = '<div class="sh-modal-backdrop" onclick="closeLoginModal()"></div>'
        +'<div class="sh-modal-box">'
        +'<button class="sh-modal-close" onclick="closeLoginModal()">'
        +'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
        +'</button>'
        +'<div class="sh-modal-icon">🔐</div>'
        +'<h3 class="sh-modal-title">Đăng nhập để tiếp tục</h3>'
        +'<p class="sh-modal-desc">Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng và thực hiện mua sắm.</p>'
        +'<div class="sh-modal-btns">'
        +'<a href="login.php" class="sh-modal-btn-primary">Đăng nhập ngay</a>'
        +'<a href="register.php" class="sh-modal-btn-secondary">Tạo tài khoản mới</a>'
        +'</div>'
        +'</div>';
    document.body.appendChild(modal);

    document.addEventListener('keydown', function escHandler(e) {
        if (e.key === 'Escape') { closeLoginModal(); document.removeEventListener('keydown', escHandler); }
    });
}

function closeLoginModal() {
    var m = document.getElementById('sh-login-modal');
    if (m) m.style.display = 'none';
}

/* ---- Thêm vào giỏ ---- */
function addToCart(id, name, price, imageUrl) {
    if (!isLoggedIn()) { showLoginModal(); return false; }
    var cart = getCart();
    var existing = null;
    for (var i = 0; i < cart.length; i++) {
        if (cart[i].id === id) { existing = cart[i]; break; }
    }
    if (existing) {
        existing.qty = (existing.qty || 1) + 1;
    } else {
        cart.push({ id: id, name: name, price: price, imageUrl: imageUrl, qty: 1 });
    }
    saveCart(cart);
    showCartToast(name);
    return true;
}

/* ---- Badge ---- */
function updateCartBadge() {
    var count = getCartCount();
    var badge = document.getElementById('cart-count-badge');
    if (!badge) {
        var cartIcon = document.querySelector('.cart-icon');
        if (!cartIcon) return;
        badge = document.createElement('span');
        badge.id = 'cart-count-badge';
        badge.className = 'cart-count-badge';
        cartIcon.appendChild(badge);
    }
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = 'flex';
        badge.classList.remove('bump');
        requestAnimationFrame(function(){
            requestAnimationFrame(function(){ badge.classList.add('bump'); });
        });
        setTimeout(function(){ badge.classList.remove('bump'); }, 300);
    } else {
        badge.style.display = 'none';
    }
}

/* ---- Toast ---- */
var toastTimer = null;
function showCartToast(productName) {
    var toast = document.getElementById('cart-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'cart-toast';
        toast.className = 'cart-toast';
        document.body.appendChild(toast);
    }
    var shortName = productName.length > 30 ? productName.substring(0,30)+'…' : productName;
    toast.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4ADE80" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>'
        +' Đã thêm "<strong>'+shortName+'</strong>" vào giỏ';
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function(){ toast.classList.remove('show'); }, 3000);
}

/* ================================================================
   DOMContentLoaded
================================================================ */
document.addEventListener('DOMContentLoaded', function() {

    updateCartBadge();

    /* Nút Thêm giỏ hàng */
    document.querySelectorAll('.btn-add-cart').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();

            if (!isLoggedIn()) { showLoginModal(); return; }

            var added = addToCart(btn.dataset.id, btn.dataset.name, parseInt(btn.dataset.price), btn.dataset.image || '');
            if (!added) return;

            var orig = btn.innerHTML;
            btn.classList.add('added');
            btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Đã thêm';
            setTimeout(function(){ btn.classList.remove('added'); btn.innerHTML = orig; }, 2000);
        });
    });

    /* Nút Mua ngay */
    document.querySelectorAll('.btn-buy-now').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();

            if (!isLoggedIn()) { showLoginModal(); return; }

            addToCart(btn.dataset.id, btn.dataset.name, parseInt(btn.dataset.price), btn.dataset.image || '');
            setTimeout(function(){ window.location.href = 'cart.php'; }, 150);
        });
    });

});