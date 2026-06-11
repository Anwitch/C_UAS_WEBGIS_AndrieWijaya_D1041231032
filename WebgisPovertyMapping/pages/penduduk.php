<?php
require_once '../config.php';
require_once '../auth/helper.php';
require_once '../koneksi.php';
if (!is_logged_in() || !has_role('operator')) { header('Location: ../auth/login.php'); exit; }
require_password_changed('../auth/change_password.php');
$is_admin  = has_role('administrator');
$ibadah_id = get_ibadah_id();
$page_title    = 'Penduduk Miskin';
$page_subtitle = 'Daftar dan manajemen data penduduk miskin';
$active_nav    = 'penduduk';
include '../includes/page-start.php';
?>
<script>
window._IBADAH_ID = <?= $ibadah_id ? (int)$ibadah_id : 'null' ?>;
window._IS_ADMIN  = <?= $is_admin ? 'true' : 'false' ?>;
</script>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<style>
.pd-filter-bar{display:flex;flex-direction:column;gap:8px;margin-bottom:16px;}
.pd-input{width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid var(--card-border);border-radius:8px;font-family:var(--font);font-size:13px;background:#fafaf9;color:var(--text-primary);outline:none;}
.pd-input:focus{border-color:#0d7490;}
.pd-filter-chips{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.pd-chip{width:auto;height:auto;padding:7px 11px;border:1px solid var(--card-border);border-radius:8px;font-family:var(--font);font-size:13px;background:#fafaf9;color:var(--text-primary);cursor:pointer;white-space:nowrap;}
.pd-chip:focus{outline:none;border-color:#0d7490;}
.pd-chip-spacer{flex:1;}
.kat-badge{display:inline-block;padding:2px 9px;border-radius:9999px;font-size:11px;font-weight:500;}
.kat-sm{background:#fef2f2;color:#ef4444;}
.kat-m{background:#fffbeb;color:#f59e0b;}
.kat-hm{background:#eff6ff;color:#3b82f6;}
.bs-badge{display:inline-block;padding:2px 9px;border-radius:9999px;font-size:11px;font-weight:500;}
.bs-blank{background:#fef2f2;color:#ef4444;}
.bs-cover{background:#f0fdf4;color:#16a34a;}
.sv-badge{display:inline-flex;align-items:center;gap:3px;padding:2px 9px;border-radius:9999px;font-size:11px;font-weight:500;}
.sv-pending{background:#fef3c7;color:#d97706;}
.sv-ok{background:#d1fae5;color:#16a34a;}
.sv-tolak{background:#fee2e2;color:#dc2626;}
.stat-sel{padding:5px 10px;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;border:1px solid var(--card-border);background:#fafaf9;font-family:var(--font);color:var(--text-secondary);}
.stat-sel.belum{background:#f5f0eb;color:#7a7067;border-color:#c5bdb5;}
.stat-sel.proses{background:#fffbeb;color:#d97706;border-color:#fcd34d;}
.stat-sel.sudah{background:#f0fdf4;color:#16a34a;border-color:#86efac;}
.modal-dark{position:fixed;inset:0;background:rgba(32,21,21,.55);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:20px;z-index:9999;opacity:0;pointer-events:none;transition:opacity .2s;}
.modal-dark.show{opacity:1;pointer-events:auto;}
.modal-box{width:min(620px,100%);background:#fafaf9;border:1px solid var(--card-border);border-radius:12px;padding:24px;box-shadow:0 4px 16px rgba(32,21,21,.1);max-height:90vh;display:flex;flex-direction:column;}
.modal-box h3{font-size:15px;font-weight:600;margin-bottom:14px;color:var(--text-primary);flex-shrink:0;}
.modal-scroll{overflow-y:auto;flex:1;padding-right:4px;}
.mfg{margin-bottom:14px;}
.mfg label{display:block;font-size:11px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;}
.mfg input,.mfg select,.mfg textarea{width:100%;padding:9px 12px;border:1px solid var(--card-border);border-radius:8px;font-family:var(--font);font-size:13px;color:var(--text-primary);background:#fafaf9;outline:none;transition:border-color .2s;}
.mfg input:focus,.mfg select:focus,.mfg textarea:focus{border-color:#0d7490;}
.mfg textarea{resize:vertical;min-height:70px;}
.mfg small{font-size:11px;color:var(--text-muted);margin-top:4px;display:block;}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:16px;flex-shrink:0;padding-top:14px;border-top:1px solid var(--card-border);}
.btn-cancel{padding:9px 16px;background:transparent;border:1px solid var(--card-border);border-radius:8px;font-family:var(--font);font-size:13px;font-weight:600;color:var(--text-secondary);cursor:pointer;transition:background .15s;}
.btn-cancel:hover{background:#ede8e2;}
.btn-primary{padding:9px 18px;background:#0d7490;border:none;border-radius:8px;font-family:var(--font);font-size:14px;font-weight:600;color:#fff;cursor:pointer;transition:background .15s;}
.btn-primary:hover{background:#0a5f7a;}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;}
.btn-approve{padding:4px 10px;background:#f0fdf4;border:1px solid #86efac;border-radius:9999px;font-size:11px;font-weight:500;color:#16a34a;cursor:pointer;white-space:nowrap;}
.btn-approve:hover{background:#dcfce7;}
.btn-reject{padding:4px 10px;background:#fef2f2;border:1px solid #fca5a5;border-radius:9999px;font-size:11px;font-weight:500;color:#dc2626;cursor:pointer;white-space:nowrap;}
.btn-reject:hover{background:#fee2e2;}
.msg-err{font-size:12px;color:#ef4444;margin-top:6px;min-height:16px;}
.map-picker-wrap{border:1px solid var(--card-border);border-radius:10px;overflow:hidden;}
.map-picker-wrap #pdMapPicker{height:240px;cursor:crosshair;}
.map-picker-hint{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-muted);margin-top:6px;}
.map-geocoding{font-size:11px;color:var(--text-muted);margin-top:4px;min-height:16px;font-style:italic;}
</style>

<!-- Filter Bar -->
<div class="pd-filter-bar">
    <input type="text" class="pd-input" id="pdSearch" placeholder="Cari nama KK..." oninput="applyFilter()">
    <div class="pd-filter-chips">
        <select class="pd-chip" id="pdKat" onchange="applyFilter()">
            <option value="">Semua Kategori</option>
            <option value="Sangat Miskin">Sangat Miskin</option>
            <option value="Miskin">Miskin</option>
            <option value="Hampir Miskin">Hampir Miskin</option>
        </select>
        <select class="pd-chip" id="pdStatus" onchange="applyFilter()">
            <option value="">Semua Status</option>
            <option value="Belum Ditangani">Belum Ditangani</option>
            <option value="Dalam Proses">Dalam Proses</option>
            <option value="Sudah Ditangani">Sudah Ditangani</option>
        </select>
        <select class="pd-chip" id="pdBlank" onchange="applyFilter()">
            <option value="">Semua Spot</option>
            <option value="1">Blank Spot</option>
            <option value="0">Terjangkau</option>
        </select>
        <select class="pd-chip" id="pdVerif" onchange="applyFilter()">
            <option value="">Semua Verifikasi</option>
            <option value="Pending">Pending</option>
            <option value="Terverifikasi">Terverifikasi</option>
            <option value="Ditolak">Ditolak</option>
        </select>
        <div style="width:1px;height:20px;background:var(--card-border);margin:0 2px;align-self:center;flex-shrink:0;"></div>
        <select class="pd-chip" id="pdSort" onchange="applyFilter()">
            <option value="">Urutkan</option>
            <option value="nama_asc">Nama A→Z</option>
            <option value="nama_desc">Nama Z→A</option>
            <option value="jiwa_desc">Jiwa Terbanyak</option>
            <option value="jiwa_asc">Jiwa Tersedikit</option>
            <option value="jarak_asc">Jarak Terdekat</option>
            <option value="jarak_desc">Jarak Terjauh</option>
        </select>
        <div class="pd-chip-spacer"></div>
        <a href="map.php" class="btn-see-all" style="text-decoration:none;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:4px;white-space:nowrap;"><i data-lucide="map"></i> Peta</a>
        <button class="btn-primary" onclick="openAddPd()" style="white-space:nowrap;">+ Tambah Penduduk</button>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div>
            <div class="table-card-title">Daftar Penduduk Miskin</div>
            <div class="table-card-count" id="pdCount"></div>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:40px;">No</th>
                    <th>Nama KK</th>
                    <th>Kategori</th>
                    <th style="text-align:right;">Jiwa</th>
                    <th>Rumah Ibadah</th>
                    <th>Status Bantuan</th>
                    <th>Keterjangkauan</th>
                    <th>Verifikasi</th>
                    <th style="width:100px;text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody id="pdTbody">
                <tr><td colspan="8" style="text-align:center;padding:28px;color:var(--text-muted);"><span class="spinner"></span> Memuat...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination">
        <span id="pd-info"></span>
        <div class="page-btns" id="pd-pages"></div>
    </div>
</div>

<!-- Riwayat Modal -->
<div class="modal-dark" id="riwayatModal">
    <div class="modal-box">
        <h3 id="riwayatTitle">Riwayat Bantuan</h3>
        <div class="modal-scroll" id="riwayatBody" style="font-size:13px;"></div>
        <div style="margin-top:14px;flex-shrink:0;">
            <button style="padding:8px 16px;background:transparent;border:1px solid var(--card-border);border-radius:8px;font-family:var(--font);font-size:13px;font-weight:600;color:var(--text-secondary);cursor:pointer;" onclick="document.getElementById('riwayatModal').classList.remove('show')">Tutup</button>
        </div>
    </div>
</div>

<script>
const PD_PER = 15;
let _pdAll = [], _pdFiltered = [], _pdPage = 1;

function esc(s) { const d = document.createElement('div'); d.appendChild(document.createTextNode(s ?? '')); return d.innerHTML; }
function attr(s) { return String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
function fmt(n) { return Number(n || 0).toLocaleString('id-ID'); }

async function loadPd() {
    try {
        const r = await fetch('../api/penduduk/ambil.php?_=' + Date.now());
        const j = await r.json();
        if (j.status !== 'success') return;
        // Operator: filter to own ibadah only
        _pdAll = (window._IBADAH_ID !== null && !window._IS_ADMIN)
            ? j.data.filter(p => parseInt(p.ibadah_id) === window._IBADAH_ID)
            : j.data;
        document.getElementById('pdCount').textContent = _pdAll.length + ' penduduk';
        applyFilter();
    } catch(e) {
        document.getElementById('pdTbody').innerHTML =
            '<tr><td colspan="8" style="text-align:center;padding:24px;color:#ef4444;">Gagal memuat data.</td></tr>';
    }
}

function applyFilter() {
    const q     = document.getElementById('pdSearch').value.toLowerCase();
    const kat   = document.getElementById('pdKat').value;
    const stat  = document.getElementById('pdStatus').value;
    const blank = document.getElementById('pdBlank').value;
    const verif = document.getElementById('pdVerif').value;
    const sort  = document.getElementById('pdSort').value;
    _pdFiltered = _pdAll.filter(p =>
        (!q     || (p.nama_kk || '').toLowerCase().includes(q)) &&
        (!kat   || p.kategori === kat) &&
        (!stat  || p.status_bantuan === stat) &&
        (blank === '' || String(p.is_blank_spot) === blank) &&
        (!verif || (p.status_verifikasi || 'Pending') === verif)
    );
    if (sort) {
        _pdFiltered.sort((a, b) => {
            switch (sort) {
                case 'nama_asc':    return (a.nama_kk||'').localeCompare(b.nama_kk||'', 'id');
                case 'nama_desc':   return (b.nama_kk||'').localeCompare(a.nama_kk||'', 'id');
                case 'jiwa_desc':   return (b.jumlah_jiwa||0) - (a.jumlah_jiwa||0);
                case 'jiwa_asc':    return (a.jumlah_jiwa||0) - (b.jumlah_jiwa||0);
                case 'jarak_asc':   return (a.jarak_m||0) - (b.jarak_m||0);
                case 'jarak_desc':  return (b.jarak_m||0) - (a.jarak_m||0);
                default: return 0;
            }
        });
    }
    _pdPage = 1;
    renderPage();
}

function renderPage() {
    const start = (_pdPage - 1) * PD_PER;
    const slice = _pdFiltered.slice(start, start + PD_PER);
    const pages = Math.ceil(_pdFiltered.length / PD_PER) || 1;
    const tbody = document.getElementById('pdTbody');

    const katCls  = { 'Sangat Miskin': 'kat-sm', 'Miskin': 'kat-m', 'Hampir Miskin': 'kat-hm' };
    const statCls = { 'Belum Ditangani': 'belum', 'Dalam Proses': 'proses', 'Sudah Ditangani': 'sudah' };

    if (!slice.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--text-muted);">Tidak ada data.</td></tr>';
    } else {
        tbody.innerHTML = slice.map((p, i) => {
            const ibadahText = p.nama_ibadah
                ? `${esc(p.nama_ibadah)}<br><small style="color:var(--text-muted);">${fmt(Math.round(p.jarak_m || 0))} m</small>`
                : '<span style="color:var(--text-muted);">—</span>';
            const nonaktifBtn = window._IS_ADMIN
                ? `<button class="tbl-action-btn" title="Nonaktifkan" style="color:#d97706;" data-id="${attr(parseInt(p.id, 10) || 0)}" onclick="doNonaktifkan(this.dataset.id)"><i data-lucide="ban"></i></button>`
                : '';
            const sc = statCls[p.status_bantuan] || '';
            const sv = p.status_verifikasi || 'Pending';
            const isVerified = sv === 'Terverifikasi';
            const svCls = { Terverifikasi: 'sv-ok', Pending: 'sv-pending', Ditolak: 'sv-tolak' }[sv] || 'sv-pending';
            const svIcon = {
                Terverifikasi: '<i data-lucide="check-circle-2" style="width:12px;height:12px;vertical-align:middle;margin-right:2px;"></i>',
                Pending: '<i data-lucide="clock" style="width:12px;height:12px;vertical-align:middle;margin-right:2px;"></i>',
                Ditolak: '<i data-lucide="x-circle" style="width:12px;height:12px;vertical-align:middle;margin-right:2px;"></i>'
            }[sv] || '';
            const verifBtns = (window._IS_ADMIN && sv === 'Pending')
                ? `<div style="display:flex;gap:4px;margin-top:4px;">
                    <button class="btn-approve" data-id="${attr(parseInt(p.id,10)||0)}" onclick="doVerifRow(this,'approve')"><i data-lucide="check" style="width:10px;height:10px;"></i></button>
                    <button class="btn-reject"  data-id="${attr(parseInt(p.id,10)||0)}" onclick="doVerifRow(this,'reject')"><i data-lucide="x" style="width:10px;height:10px;"></i></button>
                   </div>` : '';
            const statusControl = isVerified
                ? `<select class="stat-sel ${esc(sc)}"
                        onchange="updateStatus(${p.id}, this)"
                        data-orig="${esc(p.status_bantuan || '')}">
                        <option ${p.status_bantuan === 'Belum Ditangani' ? 'selected' : ''}>Belum Ditangani</option>
                        <option ${p.status_bantuan === 'Dalam Proses'    ? 'selected' : ''}>Dalam Proses</option>
                        <option ${p.status_bantuan === 'Sudah Ditangani' ? 'selected' : ''}>Sudah Ditangani</option>
                    </select>`
                : `<div>
                    <select class="stat-sel ${esc(sc)}" disabled data-orig="${esc(p.status_bantuan || '')}">
                        <option selected>${esc(p.status_bantuan || 'Belum Ditangani')}</option>
                    </select>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Menunggu verifikasi admin</div>
                   </div>`;
            return `<tr>
                <td style="color:var(--text-muted);font-size:12px;">${start + i + 1}</td>
                <td style="font-weight:600;">${esc(p.nama_kk || '—')}</td>
                <td><span class="kat-badge ${esc(katCls[p.kategori] || '')}">${esc(p.kategori || '—')}</span></td>
                <td style="text-align:right;font-family:var(--font-mono);">${esc(String(p.jumlah_jiwa || 0))}</td>
                <td style="font-size:12px;">${ibadahText}</td>
                <td>
                    ${statusControl}
                </td>
                <td><span class="bs-badge ${p.is_blank_spot ? 'bs-blank' : 'bs-cover'}">${p.is_blank_spot ? 'Blank Spot' : 'Terjangkau'}</span></td>
                <td>
                    <span class="sv-badge ${svCls}">${svIcon}${sv}</span>
                    ${verifBtns}
                </td>
                <td style="text-align:center;display:flex;align-items:center;justify-content:center;gap:4px;">
                    <button class="tbl-action-btn" title="Edit Data" style="color:#d97706;" data-id="${attr(parseInt(p.id,10)||0)}" onclick="openEditPd(parseInt(this.dataset.id,10))"><i data-lucide="pencil"></i></button>
                    <button class="tbl-action-btn" title="Riwayat Bantuan" data-id="${attr(parseInt(p.id, 10) || 0)}" data-nama="${attr(p.nama_kk || '?')}" onclick="loadRiwayat(this.dataset.id, this.dataset.nama)"><i data-lucide="clipboard-list"></i></button>
                    ${isVerified ? `<button class="tbl-action-btn" title="Kelola Kebutuhan" style="color:#0d7490;" data-id="${attr(parseInt(p.id,10)||0)}" data-nama="${attr(p.nama_kk||'?')}" onclick="openKebPd(parseInt(this.dataset.id,10), this.dataset.nama)"><i data-lucide="package"></i></button>` : ''}
                    ${nonaktifBtn}
                </td>
            </tr>`;
        }).join('');
    }

    const end = Math.min(start + PD_PER, _pdFiltered.length);
    document.getElementById('pd-info').textContent = _pdFiltered.length > 0
        ? `Menampilkan ${start + 1}–${end} dari ${_pdFiltered.length}`
        : '';

    let btns = `<button class="page-btn" onclick="goPage(${_pdPage - 1})" ${_pdPage === 1 ? 'disabled' : ''}><i data-lucide="chevron-left"></i></button>`;
    for (let p = 1; p <= pages; p++) {
        btns += `<button class="page-btn ${p === _pdPage ? 'active' : ''}" onclick="goPage(${p})">${p}</button>`;
    }
    btns += `<button class="page-btn" onclick="goPage(${_pdPage + 1})" ${_pdPage === pages ? 'disabled' : ''}><i data-lucide="chevron-right"></i></button>`;
    document.getElementById('pd-pages').innerHTML = btns;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function goPage(p) {
    const pages = Math.ceil(_pdFiltered.length / PD_PER) || 1;
    if (p < 1 || p > pages) return;
    _pdPage = p;
    renderPage();
}

async function updateStatus(id, sel) {
    const newStatus = sel.value;
    const orig      = sel.dataset.orig;
    const item = _pdAll.find(p => String(p.id) === String(id));
    if (item && (item.status_verifikasi || 'Pending') !== 'Terverifikasi') {
        alert('Menunggu verifikasi admin');
        sel.value = orig;
        return;
    }
    const fd = new FormData();
    fd.append('id', id);
    fd.append('status', newStatus);
    try {
        const r = await fetch('../api/penduduk/update_status.php', { method: 'POST', body: appendCsrf(fd) });
        const j = await r.json();
        if (j.status === 'success') {
            sel.dataset.orig = newStatus;
            const statCls = { 'Belum Ditangani': 'belum', 'Dalam Proses': 'proses', 'Sudah Ditangani': 'sudah' };
            sel.className = 'stat-sel ' + (statCls[newStatus] || '');
            // Update local data so filter stays consistent
            if (item) item.status_bantuan = newStatus;
        } else {
            alert(j.message || 'Gagal update status.');
            sel.value = orig;
        }
    } catch(e) {
        alert('Gagal terhubung ke server.');
        sel.value = orig;
    }
}

async function loadRiwayat(id, nama) {
    document.getElementById('riwayatTitle').textContent = 'Riwayat Bantuan — ' + nama;
    document.getElementById('riwayatBody').innerHTML = '<span class="spinner"></span> Memuat...';
    document.getElementById('riwayatModal').classList.add('show');
    try {
        const r = await fetch('../api/penduduk/riwayat.php?id=' + id + '&_=' + Date.now());
        const j = await r.json();
        if (!j.data || !j.data.length) {
            document.getElementById('riwayatBody').innerHTML = '<p style="color:var(--text-muted);display:inline-flex;align-items:center;gap:6px;"><i data-lucide="info"></i> Belum ada riwayat perubahan status.</p>';
            if (typeof lucide !== 'undefined') lucide.createIcons();
            return;
        }
        document.getElementById('riwayatBody').innerHTML =
            '<table class="admin-table" style="font-size:12px;">' +
            '<thead><tr><th>Waktu</th><th>Oleh</th><th>Perubahan</th><th>Catatan</th></tr></thead>' +
            '<tbody>' + j.data.map(rv => `<tr>
                <td style="white-space:nowrap;color:var(--text-muted);">${esc(rv.created_at ? rv.created_at.slice(0, 16) : '—')}</td>
                <td>${esc(rv.user_nama || '—')}</td>
                <td style="white-space:nowrap;"><span style="color:var(--text-muted);">${esc(rv.status_lama || '—')}</span> → <strong>${esc(rv.status_baru || '—')}</strong></td>
                <td style="color:var(--text-muted);">${esc(rv.catatan || '—')}</td>
            </tr>`).join('') + '</tbody></table>';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch(e) {
        document.getElementById('riwayatBody').innerHTML = '<span style="color:#ef4444;display:inline-flex;align-items:center;gap:6px;"><i data-lucide="x-circle"></i> Gagal memuat riwayat.</span>';
        if (typeof lucide !== 'undefined') lucide.createIcons();
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

async function doNonaktifkan(id) {
    const ok = await showConfirm({
        title: 'Nonaktifkan Warga',
        message: 'Tandai warga ini sebagai tidak aktif (meninggal/pindah)? Data akan diarsipkan.',
        confirmLabel: 'Ya, Nonaktifkan', danger: true
    });
    if (!ok) return;
    const fd = new FormData();
    fd.append('id', id);
    try {
        const r = await fetch('../api/penduduk/nonaktifkan.php', { method: 'POST', body: appendCsrf(fd) });
        const j = await r.json();
        if (j.status === 'success') {
            loadPd();
        } else {
            alert(j.message || 'Gagal menonaktifkan warga.');
        }
    } catch(e) {
        alert('Gagal terhubung ke server.');
    }
}

// ── Verifikasi langsung dari tabel ────────────────────────────
async function doVerifRow(btn, aksi) {
    const id = btn.dataset.id;
    const item = _pdAll.find(p => String(p.id) === String(id));
    const label = aksi === 'approve' ? 'Terverifikasi' : 'Ditolak';
    const nama = item?.nama_kk ? `"${item.nama_kk}"` : `#${id}`;
    const ok = await showConfirm({
        title: aksi === 'approve' ? 'Konfirmasi Verifikasi' : 'Konfirmasi Tolak',
        message: `Ubah status verifikasi ${nama} menjadi <strong>${label}</strong>?`,
        confirmLabel: aksi === 'approve' ? 'Ya, Verifikasi' : 'Ya, Tolak',
        danger: aksi === 'reject'
    });
    if (!ok) return;

    btn.disabled = true;
    const fd = new FormData();
    fd.append('id', id); fd.append('aksi', aksi);
    try {
        const r = await fetch('../api/penduduk/verifikasi.php', { method:'POST', body: appendCsrf(fd) });
        const j = await r.json();
        if (j.status === 'success') {
            if (item) item.status_verifikasi = j.status_verifikasi;
            applyFilter();
        } else { alert(j.message || 'Gagal.'); btn.disabled = false; }
    } catch { alert('Gagal terhubung.'); btn.disabled = false; }
}

// ── Map Picker untuk form tambah ──────────────────────────────
const PD_DEF_LAT = <?= CENTER_LAT ?>;
const PD_DEF_LNG = <?= CENTER_LNG ?>;
const PD_DEF_ZOOM = <?= ZOOM_LEVEL ?>;
let _pdMap = null, _pdMarker = null, _pdGeoTimer = null;

function initPdMap() {
    if (_pdMap) { _pdMap.remove(); _pdMap = null; _pdMarker = null; }
    _pdMap = L.map('pdMapPicker').setView([PD_DEF_LAT, PD_DEF_LNG], PD_DEF_ZOOM);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom:19, attribution:'© OpenStreetMap'
    }).addTo(_pdMap);
    _pdMap.on('click', e => { pdPlaceMarker(e.latlng.lat, e.latlng.lng); pdFillCoords(e.latlng.lat, e.latlng.lng); pdGeocode(e.latlng.lat, e.latlng.lng); });
    setTimeout(() => _pdMap && _pdMap.invalidateSize(), 80);
}

function pdPlaceMarker(lat, lng) {
    if (_pdMarker) { _pdMarker.setLatLng([lat, lng]); }
    else {
        _pdMarker = L.marker([lat, lng], { draggable: true }).addTo(_pdMap);
        _pdMarker.on('dragend', e => {
            const ll = e.target.getLatLng();
            pdFillCoords(ll.lat, ll.lng);
            pdGeocode(ll.lat, ll.lng);
        });
    }
    _pdMap.panTo([lat, lng]);
}

function pdFillCoords(lat, lng) {
    document.getElementById('pdLat').value = lat.toFixed(8);
    document.getElementById('pdLng').value = lng.toFixed(8);
}

function pdCoordsManual() {
    const lat = parseFloat(document.getElementById('pdLat').value);
    const lng = parseFloat(document.getElementById('pdLng').value);
    if (!isNaN(lat) && !isNaN(lng) && _pdMap) { pdPlaceMarker(lat, lng); pdGeocode(lat, lng); }
}

async function pdGeocode(lat, lng) {
    clearTimeout(_pdGeoTimer);
    document.getElementById('pdGeoStatus').innerHTML = '<span class="spinner"></span> Mencari alamat...';
    _pdGeoTimer = setTimeout(async () => {
        try {
            const r = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`, { headers:{'Accept-Language':'id'} });
            const j = await r.json();
            const alamatEl = document.getElementById('pdAlamat');
            if (j && j.display_name && !alamatEl.dataset.userEdited) {
                alamatEl.value = j.display_name;
            }
            document.getElementById('pdGeoStatus').innerHTML = j.display_name
                ? '<i data-lucide="check" style="color:var(--text-success);margin-right:4px;"></i> ' + esc(j.address?.road || j.address?.suburb || j.display_name.split(',')[0])
                : '';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } catch { document.getElementById('pdGeoStatus').textContent = ''; }
    }, 600);
}

function openAddPd() {
    ['pdNama','pdNik','pdAlamat','pdCatatan','pdLat','pdLng'].forEach(id => {
        const el = document.getElementById(id);
        el.value = '';
        delete el.dataset.userEdited;
    });
    document.getElementById('pdJiwa').value     = 1;
    document.getElementById('pdKategori').value = 'Miskin';
    document.getElementById('pdMsg').textContent = '';
    document.getElementById('pdGeoStatus').textContent = '';
    document.getElementById('pdSaveBtn').disabled = false;
    document.getElementById('pdSaveBtn').textContent = 'Simpan';
    document.getElementById('pdModal').classList.add('show');
    initPdMap();
}

function closeAddPd() {
    document.getElementById('pdModal').classList.remove('show');
    clearTimeout(_pdGeoTimer);
    if (_pdMap) { _pdMap.remove(); _pdMap = null; _pdMarker = null; }
}

async function savePd() {
    const btn = document.getElementById('pdSaveBtn');
    const msg = document.getElementById('pdMsg');
    const lat = document.getElementById('pdLat').value.trim();
    const lng = document.getElementById('pdLng').value.trim();
    const nama = document.getElementById('pdNama').value.trim();

    if (!nama) { msg.textContent = 'Nama Kepala Keluarga wajib diisi.'; return; }
    if (!lat || !lng) { msg.textContent = 'Klik peta untuk menentukan lokasi.'; return; }

    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Menyimpan...'; msg.textContent = '';

    const fd = new FormData();
    fd.append('nama_kk',    nama);
    fd.append('nik',        document.getElementById('pdNik').value.trim());
    fd.append('jumlah_jiwa',document.getElementById('pdJiwa').value);
    fd.append('kategori',   document.getElementById('pdKategori').value);
    fd.append('alamat',     document.getElementById('pdAlamat').value.trim());
    fd.append('catatan',    document.getElementById('pdCatatan').value.trim());
    fd.append('lat', lat); fd.append('lng', lng);

    try {
        const r = await fetch('../api/penduduk/simpan.php', { method:'POST', body: appendCsrf(fd) });
        const j = await r.json();
        btn.disabled = false; btn.innerHTML = 'Simpan';
        if (j.status === 'success') {
            closeAddPd();
            loadPd();
        } else { msg.textContent = j.message || 'Terjadi kesalahan.'; }
    } catch { btn.disabled = false; btn.innerHTML = 'Simpan'; msg.textContent = 'Gagal terhubung ke server.'; }
}

// ── Edit data penduduk ────────────────────────────────────────
function openEditPd(id) {
    const p = _pdAll.find(x => String(x.id) === String(id));
    if (!p) return;
    document.getElementById('editPdId').value          = p.id;
    document.getElementById('editPdNama').value        = p.nama_kk || '';
    document.getElementById('editPdNik').value         = p.nik || '';
    document.getElementById('editPdJiwa').value        = p.jumlah_jiwa || 1;
    document.getElementById('editPdKategori').value    = p.kategori || 'Miskin';
    document.getElementById('editPdAlamat').value      = p.alamat || '';
    document.getElementById('editPdCatatan').value     = p.catatan || '';
    document.getElementById('editPdMsg').textContent   = '';
    document.getElementById('editPdSaveBtn').disabled  = false;
    document.getElementById('editPdSaveBtn').textContent = 'Simpan Perubahan';
    document.getElementById('editPdModal').classList.add('show');
}

function closeEditPd() {
    document.getElementById('editPdModal').classList.remove('show');
}

async function saveEditPd() {
    const btn  = document.getElementById('editPdSaveBtn');
    const msg  = document.getElementById('editPdMsg');
    const nama = document.getElementById('editPdNama').value.trim();
    if (!nama) { msg.textContent = 'Nama Kepala Keluarga wajib diisi.'; return; }

    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Menyimpan...'; msg.textContent = '';

    const fd = new FormData();
    fd.append('id',          document.getElementById('editPdId').value);
    fd.append('nama_kk',     nama);
    fd.append('nik',         document.getElementById('editPdNik').value.trim());
    fd.append('jumlah_jiwa', document.getElementById('editPdJiwa').value);
    fd.append('kategori',    document.getElementById('editPdKategori').value);
    fd.append('alamat',      document.getElementById('editPdAlamat').value.trim());
    fd.append('catatan',     document.getElementById('editPdCatatan').value.trim());

    try {
        const r = await fetch('../api/penduduk/update.php', { method: 'POST', body: appendCsrf(fd) });
        const j = await r.json();
        btn.disabled = false; btn.textContent = 'Simpan Perubahan';
        if (j.status === 'success') {
            closeEditPd();
            loadPd();
        } else {
            msg.textContent = j.message || 'Terjadi kesalahan.';
        }
    } catch {
        btn.disabled = false; btn.textContent = 'Simpan Perubahan';
        msg.textContent = 'Gagal terhubung ke server.';
    }
}

// ── Kelola Kebutuhan dari tabel ───────────────────────────────
const KEB_KAT_LIST = ['Sembako','Biaya Sekolah','Biaya Kesehatan','Modal Usaha','Renovasi Rumah','Perlengkapan Rumah','Pakaian','Lainnya'];

async function openKebPd(id, nama) {
    document.getElementById('kebPdTitle').textContent = 'Kebutuhan — ' + nama;
    document.getElementById('kebPdBody').innerHTML = '<span class="spinner"></span> Memuat...';
    document.getElementById('kebPdModal').classList.add('show');
    await renderKebPd(id);
}

async function renderKebPd(id) {
    const clsMap = {'Belum Terpenuhi':'belum','Dalam Proses':'proses','Terpenuhi':'terpenuhi'};
    try {
        const r = await fetch('../api/kebutuhan/ambil.php?penduduk_id='+id+'&_='+Date.now());
        const j = await r.json();

        const listHtml = (!j.data || !j.data.length)
            ? '<p style="color:var(--text-muted);margin-bottom:4px;">Belum ada kebutuhan tercatat.</p>'
            : j.data.map(k => `
                <div style="padding:10px 0;border-bottom:1px solid var(--card-border);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                        <span style="font-weight:600;">${esc(k.kategori)}</span>
                        <select class="stat-sel ${clsMap[k.status]||''}"
                            data-id="${attr(parseInt(k.id,10)||0)}" data-orig="${attr(k.status||'')}" onchange="updateKebFromPd(this)">
                            <option ${k.status==='Belum Terpenuhi'?'selected':''}>Belum Terpenuhi</option>
                            <option ${k.status==='Dalam Proses'?'selected':''}>Dalam Proses</option>
                            <option ${k.status==='Terpenuhi'?'selected':''}>Terpenuhi</option>
                        </select>
                    </div>
                    ${k.deskripsi?`<div style="font-size:12px;color:var(--text-muted);">${esc(k.deskripsi)}</div>`:''}
                    <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">Oleh ${esc(k.created_by_nama||'—')} · ${esc((k.created_at||'').slice(0,10))}</div>
                </div>`).join('');

        const addFormHtml = `
            <div style="margin-top:14px;padding-top:14px;border-top:2px dashed var(--card-border);">
                <div style="font-size:11px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Tambah Kebutuhan</div>
                <select id="kebPdKat_${id}" style="width:100%;padding:8px 10px;border:1px solid var(--card-border);border-radius:7px;font-family:var(--font);font-size:13px;background:#fafaf9;color:var(--text-primary);outline:none;margin-bottom:8px;">
                    ${KEB_KAT_LIST.map(k=>`<option value="${esc(k)}">${esc(k)}</option>`).join('')}
                </select>
                <input type="text" id="kebPdDesc_${id}" placeholder="Deskripsi (opsional, maks 300 karakter)" maxlength="300"
                    style="width:100%;padding:8px 10px;border:1px solid var(--card-border);border-radius:7px;font-family:var(--font);font-size:13px;background:#fafaf9;color:var(--text-primary);outline:none;margin-bottom:8px;box-sizing:border-box;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <button onclick="addKebFromPd(${id})" id="kebPdAddBtn_${id}" class="btn-primary" style="padding:7px 18px;font-size:13px;">Simpan</button>
                    <span id="kebPdAddMsg_${id}" style="font-size:12px;color:#ef4444;"></span>
                </div>
            </div>`;

        document.getElementById('kebPdBody').innerHTML = listHtml + addFormHtml;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch(e) {
        document.getElementById('kebPdBody').innerHTML = '<span style="color:#ef4444;">Gagal memuat kebutuhan.</span>';
    }
}

async function updateKebFromPd(sel) {
    const id   = sel.dataset.id;
    const orig = sel.dataset.orig;
    const catatan = prompt('Catatan perubahan kebutuhan (opsional):', '');
    if (catatan === null) { sel.value = orig; return; }
    const fd = new FormData();
    fd.append('id', id); fd.append('status', sel.value); fd.append('catatan', catatan);
    try {
        const r = await fetch('../api/kebutuhan/update_status.php', {method:'POST', body: appendCsrf(fd)});
        const j = await r.json();
        if (j.status === 'success') {
            sel.dataset.orig = sel.value;
            const clsMap = {'Belum Terpenuhi':'belum','Dalam Proses':'proses','Terpenuhi':'terpenuhi'};
            sel.className = 'stat-sel ' + (clsMap[sel.value]||'');
        } else { alert(j.message||'Gagal update.'); sel.value = orig; }
    } catch(e) { alert('Gagal terhubung ke server.'); sel.value = orig; }
}

async function addKebFromPd(id) {
    const kat  = document.getElementById(`kebPdKat_${id}`).value;
    const desc = document.getElementById(`kebPdDesc_${id}`).value.trim();
    const btn  = document.getElementById(`kebPdAddBtn_${id}`);
    const msg  = document.getElementById(`kebPdAddMsg_${id}`);
    btn.disabled = true; btn.textContent = 'Menyimpan...'; msg.textContent = '';
    const fd = new FormData();
    fd.append('penduduk_id', id); fd.append('kategori', kat); fd.append('deskripsi', desc);
    try {
        const r = await fetch('../api/kebutuhan/simpan.php', {method:'POST', body: appendCsrf(fd)});
        const j = await r.json();
        if (j.status === 'success') {
            await renderKebPd(id);
        } else {
            msg.textContent = j.message || 'Gagal menyimpan.';
            btn.disabled = false; btn.textContent = 'Simpan';
        }
    } catch(e) {
        msg.textContent = 'Gagal terhubung ke server.';
        btn.disabled = false; btn.textContent = 'Simpan';
    }
}

loadPd();
</script>

<!-- Modal Edit Penduduk -->
<div class="modal-dark" id="editPdModal">
    <div class="modal-box">
        <h3>Edit Data Penduduk</h3>
        <div class="modal-scroll">
            <input type="hidden" id="editPdId">
            <div class="mfg">
                <label>Nama Kepala Keluarga <span style="color:#ef4444;">*</span></label>
                <input type="text" id="editPdNama" placeholder="Nama lengkap KK">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div class="mfg">
                    <label>NIK <small style="text-transform:none;">(opsional)</small></label>
                    <input type="text" id="editPdNik" placeholder="16 digit" maxlength="16">
                </div>
                <div class="mfg">
                    <label>Jumlah Jiwa <small style="text-transform:none;">(orang)</small></label>
                    <input type="number" id="editPdJiwa" value="1" min="1" max="30">
                </div>
            </div>
            <div class="mfg">
                <label>Kategori Kemiskinan</label>
                <select id="editPdKategori">
                    <option value="Sangat Miskin">Sangat Miskin</option>
                    <option value="Miskin" selected>Miskin</option>
                    <option value="Hampir Miskin">Hampir Miskin</option>
                </select>
            </div>
            <div class="mfg">
                <label>Alamat</label>
                <input type="text" id="editPdAlamat" placeholder="Alamat lengkap">
            </div>
            <div class="mfg">
                <label>Catatan Kondisi <small style="text-transform:none;">(opsional)</small></label>
                <textarea id="editPdCatatan" placeholder="Kondisi rumah, kebutuhan khusus, dll..."></textarea>
            </div>
            <div class="msg-err" id="editPdMsg"></div>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeEditPd()">Batal</button>
            <button class="btn-primary" id="editPdSaveBtn" onclick="saveEditPd()">Simpan Perubahan</button>
        </div>
    </div>
</div>

<!-- Modal Kelola Kebutuhan -->
<div class="modal-dark" id="kebPdModal">
    <div class="modal-box">
        <h3 id="kebPdTitle">Kebutuhan</h3>
        <div class="modal-scroll" id="kebPdBody" style="font-size:13px;"></div>
        <div style="margin-top:14px;flex-shrink:0;display:flex;gap:8px;">
            <button style="padding:8px 16px;background:transparent;border:1px solid var(--card-border);border-radius:8px;font-family:var(--font);font-size:13px;font-weight:600;color:var(--text-secondary);cursor:pointer;" onclick="document.getElementById('kebPdModal').classList.remove('show')">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal Tambah Penduduk -->
<div class="modal-dark" id="pdModal">
    <div class="modal-box">
        <h3>Tambah Penduduk Miskin</h3>
        <div class="modal-scroll">

            <!-- Peta -->
            <div class="mfg">
                <label>Lokasi di Peta <span style="font-weight:400;color:var(--text-muted);text-transform:none;letter-spacing:0;">— klik untuk set titik</span></label>
                <div class="map-picker-wrap"><div id="pdMapPicker"></div></div>
                <div class="map-picker-hint" style="display:flex;align-items:center;gap:12px;margin-top:6px;">
                    <span style="display:inline-flex;align-items:center;gap:4px;"><i data-lucide="mouse-pointer"></i> Klik peta</span> · <span style="display:inline-flex;align-items:center;gap:4px;"><i data-lucide="map-pin"></i> Drag marker</span> · <span style="display:inline-flex;align-items:center;gap:4px;"><i data-lucide="zoom-in"></i> Scroll zoom</span>
                </div>
                <div class="map-geocoding" id="pdGeoStatus"></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div class="mfg">
                    <label>Latitude</label>
                    <input type="number" id="pdLat" step="any" placeholder="-0.0557" oninput="pdCoordsManual()">
                </div>
                <div class="mfg">
                    <label>Longitude</label>
                    <input type="number" id="pdLng" step="any" placeholder="109.3487" oninput="pdCoordsManual()">
                </div>
            </div>

            <!-- Data Identitas -->
            <div class="mfg">
                <label>Nama Kepala Keluarga <span style="color:#ef4444;">*</span></label>
                <input type="text" id="pdNama" placeholder="Nama lengkap KK">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div class="mfg">
                    <label>NIK <small style="text-transform:none;">(opsional)</small></label>
                    <input type="text" id="pdNik" placeholder="16 digit" maxlength="16">
                </div>
                <div class="mfg">
                    <label>Jumlah Jiwa</label>
                    <input type="number" id="pdJiwa" value="1" min="1" max="30">
                </div>
            </div>
            <div class="mfg">
                <label>Kategori Kemiskinan</label>
                <select id="pdKategori">
                    <option value="Sangat Miskin">Sangat Miskin</option>
                    <option value="Miskin" selected>Miskin</option>
                    <option value="Hampir Miskin">Hampir Miskin</option>
                </select>
            </div>
            <div class="mfg">
                <label>Alamat</label>
                <input type="text" id="pdAlamat" placeholder="Alamat lengkap"
                       oninput="this.dataset.userEdited='1'">
            </div>
            <div class="mfg">
                <label>Catatan Kondisi <small style="text-transform:none;">(opsional)</small></label>
                <textarea id="pdCatatan" placeholder="Kondisi rumah, kebutuhan khusus, dll..."></textarea>
            </div>

            <?php if ($is_admin): ?>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;font-size:12px;color:#16a34a;margin-bottom:4px;display:inline-flex;align-items:center;gap:6px;width:100%;box-sizing:border-box;">
                <i data-lucide="check-circle" style="color:#16a34a;flex-shrink:0;"></i> <span>Sebagai Admin, data yang disimpan langsung berstatus <strong>Terverifikasi</strong>.</span>
            </div>
            <?php else: ?>
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:12px;color:#d97706;margin-bottom:4px;display:inline-flex;align-items:center;gap:6px;width:100%;box-sizing:border-box;">
                <i data-lucide="clock" style="color:#d97706;flex-shrink:0;"></i> <span>Data akan berstatus <strong>Pending</strong> hingga diverifikasi oleh Administrator.</span>
            </div>
            <?php endif; ?>

            <div class="msg-err" id="pdMsg"></div>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeAddPd()">Batal</button>
            <button class="btn-primary" id="pdSaveBtn" onclick="savePd()">Simpan</button>
        </div>
    </div>
</div>

<?php include '../includes/page-end.php'; ?>
