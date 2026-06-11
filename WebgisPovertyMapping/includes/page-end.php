<!-- page content ends here -->
        </main><!-- .al-content -->

        <footer class="al-footer">
            <span>© 2026 WebGIS Pemetaan Kemiskinan</span>
            <span>Pemetaan Partisipatif Berbasis Rumah Ibadah</span>
        </footer>

    </div><!-- .al-main -->
</div><!-- .al-wrap -->

<!-- Chart.js v4 CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
function toggleAdminSidebar() {
    const sb = document.getElementById('alSidebar');
    if (!sb) return;
    sb.classList.toggle('collapsed');
    try { localStorage.setItem('admin_sb_collapsed', sb.classList.contains('collapsed') ? '1' : '0'); } catch(e) {}
}
(function() {
    try {
        if (localStorage.getItem('admin_sb_collapsed') === '1') {
            const sb = document.getElementById('alSidebar');
            if (sb) sb.classList.add('collapsed');
        }
    } catch(e) {}
    if (typeof lucide !== 'undefined') lucide.createIcons();
})();

// ── Notification panel ───────────────────────────────────────────────
(function() {
    const btn   = document.getElementById('hd-notif-btn');
    const panel = document.getElementById('notif-panel');
    const badge = document.getElementById('hd-notif-badge');
    const list  = document.getElementById('notif-list');
    const close = document.getElementById('notif-close');
    if (!btn || !panel) return;

    const root = window.APP_ROOT || '';

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function renderItems(items) {
        if (!items.length) {
            return '<div class="notif-empty"><i data-lucide="check-circle"></i><span>Semua sudah beres!</span></div>';
        }
        return items.map(function(item) {
            return '<a href="' + esc(root + item.page) + '" class="notif-item">' +
                '<div class="notif-item-icon type-' + esc(item.type) + '">' +
                    '<i data-lucide="' + esc(item.icon) + '"></i>' +
                '</div>' +
                '<div class="notif-item-text">' + esc(item.label) + '</div>' +
                '<div class="notif-item-count">' + esc(item.count) + '</div>' +
            '</a>';
        }).join('');
    }

    function loadNotifs() {
        list.innerHTML = '<div class="notif-loading">Memuat…</div>';
        fetch(root + 'api/notif/ambil.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status !== 'success') throw new Error();
                var total = data.total || 0;
                badge.textContent = total > 99 ? '99+' : total;
                badge.style.display = total > 0 ? '' : 'none';
                list.innerHTML = renderItems(data.items || []);
                if (typeof lucide !== 'undefined') lucide.createIcons();
            })
            .catch(function() {
                list.innerHTML = '<div class="notif-empty">Gagal memuat notifikasi.</div>';
            });
    }

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var open = panel.style.display !== 'none';
        panel.style.display = open ? 'none' : 'block';
        if (!open) loadNotifs();
    });

    if (close) {
        close.addEventListener('click', function(e) {
            e.stopPropagation();
            panel.style.display = 'none';
        });
    }

    document.addEventListener('click', function(e) {
        if (panel.style.display !== 'none' &&
            !panel.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            panel.style.display = 'none';
        }
    });

    // Load badge count on page load (silent)
    loadNotifs();
})();
</script>
</body>
</html>
