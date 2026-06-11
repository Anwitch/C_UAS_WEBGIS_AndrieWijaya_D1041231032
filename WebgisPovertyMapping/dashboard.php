<?php
require_once 'config.php';
require_once 'auth/helper.php';
require_once 'koneksi.php';

if (!is_logged_in() || !has_role('administrator')) {
    header('Location: auth/login.php'); exit;
}
require_password_changed('auth/change_password.php');

$page_title    = 'Dashboard Administrator';
$page_subtitle = 'Ringkasan situasi kemiskinan di wilayah Anda';
$active_nav    = 'dashboard';
include 'includes/page-start.php';
?>

<!-- ── Stats Cards ──────────────────────────────────────── -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon teal"><i data-lucide="map-pin"></i></div>
            <div class="stat-label">Total Rumah Ibadah Aktif</div>
        </div>
        <div class="stat-value" id="sc-ibadah"><span class="spinner"></span></div>
        <a href="pages/map.php" class="stat-footer">Lihat di peta →</a>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon purple"><i data-lucide="home"></i></div>
            <div class="stat-label">Total KK Miskin Aktif</div>
        </div>
        <div class="stat-value" id="sc-kk"><span class="spinner"></span></div>
        <a href="pages/map.php" class="stat-footer">Lihat detail →</a>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon green"><i data-lucide="users"></i></div>
            <div class="stat-label">Total Jiwa Miskin Aktif</div>
        </div>
        <div class="stat-value" id="sc-jiwa"><span class="spinner"></span></div>
        <a href="pages/map.php" class="stat-footer">Lihat detail →</a>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon slate"><i data-lucide="check-circle-2"></i></div>
            <div class="stat-label">Total Jiwa Terjangkau</div>
        </div>
        <div class="stat-value" id="sc-terjangkau"><span class="spinner"></span></div>
        <a href="pages/map.php" class="stat-footer">Lihat detail →</a>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon amber"><i data-lucide="alert-triangle"></i></div>
            <div class="stat-label">Total Jiwa Blank Spot</div>
        </div>
        <div class="stat-value" id="sc-blankspot"><span class="spinner"></span></div>
        <a href="pages/map.php" class="stat-footer">Filter di peta →</a>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon blue"><i data-lucide="trending-up"></i></div>
            <div class="stat-label">Persentase Cakupan</div>
        </div>
        <div class="stat-value" id="sc-pct" style="font-size:22px;"><span class="spinner"></span></div>
        <a href="pages/map.php" class="stat-footer">Lihat detail →</a>
    </div>
</div>

