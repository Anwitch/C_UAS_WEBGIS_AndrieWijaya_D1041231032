<?php
require_once '../config.php';
require_once '../auth/helper.php';
require_once '../koneksi.php';
if (!is_logged_in() || !has_role('administrator')) { header('Location: ../auth/login.php'); exit; }
require_password_changed('../auth/change_password.php');
$page_title    = 'Analisis & Blank Spot';
$page_subtitle = 'Warga yang tidak terjangkau radius rumah ibadah manapun';
$active_nav    = 'analisis';
include '../includes/page-start.php';
?>

<style>
.kat-sm{background:#fef2f2;color:#ef4444;}
.kat-m{background:#fffbeb;color:#f59e0b;}
.kat-hm{background:#eff6ff;color:#3b82f6;}
.text-danger{color:#dc2626;}
.text-success{color:#16a34a;}
</style>

<!-- Summary Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-card-top"><div class="stat-icon amber"><i data-lucide="alert-triangle"></i></div><div class="stat-label">Jiwa Blank Spot</div></div>
        <div class="stat-value text-danger" id="an-blank"><span class="spinner"></span></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top"><div class="stat-icon blue"><i data-lucide="percent"></i></div><div class="stat-label">% Blank Spot dari Total</div></div>
        <div class="stat-value" style="font-size:22px;" id="an-pct"><span class="spinner"></span></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top"><div class="stat-icon green"><i data-lucide="check-circle-2"></i></div><div class="stat-label">Jiwa Terjangkau</div></div>
        <div class="stat-value text-success" id="an-cover"><span class="spinner"></span></div>
    </div>
    <div class="stat-card" style="cursor:pointer;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.12)'" onmouseout="this.style.boxShadow=''" onclick="recalcAll()">
        <div class="stat-card-top"><div class="stat-icon slate"><i data-lucide="refresh-cw"></i></div><div class="stat-label">Hitung Ulang Proximity</div></div>
        <div class="stat-value" style="font-size:14px;font-family:var(--font);color:var(--text-secondary);" id="an-recalc">Klik untuk jalankan</div>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div>
            <div class="table-card-title">Daftar Warga Blank Spot</div>
            <div class="table-card-count" id="an-count"></div>
        </div>
        <a href="map.php" class="btn-see-all"><i data-lucide="map" style="width:14px;height:14px;margin-right:4px;"></i> Lihat di Peta</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th>Nama KK</th>
                    <th>Kategori</th>
                    <th style="text-align:right;">Jiwa</th>
                    <th>Alamat</th>
                    <th>Jarak ke Ibadah Terdekat</th>
                </tr>
            </thead>
            <tbody id="anTbody">
                <tr><td colspan="6" style="text-align:center;padding:28px;color:var(--text-muted);"><span class="spinner"></span></td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function esc(s){const d=document.createElement('div');d.appendChild(document.createTextNode(s??''));return d.innerHTML;}
function fmt(n){return Number(n||0).toLocaleString('id-ID');}

async function loadAnalisis() {
    try {
        const [rs, rp] = await Promise.all([
            fetch('../api/stats/ambil.php?_='+Date.now()),
            fetch('../api/penduduk/ambil.php?_='+Date.now())
        ]);
        const js = await rs.json();
        const jp = await rp.json();

        if (js.status === 'success') {
            const d = js.data;
            document.getElementById('an-blank').textContent = fmt(d.jiwa_blank_spot);
            document.getElementById('an-cover').textContent = fmt(d.jiwa_terjangkau);
            const pct = d.total_jiwa > 0
                ? ((d.jiwa_blank_spot / d.total_jiwa) * 100).toFixed(1) + '%'
                : '0%';
            document.getElementById('an-pct').textContent = pct;
        }

        if (jp.status === 'success') {
            const blanks = jp.data.filter(p => parseInt(p.is_blank_spot) === 1);
            document.getElementById('an-count').textContent = blanks.length + ' warga blank spot';
            const katCls = {'Sangat Miskin':'kat-sm','Miskin':'kat-m','Hampir Miskin':'kat-hm'};
            document.getElementById('anTbody').innerHTML = blanks.length
                ? blanks.map((p, i) => `<tr>
                    <td style="color:var(--text-muted);font-size:12px;">${i + 1}</td>
                    <td style="font-weight:600;">${esc(p.nama_kk||'—')}</td>
                    <td><span style="display:inline-block;padding:2px 9px;border-radius:9999px;font-size:11px;font-weight:500;" class="${esc(katCls[p.kategori]||'')}">${esc(p.kategori||'—')}</span></td>
                    <td style="text-align:right;font-family:var(--font-mono);">${esc(String(p.jumlah_jiwa||0))}</td>
                    <td style="font-size:12px;color:var(--text-muted);">${esc(p.alamat||'—')}</td>
                    <td style="font-size:12px;color:var(--text-muted);">${p.jarak_m ? esc(String(Math.round(p.jarak_m)))+' m (di luar radius)' : '—'}</td>
                </tr>`).join('')
                : '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);"><i data-lucide="check" style="color:var(--text-success);margin-right:4px;"></i> Tidak ada blank spot saat ini.</td></tr>';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    } catch(e) {
        document.getElementById('anTbody').innerHTML =
            '<tr><td colspan="6" style="text-align:center;padding:24px;color:#ef4444;">Gagal memuat data.</td></tr>';
    }
}

function showConfirm({ title, message, confirmLabel = 'Ya, Lanjutkan', cancelLabel = 'Batal', danger = false }) {
    return new Promise(resolve => {
        const el = document.createElement('div');
        el.className = 'confirm-overlay';
        el.innerHTML = `<div class="confirm-box">
            <div class="confirm-title">${title}</div>
            <p class="confirm-msg">${message}</p>
            <div class="confirm-actions">
                <button class="btn-secondary" id="_sc_cancel">${cancelLabel}</button>
                <button class="${danger ? 'btn-danger' : 'btn-primary'}" id="_sc_ok">${confirmLabel}</button>
            </div></div>`;
        document.body.appendChild(el);
        const done = val => { el.remove(); resolve(val); };
        el.querySelector('#_sc_ok').onclick     = () => done(true);
        el.querySelector('#_sc_cancel').onclick = () => done(false);
        el.addEventListener('click', e => { if (e.target === el) done(false); });
    });
}

async function recalcAll() {
    const ok = await showConfirm({
        title: 'Hitung Ulang Proximity',
        message: 'Proses ini akan mengubah assignment warga ke rumah ibadah/operator berdasarkan radius terkini. Lanjutkan?',
        confirmLabel: 'Ya, Hitung Ulang'
    });
    if (!ok) return;

    const lbl = document.getElementById('an-recalc');
    lbl.innerHTML = '<span class="spinner"></span> Menghitung...';
    try {
        const r = await fetch('../api/ibadah/recalculate.php', {method:'POST', body: appendCsrf(new FormData())});
        const j = await r.json();
        lbl.innerHTML = j.status === 'success' ? '<i data-lucide="check"></i> Selesai!' : '<i data-lucide="x"></i> Gagal: '+(j.message||'');
        if (typeof lucide !== 'undefined') lucide.createIcons();
        setTimeout(() => { lbl.textContent = 'Klik untuk jalankan'; loadAnalisis(); }, 2500);
    } catch(e) {
        lbl.innerHTML = '<i data-lucide="x"></i> Error koneksi';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        setTimeout(() => { lbl.textContent = 'Klik untuk jalankan'; }, 2500);
    }
}

loadAnalisis();
</script>

<?php include '../includes/page-end.php'; ?>
