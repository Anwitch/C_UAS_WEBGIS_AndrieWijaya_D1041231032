<?php
require_once '../config.php';
require_once '../auth/helper.php';
require_once '../koneksi.php';
if (!is_logged_in() || !has_role('operator')) { header('Location: ../auth/login.php'); exit; }
require_password_changed('../auth/change_password.php');
$ibadah_id = get_ibadah_id();
$page_title    = 'Status Bantuan';
$page_subtitle = 'Monitor dan update status penyaluran bantuan per warga';
$active_nav    = 'status';
include '../includes/page-start.php';
?>
<script>
window._IBADAH_ID = <?= $ibadah_id ? (int)$ibadah_id : 'null' ?>;
window._IS_ADMIN  = <?= has_role('administrator') ? 'true' : 'false' ?>;
</script>

<style>
.sb-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;}
@media(max-width:600px){.sb-cards{grid-template-columns:1fr;}}
.stat-sel-sb{padding:5px 10px;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;border:1px solid var(--card-border);background:#fafaf9;font-family:var(--font);color:var(--text-secondary);}
.stat-sel-sb.belum{background:#f5f0eb;color:#7a7067;border-color:#c5bdb5;}
.stat-sel-sb.proses{background:#fffbeb;color:#d97706;border-color:#fcd34d;}
.stat-sel-sb.sudah{background:#f0fdf4;color:#16a34a;border-color:#86efac;}
.text-danger{color:#dc2626;}
.text-success{color:#16a34a;}
.sb-filter-bar{display:flex;flex-direction:column;gap:8px;margin-bottom:16px;}
.sb-input{width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid var(--card-border);border-radius:8px;font-family:var(--font);font-size:13px;background:#fafaf9;color:var(--text-primary);outline:none;}
.sb-input:focus{border-color:#0d7490;}
.sb-filter-chips{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.sb-chip{width:auto;height:auto;padding:7px 11px;border:1px solid var(--card-border);border-radius:8px;font-family:var(--font);font-size:13px;background:#fafaf9;color:var(--text-primary);cursor:pointer;white-space:nowrap;}
.sb-chip:focus{outline:none;border-color:#0d7490;}
</style>

<div class="sb-cards">
    <div class="stat-card">
        <div class="stat-card-top"><div class="stat-icon amber"><i data-lucide="clipboard-list"></i></div><div class="stat-label">Belum Ditangani</div></div>
        <div class="stat-value text-danger" id="sb-belum">-</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top"><div class="stat-icon blue"><i data-lucide="clock"></i></div><div class="stat-label">Dalam Proses</div></div>
        <div class="stat-value" style="color:#d97706;" id="sb-proses">-</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top"><div class="stat-icon green"><i data-lucide="check-circle-2"></i></div><div class="stat-label">Sudah Ditangani</div></div>
        <div class="stat-value text-success" id="sb-sudah">-</div>
    </div>
</div>

<div class="sb-filter-bar">
    <input type="text" class="sb-input" id="sbSearch" placeholder="Cari nama KK..." oninput="filterSb()">
    <div class="sb-filter-chips">
        <select class="sb-chip" id="sbFilter" onchange="filterSb()">
            <option value="">Semua Status</option>
            <option value="Belum Ditangani">Belum Ditangani</option>
            <option value="Dalam Proses">Dalam Proses</option>
            <option value="Sudah Ditangani">Sudah Ditangani</option>
        </select>
        <div style="width:1px;height:20px;background:var(--card-border);margin:0 2px;align-self:center;flex-shrink:0;"></div>
        <select class="sb-chip" id="sbSort" onchange="filterSb()">
            <option value="">Urutkan</option>
            <option value="nama_asc">Nama A→Z</option>
            <option value="nama_desc">Nama Z→A</option>
            <option value="jiwa_desc">Jiwa Terbanyak</option>
            <option value="jiwa_asc">Jiwa Tersedikit</option>
            <option value="updated_desc">Diupdate Terbaru</option>
            <option value="updated_asc">Diupdate Terlama</option>
        </select>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div class="table-card-title">Daftar Penyaluran Bantuan</div>
        <span class="table-card-count" id="sb-count"></span>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th>Nama KK</th>
                    <th style="text-align:right;">Jiwa</th>
                    <th>Ibadah</th>
                    <th>Status</th>
                    <th>Terakhir Diupdate</th>
                </tr>
            </thead>
            <tbody id="sbTbody">
                <tr><td colspan="6" style="text-align:center;padding:28px;color:var(--text-muted);"><span class="spinner"></span> Memuat...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let _sbAll = [];
function esc(s){const d=document.createElement('div');d.appendChild(document.createTextNode(s??''));return d.innerHTML;}
function attr(s){return String(s??'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function fmt(n){return Number(n||0).toLocaleString('id-ID');}

async function loadSb() {
    try {
        const r = await fetch('../api/penduduk/ambil.php?_='+Date.now());
        const j = await r.json();
        if (j.status !== 'success') {
            document.getElementById('sbTbody').innerHTML =
                '<tr><td colspan="6" style="text-align:center;padding:24px;color:#ef4444;">'+esc(j.message || 'Gagal memuat data.')+'</td></tr>';
            return;
        }
        const verifiedRows = j.data.filter(p => p.status_verifikasi === 'Terverifikasi');
        _sbAll = (window._IBADAH_ID && !window._IS_ADMIN)
            ? verifiedRows.filter(p => parseInt(p.ibadah_id) === window._IBADAH_ID)
            : verifiedRows;
        updateCards();
        filterSb();
    } catch(e) {
        document.getElementById('sbTbody').innerHTML =
            '<tr><td colspan="6" style="text-align:center;padding:24px;color:#ef4444;">Gagal memuat data.</td></tr>';
    }
}

function updateCards() {
    const b  = _sbAll.filter(p => p.status_bantuan === 'Belum Ditangani').length;
    const pr = _sbAll.filter(p => p.status_bantuan === 'Dalam Proses').length;
    const s  = _sbAll.filter(p => p.status_bantuan === 'Sudah Ditangani').length;
    document.getElementById('sb-belum').textContent = fmt(b);
    document.getElementById('sb-proses').textContent = fmt(pr);
    document.getElementById('sb-sudah').textContent  = fmt(s);
}

function filterSb() {
    const q    = document.getElementById('sbSearch').value.toLowerCase();
    const f    = document.getElementById('sbFilter').value;
    const sort = document.getElementById('sbSort').value;
    const rows = _sbAll.filter(p =>
        (!q || (p.nama_kk||'').toLowerCase().includes(q)) &&
        (!f || p.status_bantuan === f)
    );
    if (sort) {
        rows.sort((a, b) => {
            switch (sort) {
                case 'nama_asc':     return (a.nama_kk||'').localeCompare(b.nama_kk||'', 'id');
                case 'nama_desc':    return (b.nama_kk||'').localeCompare(a.nama_kk||'', 'id');
                case 'jiwa_desc':    return (b.jumlah_jiwa||0) - (a.jumlah_jiwa||0);
                case 'jiwa_asc':     return (a.jumlah_jiwa||0) - (b.jumlah_jiwa||0);
                case 'updated_desc': return new Date(b.updated_at||b.created_at||0) - new Date(a.updated_at||a.created_at||0);
                case 'updated_asc':  return new Date(a.updated_at||a.created_at||0) - new Date(b.updated_at||b.created_at||0);
                default: return 0;
            }
        });
    }
    document.getElementById('sb-count').textContent = rows.length + ' warga';
    const clsMap = {'Belum Ditangani':'belum','Dalam Proses':'proses','Sudah Ditangani':'sudah'};
    document.getElementById('sbTbody').innerHTML = rows.length
        ? rows.map((p, i) => `<tr>
            <td style="color:var(--text-muted);font-size:12px;">${i+1}</td>
            <td style="font-weight:600;">${esc(p.nama_kk||'—')}</td>
            <td style="text-align:right;font-family:var(--font-mono);">${esc(String(p.jumlah_jiwa||0))}</td>
            <td style="font-size:12px;color:var(--text-muted);">${esc(p.nama_ibadah||'—')}</td>
            <td>
                <select class="stat-sel-sb ${esc(clsMap[p.status_bantuan]||'')}"
                    data-id="${attr(parseInt(p.id, 10) || 0)}" data-orig="${attr(p.status_bantuan||'')}"
                    onchange="updateStatusSb(this)">
                    <option ${p.status_bantuan==='Belum Ditangani'?'selected':''}>Belum Ditangani</option>
                    <option ${p.status_bantuan==='Dalam Proses'?'selected':''}>Dalam Proses</option>
                    <option ${p.status_bantuan==='Sudah Ditangani'?'selected':''}>Sudah Ditangani</option>
                </select>
            </td>
            <td style="font-size:12px;color:var(--text-muted);">${esc((p.updated_at||p.created_at||'').slice(0,10))}</td>
        </tr>`).join('')
        : '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">Tidak ada data.</td></tr>';
}

async function updateStatusSb(sel) {
    const id  = sel.dataset.id;
    const orig = sel.dataset.orig;
    const catatan = prompt('Catatan perubahan status (opsional):', '');
    if (catatan === null) {
        sel.value = orig;
        return;
    }
    const fd = new FormData();
    fd.append('id', id); fd.append('status', sel.value); fd.append('catatan', catatan);
    try {
        const r = await fetch('../api/penduduk/update_status.php', {method:'POST', body: appendCsrf(fd)});
        const j = await r.json();
        if (j.status === 'success') {
            sel.dataset.orig = sel.value;
            const clsMap = {'Belum Ditangani':'belum','Dalam Proses':'proses','Sudah Ditangani':'sudah'};
            sel.className = 'stat-sel-sb ' + (clsMap[sel.value]||'');
            const item = _sbAll.find(p => String(p.id) === String(id));
            if (item) item.status_bantuan = sel.value;
            updateCards();
        } else {
            alert(j.message||'Gagal update status.');
            sel.value = orig;
        }
    } catch(e) {
        alert('Gagal terhubung ke server.');
        sel.value = orig;
    }
}

loadSb();
</script>

<?php include '../includes/page-end.php'; ?>