<!-- ── Charts + Side Panel ──────────────────────────────── -->
<div class="dash-grid">

    <div class="dash-charts">

        <!-- Donut: Sebaran Kategori -->
        <div class="dash-card">
            <div class="dash-card-title">
                Sebaran Kategori Kemiskinan
                <span class="dash-card-subtitle" id="donut-total"></span>
            </div>
            <div class="chart-wrap"><canvas id="chartKategori"></canvas></div>
            <div id="donut-legend" style="margin-top:14px;display:flex;flex-direction:column;gap:6px;"></div>
        </div>

        <!-- Line: Tren Cakupan -->
        <div class="dash-card">
            <div class="dash-card-title">
                Tren Cakupan Penjangkauan
                <span class="dash-card-subtitle">(6 Bulan Terakhir)</span>
            </div>
            <div class="chart-wrap" style="height:260px;"><canvas id="chartTren"></canvas></div>
            <div id="tren-note" style="margin-top:8px;font-size:11px;color:var(--text-muted);">
                Persentase Cakupan = (Jiwa Terjangkau / Total Jiwa) × 100%
            </div>
        </div>

    </div>

    <!-- Right Panel -->
    <div class="dash-right">

        <!-- Peringatan & Notifikasi -->
        <div class="dash-card">
            <div class="dash-card-title">
                Peringatan &amp; Notifikasi
                <span id="dash-notif-badge"
                      style="display:none;background:#ef4444;color:#fff;font-size:10px;font-weight:600;padding:1px 7px;border-radius:10px;"></span>
            </div>
            <div id="dash-notif-list">
                <div style="font-size:12px;color:var(--text-muted);">
                    <span class="spinner"></span> Memuat...
                </div>
            </div>
            <button type="button" class="link-muted" disabled style="display:block;text-align:center;margin-top:12px;border:0;background:transparent;width:100%;cursor:not-allowed;">
                Semua notifikasi aktif tampil di panel ini
            </button>
        </div>

        <!-- Aksi Cepat -->
        <div class="dash-card">
            <div class="dash-card-title">Aksi Cepat</div>
            <div class="quick-grid">
                <a href="pages/map.php" class="quick-btn">
                    <span class="quick-btn-icon"><i data-lucide="map-pin"></i></span> Buka Peta
                </a>
                <a href="pages/penduduk.php" class="quick-btn">
                    <span class="quick-btn-icon"><i data-lucide="home"></i></span> Kelola Penduduk
                </a>
                <a href="pages/import.php" class="quick-btn" id="qa-import">
                    <span class="quick-btn-icon"><i data-lucide="file-input"></i></span> Import CSV Penduduk
                </a>
                <a href="pages/analisis.php" class="quick-btn" id="qa-recalc">
                    <span class="quick-btn-icon"><i data-lucide="refresh-cw"></i></span> Hitung Ulang Semua
                </a>
                <a href="pages/users.php" class="quick-btn">
                    <span class="quick-btn-icon"><i data-lucide="users"></i></span> Manajemen Pengguna
                </a>
                <a href="pages/laporan.php" class="quick-btn">
                    <span class="quick-btn-icon"><i data-lucide="file-output"></i></span> Export Laporan
                </a>
            </div>
        </div>

        <!-- Statistik Kebutuhan -->
        <div class="dash-card">
            <div class="dash-card-title">
                Statistik Kebutuhan
                <span class="dash-card-subtitle" id="need-date"></span>
            </div>
            <div class="need-stats-grid">
                <div class="need-stat">
                    <div class="need-stat-val amber" id="ns-kk-open">—</div>
                    <div class="need-stat-lbl">KK dengan Kebutuhan Belum Terpenuhi</div>
                </div>
                <div class="need-stat">
                    <div class="need-stat-val blue" id="ns-belum">—</div>
                    <div class="need-stat-lbl">Total Kebutuhan Belum Terpenuhi</div>
                </div>
                <div class="need-stat">
                    <div class="need-stat-val yellow" id="ns-proses">—</div>
                    <div class="need-stat-lbl">Dalam Proses</div>
                </div>
                <div class="need-stat">
                    <div class="need-stat-val green" id="ns-terpenuhi">—</div>
                    <div class="need-stat-lbl">Terpenuhi</div>
                </div>
            </div>
            <a href="papan-kebutuhan.php" class="link-muted" style="display:block;text-align:right;margin-top:10px;">
                Lihat detail kebutuhan →
            </a>
        </div>

    </div><!-- .dash-right -->
</div><!-- .dash-grid -->

<!-- ── Tabel Rekapitulasi ────────────────────────────────── -->
<div class="table-card">
    <div class="table-card-header">
        <div>
            <div class="table-card-title">Rekapitulasi per Rumah Ibadah</div>
            <div class="table-card-count" id="rekap-count"></div>
        </div>
        <a href="pages/map.php" class="btn-see-all">Lihat semua</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th>Nama Rumah Ibadah</th>
                    <th>Jenis</th>
                    <th style="text-align:right;">KK Binaan</th>
                    <th style="text-align:right;">Jiwa Binaan</th>
                    <th style="min-width:160px;">% Sudah Ditangani</th>
                    <th style="width:88px;text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody id="rekapTbody">
                <tr><td colspan="7" style="text-align:center;padding:28px;color:var(--text-muted);">
                    <span class="spinner"></span> Memuat data...
                </td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination">
        <span id="rekap-info"></span>
        <div class="page-btns" id="rekap-pages"></div>
    </div>
</div>

<script>
// ── Helpers ───────────────────────────────────────────────
function fmt(n)    { return Number(n || 0).toLocaleString('id-ID'); }
function fmtP(n)   { return Number(n || 0).toFixed(2).replace('.', ',') + '%'; }
function esc(s)    { const d = document.createElement('div'); d.appendChild(document.createTextNode(s ?? '')); return d.innerHTML; }
function now_label() {
    const d = new Date();
    const M = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
    return `Data per ${d.getDate()} ${M[d.getMonth()]} ${d.getFullYear()}`;
}

