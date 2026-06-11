<?php
require_once '../config.php';
require_once '../auth/helper.php';
require_once '../koneksi.php';
if (!is_logged_in() || !has_role('administrator')) { header('Location: ../auth/login.php'); exit; }
require_password_changed('../auth/change_password.php');
$page_title    = 'Rumah Ibadah';
$page_subtitle = 'Kelola data rumah ibadah dan radius pelayanan';
$active_nav    = 'ibadah';
include '../includes/page-start.php';
?>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<style>
.ib-filter-bar { display:flex; flex-direction:column; gap:8px; margin-bottom:16px; }
.ib-search { width:100%; box-sizing:border-box; padding:8px 12px; border:1px solid var(--card-border); border-radius:8px; font-family:var(--font); font-size:13px; background:#fafaf9; color:var(--text-primary); outline:none; }
.ib-search:focus { border-color:#0d7490; }
.ib-filter-chips { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.ib-chip { width:auto; height:auto; padding:7px 11px; border:1px solid var(--card-border); border-radius:8px; font-family:var(--font); font-size:13px; background:#fafaf9; color:var(--text-primary); cursor:pointer; white-space:nowrap; }
.ib-chip:focus { outline:none; border-color:#0d7490; }
.modal-dark { position:fixed; inset:0; background:rgba(32,21,21,.55); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; padding:20px; z-index:9999; opacity:0; pointer-events:none; transition:opacity .2s; }
.modal-dark.show { opacity:1; pointer-events:auto; }
.modal-box { width:min(600px,100%); background:#fafaf9; border:1px solid var(--card-border); border-radius:12px; padding:28px; box-shadow:0 4px 16px rgba(32,21,21,.1); max-height:90vh; overflow-y:auto; }
.modal-box h3 { font-size:16px; font-weight:600; margin-bottom:20px; color:var(--text-primary); }
.mfg { margin-bottom:14px; }
.mfg label { display:block; font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; margin-bottom:5px; }
.mfg input, .mfg select { width:100%; padding:9px 12px; border:1px solid var(--card-border); border-radius:8px; font-family:var(--font); font-size:13px; color:var(--text-primary); background:#fafaf9; outline:none; transition:border-color .2s; }
.mfg input:focus, .mfg select:focus { border-color:#0d7490; }
.mfg small { font-size:11px; color:var(--text-muted); margin-top:4px; display:block; }
.modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; }
.btn-cancel { padding:9px 16px; background:transparent; border:1px solid var(--card-border); border-radius:8px; font-family:var(--font); font-size:13px; font-weight:600; color:var(--text-secondary); cursor:pointer; transition:background .15s; }
.btn-cancel:hover { background:#ede8e2; }
.btn-submit { padding:9px 18px; background:#0d7490; border:none; border-radius:8px; font-family:var(--font); font-size:14px; font-weight:600; color:#fff; cursor:pointer; transition:background .15s; }
.btn-submit:hover { background:#0a5f7a; }
.btn-submit:disabled { opacity:.5; cursor:not-allowed; }
.btn-danger-submit { padding:9px 18px; background:#ef4444; border:none; border-radius:8px; font-family:var(--font); font-size:13px; font-weight:600; color:#fff; cursor:pointer; transition:background .15s; }
.btn-danger-submit:hover { background:#dc2626; }
.msg-err { font-size:12px; color:#ef4444; margin-top:8px; min-height:16px; }

/* ── Map picker ── */
.map-picker-wrap { border:1px solid var(--card-border); border-radius:10px; overflow:hidden; position:relative; }
.map-picker-wrap #ibMapPicker { height:260px; cursor:crosshair; }
.map-picker-hint { display:flex; align-items:center; gap:6px; font-size:11px; color:var(--text-muted); margin-top:6px; }
.map-geocoding { font-size:11px; color:var(--text-muted); margin-top:5px; min-height:16px; font-style:italic; }
/* Override Leaflet z-index agar tidak keluar modal */
#ibMapPicker .leaflet-control-zoom { display:block; }
</style>

<!-- Toolbar -->
<div class="ib-filter-bar">
    <input type="text" class="ib-search" id="ibSearch" placeholder="Cari nama rumah ibadah..." oninput="filterTable()">
    <div class="ib-filter-chips">
        <select class="ib-chip" id="ibFilterJenis" onchange="filterTable()">
            <option value="">Semua Jenis</option>
            <option>Masjid</option><option>Mushola</option><option>Gereja</option>
            <option>Pura</option><option>Vihara</option><option>Klenteng</option>
        </select>
        <div style="width:1px;height:20px;background:var(--card-border);margin:0 2px;align-self:center;flex-shrink:0;"></div>
        <select class="ib-chip" id="ibSort" onchange="filterTable()">
            <option value="">Urutkan</option>
            <option value="nama_asc">Nama A→Z</option>
            <option value="nama_desc">Nama Z→A</option>
            <option value="radius_desc">Radius Terbesar</option>
            <option value="radius_asc">Radius Terkecil</option>
            <option value="kk_desc">KK Binaan Terbanyak</option>
            <option value="kk_asc">KK Binaan Tersedikit</option>
            <option value="jiwa_desc">Jiwa Terbanyak</option>
            <option value="jiwa_asc">Jiwa Tersedikit</option>
        </select>
        <div style="flex:1;"></div>
        <a href="map.php" class="btn-see-all" style="text-decoration:none;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:4px;white-space:nowrap;"><i data-lucide="map"></i> Lihat di Peta</a>
        <button class="btn-submit" onclick="openAdd()" style="white-space:nowrap;">+ Tambah Ibadah</button>
    </div>
</div>

<!-- Table -->
<div class="table-card">
    <div class="table-card-header">
        <div>
            <div class="table-card-title">Daftar Rumah Ibadah</div>
            <div class="table-card-count" id="ibCount"></div>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th>Nama</th>
                    <th>Jenis</th>
                    <th style="text-align:right;">Radius (m)</th>
                    <th style="text-align:right;">KK Binaan</th>
                    <th style="text-align:right;">Jiwa</th>
                    <th>Kontak</th>
                    <th style="width:100px;text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody id="ibTbody">
                <tr><td colspan="8" style="text-align:center;padding:28px;color:var(--text-muted);"><span class="spinner"></span> Memuat...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add/Edit -->
<div class="modal-dark" id="ibModal">
    <div class="modal-box">
        <h3 id="ibModalTitle">Tambah Rumah Ibadah</h3>
        <input type="hidden" id="ibEditId">
        <div class="mfg">
            <label>Nama Rumah Ibadah</label>
            <input type="text" id="ibNama" placeholder="Masjid Al-Ikhlas">
        </div>
        <div class="mfg">
            <label>Jenis</label>
            <select id="ibJenis">
                <option>Masjid</option><option>Mushola</option><option>Gereja</option>
                <option>Pura</option><option>Vihara</option><option>Klenteng</option>
            </select>
        </div>
        <div class="mfg">
            <label>Alamat</label>
            <input type="text" id="ibAlamat" placeholder="Jl. Contoh No. 1">
        </div>
        <div class="mfg">
            <label>Kontak Pengurus</label>
            <input type="text" id="ibKontak" placeholder="08xx-xxxx-xxxx">
        </div>
        <!-- Map Picker -->
        <div class="mfg">
            <label>Lokasi di Peta <span style="font-weight:400;color:var(--text-muted);text-transform:none;letter-spacing:0;">— klik untuk set titik, drag marker untuk geser</span></label>
            <div class="map-picker-wrap">
                <div id="ibMapPicker"></div>
            </div>
            <div class="map-picker-hint" style="display:flex;align-items:center;gap:12px;">
                <span style="display:inline-flex;align-items:center;gap:4px;"><i data-lucide="mouse-pointer"></i> Klik peta</span>
                <span style="color:var(--card-border);">·</span>
                <span style="display:inline-flex;align-items:center;gap:4px;"><i data-lucide="map-pin"></i> Drag marker</span>
                <span style="color:var(--card-border);">·</span>
                <span style="display:inline-flex;align-items:center;gap:4px;"><i data-lucide="zoom-in"></i> Scroll untuk zoom</span>
            </div>
            <div class="map-geocoding" id="ibGeocodingStatus"></div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="mfg">
                <label>Latitude</label>
                <input type="number" id="ibLat" step="any" placeholder="-0.0557"
                       oninput="onCoordsManualChange()">
            </div>
            <div class="mfg">
                <label>Longitude</label>
                <input type="number" id="ibLng" step="any" placeholder="109.3487"
                       oninput="onCoordsManualChange()">
            </div>
        </div>
        <div class="mfg">
            <label>Radius Pelayanan</label>
            <div style="display:flex;align-items:center;gap:12px;">
                <input type="range" id="ibRadius" value="500" min="100" max="5000" step="50"
                       oninput="onRadiusChange(this.value)"
                       style="flex:1;height:4px;accent-color:#111111;cursor:pointer;">
                <span id="ibRadiusVal"
                      style="min-width:56px;text-align:right;font-family:var(--font);
                             font-size:14px;font-weight:600;color:#374151;">500m</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--text-muted);margin-top:3px;">
                <span>100m</span><span>500m</span><span>1km</span><span>2.5km</span><span>5km</span>
            </div>
        </div>
        <div class="msg-err" id="ibMsg"></div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeIbModal()">Batal</button>
            <button class="btn-submit" id="ibSaveBtn" onclick="saveIbadah()">Simpan</button>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus (2-step) -->
<div class="modal-dark" id="ibHapusModal">
    <div class="modal-box" style="max-width:440px;">
        <h3 id="ibHapusTitle">Konfirmasi Hapus</h3>
        <div id="ibHapusWarning" style="font-size:13px;color:var(--text-secondary);line-height:1.6;margin-bottom:16px;"></div>
        <input type="hidden" id="ibHapusId">
        <input type="hidden" id="ibHapusConfirmed" value="0">
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeHapusModal()">Batal</button>
            <button class="btn-danger-submit" id="ibHapusBtn" onclick="doHapus()">Ya, Hapus</button>
        </div>
    </div>
</div>

<script>
let _ibAll = [];

function esc(s) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(s ?? ''));
    return d.innerHTML;
}
function attr(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

async function loadIbadah() {
    try {
        const r = await fetch('../api/ibadah/ambil.php?_=' + Date.now());
        const j = await r.json();
        if (j.status !== 'success') return;
        _ibAll = j.data;
        document.getElementById('ibCount').textContent = j.total + ' rumah ibadah';
        filterTable();
    } catch(e) {
        document.getElementById('ibTbody').innerHTML =
            '<tr><td colspan="8" style="text-align:center;padding:24px;color:#ef4444;">Gagal memuat data.</td></tr>';
    }
}

function filterTable() {
    const q     = document.getElementById('ibSearch').value.toLowerCase();
    const jenis = document.getElementById('ibFilterJenis').value;
    const sort  = document.getElementById('ibSort').value;
    const rows  = _ibAll.filter(r =>
        (!q     || r.nama.toLowerCase().includes(q)) &&
        (!jenis || r.jenis === jenis)
    );
    if (sort) {
        rows.sort((a, b) => {
            switch (sort) {
                case 'nama_asc':    return (a.nama||'').localeCompare(b.nama||'', 'id');
                case 'nama_desc':   return (b.nama||'').localeCompare(a.nama||'', 'id');
                case 'radius_desc': return (b.radius_m||0) - (a.radius_m||0);
                case 'radius_asc':  return (a.radius_m||0) - (b.radius_m||0);
                case 'kk_desc':     return (b.total_kk||0) - (a.total_kk||0);
                case 'kk_asc':      return (a.total_kk||0) - (b.total_kk||0);
                case 'jiwa_desc':   return (b.total_jiwa||0) - (a.total_jiwa||0);
                case 'jiwa_asc':    return (a.total_jiwa||0) - (b.total_jiwa||0);
                default: return 0;
            }
        });
    }
    const jenisMap = { Masjid:'masjid', Mushola:'mushola', Gereja:'gereja', Pura:'pura', Vihara:'vihara', Klenteng:'klenteng' };
    const tbody = document.getElementById('ibTbody');
    if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--text-muted);">Tidak ada data.</td></tr>';
        return;
    }
    tbody.innerHTML = rows.map((r, i) => `<tr>
        <td style="color:var(--text-muted);font-size:12px;">${i + 1}</td>
        <td style="font-weight:600;">${esc(r.nama)}</td>
        <td><span class="jenis-badge ${jenisMap[r.jenis] || ''}">${esc(r.jenis)}</span></td>
        <td style="text-align:right;font-family:var(--font-mono);">${esc(String(r.radius_m))}</td>
        <td style="text-align:right;font-family:var(--font-mono);">${esc(String(r.total_kk || 0))}</td>
        <td style="text-align:right;font-family:var(--font-mono);">${esc(String(r.total_jiwa || 0))}</td>
        <td style="color:var(--text-muted);font-size:12px;">${esc(r.kontak || '—')}</td>
        <td style="text-align:center;display:flex;align-items:center;justify-content:center;gap:4px;">
            <button class="tbl-action-btn" title="Edit" data-id="${attr(parseInt(r.id, 10) || 0)}" onclick="openEditById(this.dataset.id)"><i data-lucide="pencil"></i></button>
            <button class="tbl-action-btn" title="Hapus" style="color:#ef4444;" data-id="${attr(parseInt(r.id, 10) || 0)}" onclick="openHapus(this.dataset.id)"><i data-lucide="trash-2"></i></button>
        </td>
    </tr>`).join('');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function openAdd() {
    document.getElementById('ibEditId').value = '';
    document.getElementById('ibModalTitle').textContent = 'Tambah Rumah Ibadah';
    ['ibNama', 'ibAlamat', 'ibKontak', 'ibLat', 'ibLng'].forEach(id => {
        const el = document.getElementById(id);
        el.value = '';
        delete el.dataset.userEdited;
    });
    document.getElementById('ibRadius').value = 500;
    document.getElementById('ibRadiusVal').textContent = '500m';
    document.getElementById('ibJenis').value  = 'Masjid';
    document.getElementById('ibMsg').textContent = '';
    document.getElementById('ibGeocodingStatus').textContent = '';
    document.getElementById('ibSaveBtn').textContent = 'Simpan';
    document.getElementById('ibModal').classList.add('show');
    // Init map tanpa koordinat awal — center ke default wilayah
    initMapPicker(null, null);
}

function openEdit(r) {
    document.getElementById('ibEditId').value  = r.id;
    document.getElementById('ibModalTitle').textContent = 'Edit Rumah Ibadah';
    document.getElementById('ibNama').value    = r.nama;
    document.getElementById('ibJenis').value   = r.jenis;
    document.getElementById('ibKontak').value  = r.kontak  || '';
    document.getElementById('ibLat').value     = r.lat;
    document.getElementById('ibLng').value     = r.lng;
    const rv = parseInt(r.radius_m) || 500;
    document.getElementById('ibRadius').value  = rv;
    document.getElementById('ibRadiusVal').textContent = rv >= 1000
        ? (rv / 1000).toFixed(rv % 1000 === 0 ? 0 : 1) + 'km' : rv + 'm';
    document.getElementById('ibMsg').textContent = '';
    document.getElementById('ibGeocodingStatus').textContent = '';
    document.getElementById('ibSaveBtn').textContent = 'Simpan';

    // Set alamat dan tandai sudah diedit (jangan di-overwrite geocoding)
    const alamatEl = document.getElementById('ibAlamat');
    alamatEl.value = r.alamat || '';
    alamatEl.dataset.userEdited = r.alamat ? '1' : '';

    document.getElementById('ibModal').classList.add('show');
    // Init map pada koordinat yang sudah ada
    initMapPicker(parseFloat(r.lat), parseFloat(r.lng));
}

function openEditById(id) {
    const row = _ibAll.find(r => String(r.id) === String(id));
    if (row) openEdit(row);
}

function closeIbModal() {
    document.getElementById('ibModal').classList.remove('show');
    clearTimeout(_geocodeTimer);
    if (_ibMap) { _ibMap.remove(); _ibMap = null; _ibMarker = null; _ibCircle = null; }
}

async function saveIbadah() {
    const id  = document.getElementById('ibEditId').value;
    const btn = document.getElementById('ibSaveBtn');
    const msg = document.getElementById('ibMsg');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Menyimpan...';
    msg.textContent = '';

    const namaVal = document.getElementById('ibNama').value.trim();
    const latVal  = document.getElementById('ibLat').value.trim();
    const lngVal  = document.getElementById('ibLng').value.trim();
    if (!namaVal) {
        msg.textContent = 'Nama wajib diisi.';
        btn.disabled = false; btn.textContent = 'Simpan'; return;
    }
    if (!latVal || !lngVal) {
        msg.textContent = 'Koordinat latitude dan longitude wajib diisi.';
        btn.disabled = false; btn.textContent = 'Simpan'; return;
    }

    const fd = new FormData();
    if (id) fd.append('id', id);
    fd.append('nama',     document.getElementById('ibNama').value.trim());
    fd.append('jenis',    document.getElementById('ibJenis').value);
    fd.append('alamat',   document.getElementById('ibAlamat').value.trim());
    fd.append('kontak',   document.getElementById('ibKontak').value.trim());
    fd.append('lat',      document.getElementById('ibLat').value);
    fd.append('lng',      document.getElementById('ibLng').value);
    fd.append('radius_m', document.getElementById('ibRadius').value);

    const url = id ? '../api/ibadah/update.php' : '../api/ibadah/simpan.php';
    try {
        const r = await fetch(url, { method: 'POST', body: appendCsrf(fd) });
        const j = await r.json();
        btn.disabled = false;
        btn.textContent = 'Simpan';
        if (j.status === 'success') { closeIbModal(); loadIbadah(); }
        else { msg.textContent = j.message || 'Terjadi kesalahan.'; }
    } catch(e) {
        btn.disabled = false;
        btn.textContent = 'Simpan';
        msg.textContent = 'Gagal terhubung ke server.';
    }
}

function openHapus(id) {
    document.getElementById('ibHapusId').value        = id;
    document.getElementById('ibHapusConfirmed').value = '0';
    document.getElementById('ibHapusTitle').textContent = 'Konfirmasi Hapus';
    document.getElementById('ibHapusWarning').textContent =
        'Yakin ingin menghapus rumah ibadah ini? Semua data proximity akan dihitung ulang.';
    document.getElementById('ibHapusBtn').textContent = 'Ya, Hapus';
    document.getElementById('ibHapusModal').classList.add('show');
}

function closeHapusModal() {
    document.getElementById('ibHapusModal').classList.remove('show');
}

async function doHapus() {
    const id        = document.getElementById('ibHapusId').value;
    const confirmed = document.getElementById('ibHapusConfirmed').value;
    const btn       = document.getElementById('ibHapusBtn');
    btn.disabled    = true;
    btn.innerHTML   = '<span class="spinner"></span> Menghapus...';

    const fd = new FormData();
    fd.append('id', id);
    if (confirmed === '1') fd.append('confirmed', '1');

    try {
        const r = await fetch('../api/ibadah/hapus.php', { method: 'POST', body: appendCsrf(fd) });
        const j = await r.json();
        btn.disabled = false;

        if (j.status === 'warning') {
            const opNames = (j.operators || []).map(o => esc(o.nama_lengkap)).join(', ');
            document.getElementById('ibHapusTitle').innerHTML = '<i data-lucide="alert-triangle" style="color:#f59e0b;vertical-align:middle;"></i> Peringatan';
            document.getElementById('ibHapusWarning').innerHTML =
                `Rumah ibadah ini terhubung dengan Operator: <strong>${opNames}</strong>.<br>
                Operator tidak bisa login sampai dikaitkan ke ibadah lain.<br><br>Tetap hapus?`;
            document.getElementById('ibHapusConfirmed').value = '1';
            document.getElementById('ibHapusBtn').textContent = 'Ya, Tetap Hapus';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } else if (j.status === 'success') {
            closeHapusModal();
            loadIbadah();
        } else {
            document.getElementById('ibHapusWarning').innerHTML =
                `<span style="color:#ef4444;">${esc(j.message || 'Terjadi kesalahan.')}</span>`;
            btn.textContent = 'Ya, Hapus';
        }
    } catch(e) {
        btn.disabled = false;
        btn.textContent = 'Ya, Hapus';
        document.getElementById('ibHapusWarning').innerHTML =
            '<span style="color:#ef4444;">Gagal terhubung ke server.</span>';
    }
}

// ── Map Picker ──────────────────────────────────────────────
const DEFAULT_LAT = <?= CENTER_LAT ?>;
const DEFAULT_LNG = <?= CENTER_LNG ?>;
const DEFAULT_ZOOM = <?= ZOOM_LEVEL ?>;

let _ibMap    = null;
let _ibMarker = null;
let _ibCircle = null;
let _geocodeTimer = null;

function initMapPicker(lat, lng) {
    const centerLat = lat || DEFAULT_LAT;
    const centerLng = lng || DEFAULT_LNG;
    const zoom      = lat ? 17 : DEFAULT_ZOOM;

    if (_ibMap) {
        _ibMap.remove();
        _ibMap = null;
        _ibMarker = null;
    }

    _ibMap = L.map('ibMapPicker', { zoomControl: true }).setView([centerLat, centerLng], zoom);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(_ibMap);

    // Marker awal jika ada koordinat
    if (lat && lng) {
        placeMarker(lat, lng);
    }

    // Klik peta → set lokasi
    _ibMap.on('click', function (e) {
        placeMarker(e.latlng.lat, e.latlng.lng);
        fillCoords(e.latlng.lat, e.latlng.lng);
        scheduleGeocode(e.latlng.lat, e.latlng.lng);
    });

    // Fix ukuran setelah modal terlihat
    setTimeout(() => _ibMap && _ibMap.invalidateSize(), 80);
}

function placeMarker(lat, lng) {
    if (_ibMarker) {
        _ibMarker.setLatLng([lat, lng]);
    } else {
        _ibMarker = L.marker([lat, lng], { draggable: true }).addTo(_ibMap);
        _ibMarker.on('dragend', function (e) {
            const ll = e.target.getLatLng();
            fillCoords(ll.lat, ll.lng);
            updateCircle(ll.lat, ll.lng);
            scheduleGeocode(ll.lat, ll.lng);
        });
    }
    updateCircle(lat, lng);
    _ibMap.panTo([lat, lng]);
}

function updateCircle(lat, lng) {
    const r = parseInt(document.getElementById('ibRadius').value) || 500;
    if (_ibCircle) {
        _ibCircle.setLatLng([lat, lng]).setRadius(r);
    } else if (_ibMap) {
        _ibCircle = L.circle([lat, lng], {
            radius: r, color: '#111111', weight: 1.5,
            fillColor: '#111111', fillOpacity: 0.05, opacity: 0.6
        }).addTo(_ibMap);
    }
}

function onRadiusChange(val) {
    document.getElementById('ibRadiusVal').textContent = val >= 1000
        ? (val / 1000).toFixed(val % 1000 === 0 ? 0 : 1) + 'km'
        : val + 'm';
    if (_ibCircle) _ibCircle.setRadius(parseInt(val));
}

function fillCoords(lat, lng) {
    document.getElementById('ibLat').value = lat.toFixed(8);
    document.getElementById('ibLng').value = lng.toFixed(8);
}

// Ketik manual di field lat/lng → pindahkan marker
function onCoordsManualChange() {
    const lat = parseFloat(document.getElementById('ibLat').value);
    const lng = parseFloat(document.getElementById('ibLng').value);
    if (!isNaN(lat) && !isNaN(lng) && _ibMap) {
        placeMarker(lat, lng);
        scheduleGeocode(lat, lng);
    }
}

// Debounce reverse geocoding (delay 600ms setelah berhenti drag/klik)
function scheduleGeocode(lat, lng) {
    clearTimeout(_geocodeTimer);
    const status = document.getElementById('ibGeocodingStatus');
    status.innerHTML = '<span class="spinner"></span> Mencari alamat...';
    _geocodeTimer = setTimeout(() => reverseGeocode(lat, lng), 600);
}

async function reverseGeocode(lat, lng) {
    const status  = document.getElementById('ibGeocodingStatus');
    const alamatEl = document.getElementById('ibAlamat');
    try {
        const r = await fetch(
            `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`,
            { headers: { 'Accept-Language': 'id' } }
        );
        const j = await r.json();
        if (j && j.display_name) {
            // Hanya isi alamat jika masih kosong atau user belum mengetik manual
            if (!alamatEl.dataset.userEdited) {
                alamatEl.value = j.display_name;
            }
            status.innerHTML = '<i data-lucide="check" style="color:var(--text-success);margin-right:4px;"></i> ' + (j.address?.road || j.address?.suburb || j.display_name.split(',')[0]);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } else {
            status.textContent = '';
        }
    } catch {
        status.textContent = '';
    }
}

// Tandai alamat sudah diedit manual → jangan di-overwrite geocoding
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('ibAlamat').addEventListener('input', function () {
        this.dataset.userEdited = '1';
    });
});

loadIbadah();
</script>

<?php include '../includes/page-end.php'; ?>
