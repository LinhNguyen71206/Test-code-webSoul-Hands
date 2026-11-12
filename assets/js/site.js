/* ============================================================
   SOUL & HANDS — Giao diện dùng chung (nạp 1 lần ở footer.php)
   Gồm: menu người dùng, gợi ý tìm kiếm, mega menu, sắp xếp, bộ lọc.
   (Trước đây nằm inline trong header.php + footer.php, lặp ở mọi trang.)
============================================================ */

/* ---------- 1. Menu người dùng ---------- */
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('user-menu-btn');
        var menu = document.getElementById('user-menu-content');
        if (!btn || !menu) return;
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var isOpen = menu.classList.toggle('open');
            btn.setAttribute('aria-expanded', String(isOpen));
        });
        document.addEventListener('click', function(e) {
            if (!menu.contains(e.target) && !btn.contains(e.target)) {
                menu.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { menu.classList.remove('open'); }
        });
    });
})();

/* ---------- 2. Gợi ý tìm kiếm ---------- */
/* ============================================================
   LIVE SEARCH — Gọi search_suggest.php để lấy gợi ý sản phẩm
============================================================ */
(function() {
    const input   = document.getElementById('nav-search-input');
    const dropdown= document.getElementById('nav-search-suggestions');
    if (!input || !dropdown) return;

    let timer     = null;
    let lastQuery = '';
    let focusIdx  = -1;

    function highlight(text, q) {
        if (!q) return escHtml(text);
        return escHtml(text).replace(
            new RegExp('(' + escRegex(escHtml(q)) + ')', 'gi'),
            '<mark>$1</mark>'
        );
    }
    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function escRegex(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
    function fmtMoney(n) { return Number(n).toLocaleString('vi-VN') + 'đ'; }

    function showLoading() {
        dropdown.innerHTML = '<div class="nsd-loading"><div class="nsd-spinner"></div> Đang tìm...</div>';
        dropdown.classList.add('open');
    }

    function close() {
        dropdown.classList.remove('open');
        focusIdx = -1;
    }

    function renderResults(data, q) {
        const { products = [] } = data;

        if (!products.length) {
            dropdown.innerHTML = '<div class="nsd-empty">Không tìm thấy kết quả nào cho "<strong>' + escHtml(q) + '</strong>"</div>';
            dropdown.classList.add('open');
            return;
        }

        let html = '<div class="nsd-section-label">Sản phẩm</div>';
        products.forEach(p => {
            const img = p.image_url
                ? `<img class="nsd-item-img" src="${escHtml(p.image_url)}" alt="" loading="lazy">`
                : `<div class="nsd-item-img-placeholder">🏺</div>`;
            html += `<a href="product.php?id=${p.id}" class="nsd-item">
                ${img}
                <div class="nsd-item-info">
                    <div class="nsd-item-name">${highlight(p.name, q)}</div>
                    <div class="nsd-item-meta">${escHtml(p.category_name ?? '')}</div>
                </div>
                <span class="nsd-item-price">${fmtMoney(p.price)}</span>
            </a>`;
        });

        html += `<div class="nsd-footer">
            <a href="search.php?q=${encodeURIComponent(q)}" class="nsd-footer-link">
                Xem tất cả kết quả cho "${escHtml(q)}" →
            </a>
        </div>`;

        dropdown.innerHTML = html;
        dropdown.classList.add('open');
        focusIdx = -1;
    }

    function fetchSuggestions(q) {
        if (q === lastQuery) return;
        lastQuery = q;

        if (q.length < 2) { close(); return; }

        showLoading();

        fetch('search_suggest.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => { if (input.value.trim() === q) renderResults(data, q); })
            .catch(() => { close(); });
    }

    // Điều hướng bằng phím mũi tên: -1 = đang ở ô nhập, 0..n-1 = các gợi ý
    function moveFocus(dir) {
        const items = dropdown.querySelectorAll('.nsd-item');
        if (!items.length) return;
        if (focusIdx >= 0) items[focusIdx].classList.remove('focused');
        focusIdx += dir;
        if (focusIdx < -1) focusIdx = items.length - 1;   // lên từ ô nhập → mục cuối
        if (focusIdx >= items.length) focusIdx = -1;      // xuống quá mục cuối → về ô nhập
        if (focusIdx >= 0) {
            items[focusIdx].classList.add('focused');
            items[focusIdx].scrollIntoView({ block: 'nearest' });
        }
    }

    input.addEventListener('input', function() {
        const q = this.value.trim();
        clearTimeout(timer);
        if (!q) { close(); lastQuery = ''; return; }
        timer = setTimeout(() => fetchSuggestions(q), 220);
    });

    input.addEventListener('keydown', function(e) {
        if (!dropdown.classList.contains('open')) return;
        if (e.key === 'ArrowDown')  { e.preventDefault(); moveFocus(1); }
        if (e.key === 'ArrowUp')    { e.preventDefault(); moveFocus(-1); }
        if (e.key === 'Escape')     { close(); this.blur(); }
        if (e.key === 'Enter' && focusIdx >= 0) {
            e.preventDefault();
            dropdown.querySelectorAll('.nsd-item')[focusIdx]?.click();
        }
    });

    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) close();
    });

    // Re-open khi focus lại nếu đã có query
    input.addEventListener('focus', function() {
        const q = this.value.trim();
        if (q.length >= 2 && dropdown.innerHTML) dropdown.classList.add('open');
    });
})();