// ── Load Stats ────────────────────────────────────────────
async function loadStats() {
    let j;
    try {
        const r = await fetch('api/stats/ambil.php?_=' + Date.now());
        j = await r.json();
    } catch(e) { return; }
    if (j.status !== 'success') return;
    const d = j.data;

    document.getElementById('sc-ibadah').textContent    = fmt(d.total_ibadah);
    document.getElementById('sc-kk').textContent         = fmt(d.total_kk);
    document.getElementById('sc-jiwa').textContent       = fmt(d.total_jiwa);
    document.getElementById('sc-terjangkau').textContent = fmt(d.jiwa_terjangkau);
    document.getElementById('sc-blankspot').textContent  = fmt(d.jiwa_blank_spot);
    document.getElementById('sc-pct').textContent        = fmtP(d.pct_cakupan);

    // Kebutuhan stats
    const rk = d.rekap_kebutuhan || [];
    let belum = 0, proses = 0, terpenuhi = 0;
    rk.forEach(k => {
        belum     += parseInt(k.belum     || 0);
        proses    += parseInt(k.proses    || 0);
        terpenuhi += parseInt(k.terpenuhi || 0);
    });
    document.getElementById('ns-kk-open').textContent  = fmt(d.kk_kebutuhan_open);
    document.getElementById('ns-belum').textContent     = fmt(belum);
    document.getElementById('ns-proses').textContent    = fmt(proses);
    document.getElementById('ns-terpenuhi').textContent = fmt(terpenuhi);
    document.getElementById('need-date').textContent    = now_label();

    renderDonut(d.kategori || []);
    renderRekap(d.rekap_ibadah || []);
}

// ── Donut Chart ────────────────────────────────────────────
let _donut = null;
function renderDonut(rows) {
    const labels = rows.map(r => r.kategori);
    const data   = rows.map(r => parseInt(r.jml_jiwa));
    const total  = data.reduce((a, b) => a + b, 0);
    const colors = ['#ef4444', '#f59e0b', '#3b82f6'];

    document.getElementById('donut-total').textContent = total > 0 ? fmt(total) + ' jiwa total' : '';

    if (_donut) _donut.destroy();
    _donut = new Chart(document.getElementById('chartKategori'), {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 3, borderColor: '#fff' }] },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ` ${fmt(ctx.parsed)} jiwa (${fmtP(total > 0 ? ctx.parsed / total * 100 : 0)})` } }
            }
        }
    });

    document.getElementById('donut-legend').innerHTML = rows.map((r, i) => {
        const pct = total > 0 ? fmtP(parseInt(r.jml_jiwa) / total * 100) : '0%';
        return `<div style="display:flex;align-items:center;gap:8px;font-size:12px;">
            <div style="width:11px;height:11px;border-radius:3px;background:${colors[i]};flex-shrink:0;"></div>
            <span style="font-weight:600;color:var(--text-primary);">${esc(r.kategori)}</span>
            <span style="color:var(--text-muted);">${fmt(r.jml_jiwa)} jiwa &middot; ${pct}</span>
        </div>`;
    }).join('');
}

// ── Trend Line Chart ───────────────────────────────────────
let _tren = null;
async function loadTren() {
    let j;
    try {
        const r = await fetch('api/dashboard/trend.php?_=' + Date.now());
        j = await r.json();
    } catch(e) { return; }
    if (j.status !== 'success' || !j.data) return;

    const labels = j.data.map(d => d.bulan);
    const data   = j.data.map(d => parseFloat(d.pct_cakupan));

    if (_tren) _tren.destroy();
    _tren = new Chart(document.getElementById('chartTren'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Cakupan (%)', data,
                borderColor: '#0d7490', backgroundColor: 'rgba(13,116,144,.06)',
                fill: true, tension: 0.4,
                pointBackgroundColor: '#0d7490', pointBorderColor: '#fafaf9',
                pointBorderWidth: 2, pointRadius: 5, pointHoverRadius: 7,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                y: { min: -10, max: 110,
                    ticks: { callback: v => v >= 0 && v <= 100 ? v + '%' : null, font: { size: 10 }, color: '#7a7067', stepSize: 10 },
                    grid: { color: '#ede8e2' }
                },
                x: {
                    ticks: { font: { size: 10 }, color: '#7a7067' },
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' ' + fmtP(ctx.parsed.y) } }
            }
        }
    });
}

// ── Notifikasi ─────────────────────────────────────────────
function renderNotif(items, total) {
    const el    = document.getElementById('dash-notif-list');
    const badge = document.getElementById('dash-notif-badge');
    if (!el) return;

    if (badge) {
        badge.textContent = total > 99 ? '99+' : total;
        badge.style.display = total > 0 ? '' : 'none';
    }

    if (items.length === 0) {
        el.innerHTML = '<div style="font-size:12px;color:var(--text-muted);text-align:center;padding:12px 0;"><i data-lucide="check" style="color:var(--text-success);margin-right:4px;"></i> Tidak ada notifikasi baru.</div>';
        if (typeof lucide !== 'undefined') lucide.createIcons({attrs: {class: 'lucide'}});
        return;
    }

    el.innerHTML = items.map(it => `
        <a href="${esc(it.page || '#')}" class="notif-item">
            <div class="notif-dot ${esc(it.type)}"><i data-lucide="${esc(it.icon)}"></i></div>
            <div class="notif-text">
                <div class="notif-title">${esc(it.label)}</div>
                <div class="notif-body">${fmt(it.count)} item aktif</div>
            </div>
            <div class="notif-time">Aktif</div>
        </a>`).join('');
    if (typeof lucide !== 'undefined') lucide.createIcons({attrs: {class: 'lucide'}});
}

