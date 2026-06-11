// modules/stats.js — Dashboard statistik role-aware (v1.0)
(function () {
    let charts = { kategori: null };

    window._statsUpdate = function () {
        const modal = document.getElementById('modalStats');
        if (!modal) return;
        if (modal.classList.contains('show')) {
            loadAndRender();
        }
    };

    window._showDashboard = function () {
        const modal = document.getElementById('modalStats');
        if (!modal) return;
        modal.classList.add('show');
        loadAndRender();
    };

    window._hideDashboard = function () {
        const modal = document.getElementById('modalStats');
        if (!modal) return;
        modal.classList.remove('show');
    };

    function loadAndRender() {
        fetch('api/stats/ambil.php?_=' + Date.now(), { cache: 'no-store' })
            .then(r => r.json())
            .then(j => {
                if (j.status !== 'success') return;
                renderCards(j.data);
                renderKategoriChart(j.data.kategori || []);
                renderRekapTable(j.data.rekap_ibadah ?? null);
                renderRekapKebutuhan(j.data.rekap_kebutuhan ?? null);
                updateDonaturBadge(j.data.donatur_unread ?? 0);
            })
            .catch(err => console.error('[Stats]', err));
    }

    function set(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    function updateDonaturBadge(count) {
        const badge = document.getElementById('donaturBadge');
        if (!badge) return;
        badge.textContent = count > 0 ? count : '';
        badge.style.display = count > 0 ? '' : 'none';
    }

    function renderCards(d) {
        set('statTotalIbadah',    d.total_ibadah);
        set('statTotalKK',        d.total_kk);
        set('statTotalJiwa',      d.total_jiwa);
        set('statJiwaTerjangkau', d.jiwa_terjangkau);
        set('statJiwaBlankSpot',  d.jiwa_blank_spot);
        set('statPctCakupan',     d.pct_cakupan + '%');
        set('statKebutuhanOpen',  d.kk_kebutuhan_open ?? 0);

        const emptyMsg = document.getElementById('statsEmptyMsg');
        if (emptyMsg) {
            emptyMsg.style.display = (d.total_kk === 0 && d.total_ibadah === 0) ? 'block' : 'none';
        }
    }

    function renderKategoriChart(rows) {
        const el = document.getElementById('chartKategori');
        if (!el) return;

        const labels = rows.map(r => r.kategori);
        const data   = rows.map(r => parseInt(r.jml_jiwa));
        const colors = {
            'Sangat Miskin': '#f85149',
            'Miskin'        : '#e3b341',
            'Hampir Miskin' : '#388bfd',
        };
        const bgColors = labels.map(l => (colors[l] || '#8b949e') + '99');
        const bdColors = labels.map(l =>  colors[l] || '#8b949e');

        if (charts.kategori) charts.kategori.destroy();
        charts.kategori = new Chart(el.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{ data, backgroundColor: bgColors, borderColor: bdColors, borderWidth: 2, hoverOffset: 6 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#8b949e', font: { size: 11 }, padding: 14 }
                    }
                }
            }
        });
    }

    function renderRekapTable(rows) {
        const wrapper = document.getElementById('rekapTableWrapper');
        if (!wrapper) return;

        if (rows === null) { wrapper.style.display = 'none'; return; }
        wrapper.style.display = '';

        if (rows.length === 0) {
            wrapper.innerHTML = '<p style="color:var(--c-muted);font-size:13px;text-align:center;padding:16px 0;">Belum ada data rumah ibadah.</p>';
            return;
        }

        function esc(s) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(s ?? ''));
            return d.innerHTML;
        }

        const tbody = rows.map(r => {
            const pct = r.jml_kk > 0 ? Math.round(r.jml_ditangani / r.jml_kk * 100) : 0;
            return `<tr>
                <td style="padding:6px 10px">${esc(r.nama)}</td>
                <td style="padding:6px 10px;color:var(--c-muted)">${esc(r.jenis)}</td>
                <td style="padding:6px 10px;text-align:right;font-family:var(--font-mono)">${r.jml_kk}</td>
                <td style="padding:6px 10px;text-align:right;font-family:var(--font-mono)">${r.jml_jiwa}</td>
                <td style="padding:6px 10px;text-align:right">
                    <span style="color:${pct>=60?'#3fb950':'#e3b341'};font-weight:700;font-family:var(--font-mono)">${pct}%</span>
                </td>
            </tr>`;
        }).join('');

        wrapper.innerHTML = `
            <div style="font-size:11px;font-weight:700;color:var(--c-muted);text-transform:uppercase;
                        letter-spacing:.6px;margin-bottom:10px;">Rekapitulasi per Rumah Ibadah</div>
            <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--c-border)">
                        <th style="text-align:left;padding:6px 10px;color:var(--c-muted);font-weight:600">Nama</th>
                        <th style="text-align:left;padding:6px 10px;color:var(--c-muted);font-weight:600">Jenis</th>
                        <th style="text-align:right;padding:6px 10px;color:var(--c-muted);font-weight:600">KK</th>
                        <th style="text-align:right;padding:6px 10px;color:var(--c-muted);font-weight:600">Jiwa</th>
                        <th style="text-align:right;padding:6px 10px;color:var(--c-muted);font-weight:600">% Ditangani</th>
                    </tr>
                </thead>
                <tbody>${tbody}</tbody>
            </table>
            </div>`;
    }
    function renderRekapKebutuhan(rows) {
        const wrapper = document.getElementById('rekapKebutuhanWrapper');
        if (!wrapper) return;

        if (rows === null) { wrapper.style.display = 'none'; return; }
        wrapper.style.display = '';

        if (rows.length === 0) {
            wrapper.innerHTML = '<p style="color:var(--c-muted);font-size:13px;text-align:center;padding:16px 0;">Belum ada kebutuhan tercatat.</p>';
            return;
        }

        function esc(s) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(s ?? ''));
            return d.innerHTML;
        }

        const ICON = {
            'Sembako': 'shopping-cart',
            'Biaya Sekolah': 'graduation-cap',
            'Biaya Kesehatan': 'heart-pulse',
            'Modal Usaha': 'briefcase',
            'Renovasi Rumah': 'home',
            'Perlengkapan Rumah': 'armchair',
            'Pakaian': 'shirt',
            'Lainnya': 'clipboard-list'
        };

        const tbody = rows.map(r => {
            const iconName = ICON[r.kategori] || 'clipboard-list';
            const iconHtml = `<i data-lucide="${iconName}" class="lucide-inline" style="width:14px;height:14px;vertical-align:middle;margin-right:6px;"></i>`;
            return `<tr style="border-bottom:1px solid rgba(255,255,255,.05)">
                <td style="padding:6px 10px;display:flex;align-items:center;gap:2px;">${iconHtml} ${esc(r.kategori)}</td>
                <td style="padding:6px 10px;text-align:right;font-family:var(--font-mono);color:#ef4444">${r.belum}</td>
                <td style="padding:6px 10px;text-align:right;font-family:var(--font-mono);color:#e3b341">${r.proses}</td>
                <td style="padding:6px 10px;text-align:right;font-family:var(--font-mono);color:#3fb950">${r.terpenuhi}</td>
                <td style="padding:6px 10px;text-align:right;font-family:var(--font-mono);color:var(--c-muted)">${r.total}</td>
            </tr>`;
        }).join('');

        wrapper.innerHTML = `
            <div style="font-size:11px;font-weight:700;color:var(--c-muted);text-transform:uppercase;
                        letter-spacing:.6px;margin-bottom:10px;">Rekapitulasi Kebutuhan per Kategori</div>
            <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--c-border)">
                        <th style="text-align:left;padding:6px 10px;color:var(--c-muted);font-weight:600">Kategori</th>
                        <th style="text-align:right;padding:6px 10px;color:#ef4444;font-weight:600">Belum</th>
                        <th style="text-align:right;padding:6px 10px;color:#e3b341;font-weight:600">Proses</th>
                        <th style="text-align:right;padding:6px 10px;color:#3fb950;font-weight:600">Terpenuhi</th>
                        <th style="text-align:right;padding:6px 10px;color:var(--c-muted);font-weight:600">Total</th>
                    </tr>
                </thead>
                <tbody>${tbody}</tbody>
            </table>
            </div>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
})();