/* ---------- 3. Mega menu ---------- */
document.addEventListener('DOMContentLoaded', function() {
    const megaMenuBtn = document.getElementById('mega-menu-btn');
    const megaMenuContent = document.getElementById('mega-menu-content');
    const headerNav = document.querySelector('.header-nav');

    function positionMegaMenu() {
        if (headerNav && megaMenuContent) {
            megaMenuContent.style.top = headerNav.getBoundingClientRect().bottom + 'px';
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

    // Nút "Bộ lọc" mặc định ở trạng thái đang mở
    const filterToggleBtn = document.getElementById('filter-toggle-btn');
    if (filterToggleBtn) filterToggleBtn.classList.add('active');
});

/* ---------- 4. Sắp xếp / bộ lọc: dùng "ủy quyền sự kiện" ----------
   Gắn sự kiện ở cấp document (thay vì từng nút) để vẫn chạy
   sau khi danh sách sản phẩm được thay bằng AJAX. */
document.addEventListener('click', function(e) {
    // Nút sắp xếp (trang danh mục + shop)
    var sortMenu = document.getElementById('sort-menu');
    if (e.target.closest('#sort-btn') && sortMenu) {
        e.preventDefault();
        sortMenu.classList.toggle('show');
        return;
    }
    if (sortMenu && !e.target.closest('.sort-dropdown-wrapper')) sortMenu.classList.remove('show');

    // Nút sắp xếp (trang tìm kiếm)
    var srpMenu = document.getElementById('srp-sort-menu');
    if (e.target.closest('#srp-sort-btn') && srpMenu) {
        srpMenu.classList.toggle('open');
        return;
    }
    if (srpMenu && !e.target.closest('.srp-sort-wrap')) srpMenu.classList.remove('open');

    // Ẩn/hiện thanh bộ lọc bên trái
    var filterBtn = e.target.closest('#filter-toggle-btn');
    if (filterBtn) {
        var layout = document.querySelector('.filter-product-layout');
        if (layout) {
            var hidden = layout.classList.toggle('panel-hidden');
            filterBtn.classList.toggle('active', !hidden);
        }
        return;
    }

    // Thu gọn / mở rộng từng nhóm lọc
    var groupTitle = e.target.closest('.filter-group-title');
    if (groupTitle) {
        var body = document.getElementById(groupTitle.getAttribute('data-target'));
        if (body) {
            var isOpen = body.classList.toggle('open');
            groupTitle.classList.toggle('collapsed', !isOpen);
        }
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    var m1 = document.getElementById('sort-menu');      if (m1) m1.classList.remove('show');
    var m2 = document.getElementById('srp-sort-menu');  if (m2) m2.classList.remove('open');
});

/* ---------- 5. Lọc / sắp xếp / phân trang KHÔNG tải lại trang ----------
   Bấm vào link lọc/sắp xếp → tải ngầm trang mới, chỉ thay vùng sản phẩm,
   cập nhật thanh địa chỉ (nút Back vẫn hoạt động). Lỗi → tự chuyển trang như bình thường. */
(function() {
    var ROOTS = ['.products-section', '.srp-page'];                  // link nằm trong vùng này mới được xử lý
    var SYNC  = ['.shop-hero', '.products-section', '.srp-page'];    // các vùng được thay nội dung
    var controller = null;
    var lastKey = location.pathname + location.search;

    function inRoot(el) { return ROOTS.some(function(s) { return !!el.closest(s); }); }
    function hasRoot()  { return ROOTS.some(function(s) { return !!document.querySelector(s); }); }

    // Ghi nhớ trạng thái giao diện người dùng đã chọn để giữ nguyên sau khi thay nội dung
    function captureState() {
        var layout = document.querySelector('.filter-product-layout');
        return {
            panelHidden: !!(layout && layout.classList.contains('panel-hidden')),
            collapsed: Array.prototype.map.call(
                document.querySelectorAll('.filter-group-title.collapsed'),
                function(b) { return b.getAttribute('data-target'); })
        };
    }
    function restoreState(st) {
        var layout = document.querySelector('.filter-product-layout');
        var btn    = document.getElementById('filter-toggle-btn');
        if (layout && st.panelHidden) layout.classList.add('panel-hidden');
        if (btn) btn.classList.toggle('active', !st.panelHidden);
        st.collapsed.forEach(function(id) {
            var body  = document.getElementById(id);
            var title = document.querySelector('.filter-group-title[data-target="' + id + '"]');
            if (body)  body.classList.remove('open');
            if (title) title.classList.add('collapsed');
        });
    }

    function load(url, push, scrollToTop) {
        if (controller) controller.abort();                         // bấm nhanh: bỏ yêu cầu cũ
        controller = ('AbortController' in window) ? new AbortController() : null;

        var root = document.querySelector(ROOTS[0]) || document.querySelector(ROOTS[1]);
        if (root) root.classList.add('is-loading');
        var st = captureState();

        fetch(url, { credentials: 'same-origin', signal: controller ? controller.signal : undefined })
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function(html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                SYNC.forEach(function(sel) {
                    var cur = document.querySelector(sel), nxt = doc.querySelector(sel);
                    if (cur && nxt) cur.innerHTML = nxt.innerHTML;
                });
                if (doc.title) document.title = doc.title;
                restoreState(st);
                if (root) root.classList.remove('is-loading');

                if (push) history.pushState({ ajax: true }, '', url);
                lastKey = location.pathname + location.search;

                if (scrollToTop && root) {
                    window.scrollTo({ top: root.getBoundingClientRect().top + window.pageYOffset - 140, behavior: 'smooth' });
                }
            })
            .catch(function(err) {
                if (err && err.name === 'AbortError') return;       // đã có yêu cầu mới thay thế
                window.location.href = url;                          // lỗi mạng/máy chủ → tải trang bình thường
            });
    }

    document.addEventListener('click', function(e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var a = e.target.closest('a[href]');
        if (!a || !inRoot(a) || a.target === '_blank' || a.hasAttribute('download')) return;

        var u = new URL(a.href, location.href);
        if (u.origin !== location.origin || u.pathname !== location.pathname) return;   // chỉ xử lý link cùng trang (khác bộ lọc)

        e.preventDefault();
        if (u.pathname + u.search === lastKey) return;                                  // đang ở đúng trang này rồi
        load(u.href, true, !!a.closest('.shop-pagination'));
    });

    // Nút Back / Forward
    window.addEventListener('popstate', function() {
        if (!hasRoot()) return;
        var key = location.pathname + location.search;
        if (key === lastKey) return;                                // chỉ đổi #hash
        load(location.href, false, false);
    });
})();