async function loadDashboardNotifs() {
    const el = document.getElementById('dash-notif-list');
    if (el) {
        el.innerHTML = '<div style="font-size:12px;color:var(--text-muted);"><span class="spinner"></span> Memuat...</div>';
    }
    try {
        const r = await fetch('api/notif/ambil.php?_=' + Date.now(), { cache: 'no-store' });
        const j = await r.json();
        if (j.status !== 'success') throw new Error('notif failed');
        renderNotif(j.items || [], parseInt(j.total || 0));
    } catch (e) {
        if (el) el.innerHTML = '<div style="font-size:12px;color:var(--text-muted);text-align:center;padding:12px 0;">Gagal memuat notifikasi.</div>';
    }
}

// ── Rekap Table + Pagination ───────────────────────────────
const PER_PAGE = 5;
let _allRows = [], _page = 1;

function renderRekap(rows) {
    _allRows = rows;
    _page    = 1;
    document.getElementById('rekap-count').textContent =
        `Menampilkan ${Math.min(PER_PAGE, rows.length)} dari ${rows.length} rumah ibadah`;
    _drawPage();
}

function _drawPage() {
    const start = (_page - 1) * PER_PAGE;
    const slice = _allRows.slice(start, start + PER_PAGE);
    const pages = Math.ceil(_allRows.length / PER_PAGE) || 1;
    const tbody = document.getElementById('rekapTbody');

    if (slice.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:28px;color:var(--text-muted);">Belum ada data rumah ibadah.</td></tr>';
        document.getElementById('rekap-info').textContent  = '';
        document.getElementById('rekap-pages').innerHTML = '';
        return;
    }

    const badgeCls = { Masjid:'masjid', Mushola:'mushola', Gereja:'gereja', Pura:'pura', Vihara:'vihara', Klenteng:'klenteng' };
    tbody.innerHTML = slice.map((r, i) => {
        const jiwa  = parseInt(r.jml_jiwa || 0);
        const tang  = parseInt(r.jml_ditangani || 0);
        const pct   = jiwa > 0 ? Math.round(tang / jiwa * 100) : 0;
        const col   = pct >= 60 ? '#16a34a' : pct >= 30 ? '#f59e0b' : '#ef4444';
        const badge = `<span class="jenis-badge ${badgeCls[r.jenis] || ''}">${esc(r.jenis)}</span>`;
        return `<tr>
            <td style="color:var(--text-muted);font-size:12px;">${start + i + 1}</td>
            <td style="font-weight:600;">${esc(r.nama)}</td>
            <td>${badge}</td>
            <td style="text-align:right;font-family:var(--font-mono);">${fmt(r.jml_kk)}</td>
            <td style="text-align:right;font-family:var(--font-mono);">${fmt(jiwa)}</td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div class="progress-bar-wrap" style="flex:1;">
                        <div class="progress-bar-fill" style="width:${pct}%;background:${col};"></div>
                    </div>
                    <span style="font-size:12px;font-weight:600;color:${col};min-width:36px;">${pct}%</span>
                </div>
            </td>
            <td style="text-align:center;">
                <a href="pages/map.php" class="tbl-action-btn" title="Lihat di Peta"><i data-lucide="eye"></i></a>
            </td>
        </tr>`;
    }).join('');

    const endRow = Math.min(start + PER_PAGE, _allRows.length);
    document.getElementById('rekap-info').textContent =
        `Menampilkan ${start + 1}–${endRow} dari ${_allRows.length} rumah ibadah`;

    let html = `<button class="page-btn" onclick="goPage(${_page - 1})" ${_page === 1 ? 'disabled' : ''}><i data-lucide="chevron-left"></i></button>`;
    for (let p = 1; p <= pages; p++)
        html += `<button class="page-btn ${p === _page ? 'active' : ''}" onclick="goPage(${p})">${p}</button>`;
    html += `<button class="page-btn" onclick="goPage(${_page + 1})" ${_page === pages ? 'disabled' : ''}><i data-lucide="chevron-right"></i></button>`;
    document.getElementById('rekap-pages').innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons({attrs: {class: 'lucide'}});
}

function goPage(p) {
    const pages = Math.ceil(_allRows.length / PER_PAGE) || 1;
    if (p < 1 || p > pages) return;
    _page = p;
    _drawPage();
}

// ── Boot ──────────────────────────────────────────────────
window.addEventListener('load', function () {
    loadStats();
    loadTren();
    loadDashboardNotifs();
});
</script>

<?php include 'includes/page-end.php'; ?>
