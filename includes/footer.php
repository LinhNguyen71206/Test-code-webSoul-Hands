<footer class="footer">
  <div class="footer-top">
    <div class="footer-brand">
      <div class="footer-logo">Soul <em>&</em> Hands</div>
      <p>Mỹ nghệ thủ công Việt Nam — nơi mỗi sản phẩm mang linh hồn của đôi bàn tay nghệ nhân.</p>
      <div class="social-row">
        <a href="#" aria-label="Facebook">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
        </a>
        <a href="#" aria-label="Instagram">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
        </a>
        <a href="#" aria-label="TikTok">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.78 1.52V6.75a4.85 4.85 0 0 1-1.01-.06z"/></svg>
        </a>
      </div>
    </div>
    <div class="footer-col">
      <h4>Cửa hàng</h4>
      <ul>
        <li><a href="shop.php">Tất cả sản phẩm</a></li>
        <li><a href="gom-su.php">Gốm sứ</a></li>
        <li><a href="go-dieu-khac.php">Gỗ điêu khắc</a></li>
        <li><a href="may-tre.php">Mây tre</a></li>
        <li><a href="son-mai.php">Sơn mài</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Workshop</h4>
      <ul>
        <li><a href="workshop.php">Lịch workshop</a></li>
        <li><a href="workshop.php">Nặn gốm</a></li>
        <li><a href="workshop.php">Vẽ sơn mài</a></li>
        <li><a href="workshop.php">Đan mây tre</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Hỗ trợ</h4>
      <ul>
        <li><a href="#">Chính sách đổi trả</a></li>
        <li><a href="#">Hướng dẫn mua hàng</a></li>
        <li><a href="#">Liên hệ</a></li>
        <li><a href="login.php">Tài khoản</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© 2026 Soul & Hands · Hà Nội, Việt Nam</span>
    <span>Làm bằng tay · Trao bằng tâm</span>
  </div>
</footer>

<script src="assets/js/main.js"></script>
<script src="assets/js/cart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ==========================================
    // 1. MEGA MENU LOGIC
    // ==========================================
    const megaMenuBtn = document.getElementById('mega-menu-btn');
    const megaMenuContent = document.getElementById('mega-menu-content');
    const headerNav = document.querySelector('.header-nav');

    function positionMegaMenu() {
        if (headerNav && megaMenuContent) {
            const navRect = headerNav.getBoundingClientRect();
            megaMenuContent.style.top = navRect.bottom + 'px';
        }
    }

    if (megaMenuBtn && megaMenuContent) {
        megaMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = megaMenuContent.classList.toggle('open');
            megaMenuBtn.setAttribute('aria-expanded', String(isOpen));
            if (isOpen) positionMegaMenu();
        });

        document.addEventListener('click', function(e) {
            if (!megaMenuContent.contains(e.target) && e.target !== megaMenuBtn && !megaMenuBtn.contains(e.target)) {
                megaMenuContent.classList.remove('open');
                megaMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                megaMenuContent.classList.remove('open');
                megaMenuBtn.setAttribute('aria-expanded', 'false');
                megaMenuBtn.focus();
            }
        });

        window.addEventListener('scroll', function() {
            if (megaMenuContent.classList.contains('open')) positionMegaMenu();
        }, { passive: true });
    }

    // ==========================================
    // 2. SORT MENU LOGIC
    // ==========================================
    const sortBtn = document.getElementById('sort-btn');
    const sortMenu = document.getElementById('sort-menu');

    if (sortBtn && sortMenu) {
        sortBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sortMenu.classList.toggle('show');
        });

        document.addEventListener('click', function(e) {
            if (!sortBtn.contains(e.target) && !sortMenu.contains(e.target)) {
                sortMenu.classList.remove('show');
            }
        });
    }

    // ==========================================
    // 3. FILTER PANEL TOGGLE (Ẩn/Hiện sidebar)
    // ==========================================
    const filterToggleBtn = document.getElementById('filter-toggle-btn');
    const filterPanel      = document.getElementById('filter-panel');
    const filterLayout     = document.querySelector('.filter-product-layout');

    if (filterToggleBtn && filterPanel && filterLayout) {
        // Khởi tạo: panel mở sẵn
        filterToggleBtn.classList.add('active');

        filterToggleBtn.addEventListener('click', function() {
            const isHidden = filterLayout.classList.toggle('panel-hidden');
            filterToggleBtn.classList.toggle('active', !isHidden);
        });
    }

    // ==========================================
    // 4. COLLAPSE/EXPAND TỪNG NHÓM FILTER
    // ==========================================
    const filterGroupTitles = document.querySelectorAll('.filter-group-title');

    filterGroupTitles.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const targetId = btn.getAttribute('data-target');
            const body = document.getElementById(targetId);
            if (!body) return;

            const isOpen = body.classList.toggle('open');
            btn.classList.toggle('collapsed', !isOpen);
        });
    });

});
</script>
</body>
</html>