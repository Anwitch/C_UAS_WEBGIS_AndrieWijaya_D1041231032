// modules/ibadah.js — Manajemen Rumah Ibadah dengan Radius Buffer & Reverse Geocoding
(function () {
    const layerGroup = L.layerGroup();
    window._ibadahLayer = layerGroup;

    // Data list: [{id, lat, lng, nama, jenis, radius}] — dibaca oleh penduduk.js
    window._ibadahList = [];

    let allMarkers = {}; // { id: { marker, circle } }
    let tempMarker = null;
    let _previewCircle = null;
    let active = false;
    let bufferVisible = true;

    // ── Icon & Color Factory ──────────────────────────────────────────
    const JENIS_ICON = {
        'Masjid'   : '🕌',
        'Mushola'  : '🕌',
        'Gereja'   : '⛪',
        'Pura'     : '🛕',
        'Vihara'   : '🏯',
        'Klenteng' : '🏮',
        'Lainnya'  : '🕍',
    };

    // Warna border circle per jenis (sesuai PRD F2)
    const JENIS_COLOR = {
        'Masjid'   : { stroke: '#38bdf8', fill: '#0ea5e9' }, // biru
        'Mushola'  : { stroke: '#67e8f9', fill: '#06b6d4' }, // cyan
        'Gereja'   : { stroke: '#4ade80', fill: '#16a34a' }, // hijau
        'Pura'     : { stroke: '#f97316', fill: '#ea580c' }, // oranye
        'Vihara'   : { stroke: '#facc15', fill: '#ca8a04' }, // kuning
        'Klenteng' : { stroke: '#f43f5e', fill: '#be123c' }, // merah
        'Lainnya'  : { stroke: '#a78bfa', fill: '#7c3aed' }, // ungu
    };

    const OPERATOR_HIGHLIGHT = {
        stroke: '#f59e0b',
        fill: '#fbbf24',
        ring: 'rgba(245,158,11,0.22)',
        shadow: 'rgba(180,83,9,0.35)'
    };

    function isOwnOperatorIbadah(id) {
        const appUser = window.APP_USER || {};
        if (!appUser.isOp || appUser.ibadahId === null || appUser.ibadahId === undefined) return false;
        return parseInt(appUser.ibadahId) === parseInt(id);
    }

    // ── KK list helper (called on popupopen & after setPopupContent) ──
    function _refreshKkList(id) {
        const bodyEl  = document.getElementById('kkListBody_' + id);
        const countEl = document.getElementById('kkCount_' + id);
        if (!bodyEl) return;

        const list = (window._pendudukAll || window._pendudukList || []).filter(p => p.ibadah && parseInt(p.ibadah.id) === parseInt(id));

        // Update live count (overrides stale DB value from initial load)
        if (countEl) {
            const totalJiwa = list.reduce((s, p) => s + (parseInt(p.jumlah_jiwa) || 1), 0);
            countEl.innerHTML = `<span style="color:#38bdf8;font-weight:700;">${list.length}</span> KK &nbsp;·&nbsp; <span style="color:#38bdf8;font-weight:700;">${totalJiwa}</span> jiwa`;
        }

        if (list.length === 0) {
            bodyEl.innerHTML = '<span style="color:var(--c-muted);font-size:12px;">Belum ada warga binaan.</span>';
            return;
        }
        const MAX = 8;
        let html = list.slice(0, MAX).map(p => `
            <div onclick="window._pendudukFlyTo(${p.id})" style="
                padding:5px 8px;margin:2px 0;border-radius:6px;cursor:pointer;
                background:rgba(255,255,255,.04);display:flex;justify-content:space-between;
                align-items:center;border:1px solid rgba(255,255,255,.07);transition:background .15s;
            "
            onmouseover="this.style.background='rgba(255,255,255,.1)'"
            onmouseout="this.style.background='rgba(255,255,255,.04)'">
                <span style="font-size:12px;">${escapeHTML(p.nama_kk || '(Anonim)')}</span>
                <span style="font-size:10px;color:var(--c-muted);white-space:nowrap;margin-left:6px;">${p.jumlah_jiwa || 1} jiwa</span>
            </div>`).join('');
        if (list.length > MAX) {
            html += `<div style="font-size:11px;color:var(--c-muted);text-align:center;margin-top:4px;">+${list.length - MAX} lainnya</div>`;
        }
        bodyEl.innerHTML = html;
    }

    function createIbadahIcon(jenis, ownOperatorIbadah = false) {
        const emoji = JENIS_ICON[jenis] || '🕍';
        const size = ownOperatorIbadah ? 46 : 36;
        const border = ownOperatorIbadah ? `4px solid ${OPERATOR_HIGHLIGHT.stroke}` : '2px solid rgba(56,189,248,0.8)';
        const background = ownOperatorIbadah ? 'linear-gradient(135deg, #fffbeb, #fef3c7)' : 'linear-gradient(135deg, #ffffff, #f0f9ff)';
        const shadow = ownOperatorIbadah
            ? `0 0 0 8px ${OPERATOR_HIGHLIGHT.ring}, 0 8px 22px ${OPERATOR_HIGHLIGHT.shadow}`
            : '0 3px 12px rgba(14,165,233,0.45)';
        const fontSize = ownOperatorIbadah ? 18 : 16;
        return L.divIcon({
            className: '',
            html: `<div style="
                width:${size}px; height:${size}px;
                background: ${background};
                border: ${border};
                border-radius: 50% 50% 50% 0;
                transform: rotate(-45deg);
                box-shadow: ${shadow};
                display:flex; align-items:center; justify-content:center;
            ">
                <span style="transform:rotate(45deg); font-size:${fontSize}px; line-height:1;">${emoji}</span>
            </div>`,
            iconSize: [size, size],
            iconAnchor: ownOperatorIbadah ? [12, 46] : [8, 36],
            popupAnchor: ownOperatorIbadah ? [10, -48] : [10, -38]
        });
    }

    const iconTemp = L.divIcon({
        className: '',
        html: `<div style="
            width:32px; height:32px;
            background: rgba(56,189,248,0.15);
            border: 2px dashed #38bdf8;
            border-radius: 50%;
            animation: pulse-ibadah 1s ease-in-out infinite;
        "></div>`,
        iconSize: [32, 32],
        iconAnchor: [16, 16],
    });

    // ── Tambah ke peta ────────────────────────────────────────────────
    function addToMap(data) {
        const lat = parseFloat(data.lat);
        const lng = parseFloat(data.lng);
        const radius = parseInt(data.radius_m) || 500;
        const jenis = data.jenis || 'Lainnya';
        const emoji = JENIS_ICON[jenis] || '🕍';
        const ownOperatorIbadah = isOwnOperatorIbadah(data.id);

        // Lingkaran radius — warna berbeda per jenis
        const clr = JENIS_COLOR[jenis] || JENIS_COLOR['Lainnya'];
        const circle = L.circle([lat, lng], {
            radius: radius,
            color: ownOperatorIbadah ? OPERATOR_HIGHLIGHT.stroke : clr.stroke,
            weight: ownOperatorIbadah ? 3 : 1.5,
            opacity: ownOperatorIbadah ? 1 : 0.8,
            fillColor: ownOperatorIbadah ? OPERATOR_HIGHLIGHT.fill : clr.fill,
            fillOpacity: ownOperatorIbadah ? 0.12 : 0.07,
        });

        const isAdmin = window.APP_USER && window.APP_USER.isAdmin;
        const marker = L.marker([lat, lng], {
            icon: createIbadahIcon(jenis, ownOperatorIbadah),
            draggable: isAdmin,
            zIndexOffset: ownOperatorIbadah ? 1000 : 100
        });

        marker._ibadahId = data.id;
        marker._ibadahNama = data.nama;
        marker._ibadahJenis = jenis;
        marker._ibadahRadius = radius;
        marker._circle = circle;

        // Drag: update posisi + recalculate penduduk
        marker.on('dragend', function (e) {
            const { lat: newLat, lng: newLng } = e.target.getLatLng();
            circle.setLatLng([newLat, newLng]);

            // Update _ibadahList (parseInt to guard against string/number type mismatch)
            const entry = window._ibadahList.find(x => parseInt(x.id) === parseInt(data.id));
            if (entry) { entry.lat = newLat; entry.lng = newLng; }

            const fd = new FormData();
            fd.append('id', data.id);
            fd.append('lat', newLat.toFixed(8));
            fd.append('lng', newLng.toFixed(8));

            fetch('api/ibadah/update_posisi.php', { method: 'POST', body: appendCsrf(fd) })
                .then(r => r.json())
                .then(j => {
                    if (j.status === 'success') {
                        showToast(`Posisi "${escapeHTML(data.nama)}" diperbarui.`);
                        // Recalculate semua penduduk
                        if (typeof window._pendudukRecalcAll === 'function') {
                            window._pendudukRecalcAll();
                        }
                    } else throw new Error(j.message);
                })
                .catch(err => showToast(err.message || 'Gagal update posisi.', 'error'));
        });

        // Popup info
        marker.bindPopup(buildInfoPopup(data), { maxWidth: 340 });

        // KK list: populate on popup open and after inline edits
        marker.on('popupopen', function () { _refreshKkList(data.id); });

        if (bufferVisible) circle.addTo(layerGroup);
        marker.addTo(layerGroup);

        // Simpan data lengkap agar bisa di-update nanti
        allMarkers[data.id] = { marker, circle, data: { ...data, radius_m: radius } };

        // Update global list untuk penduduk.js
        updateIbadahList();
    }

    function buildInfoPopup(data) {
        const isAdmin = window.APP_USER && window.APP_USER.isAdmin;
        const ownOperatorIbadah = isOwnOperatorIbadah(data.id);
        const jenis = data.jenis || 'Lainnya';
        const emoji = JENIS_ICON[jenis] || '🕍';
        const r = parseInt(data.radius_m) || 500;
        const alamatHtml = data.alamat
            ? `<div class="info-row">
                <div class="info-row-icon"><i data-lucide="map-pin"></i></div>
                <div>
                    <div class="info-row-label">Alamat</div>
                    <div class="info-row-value" style="font-size:12px;line-height:1.4;">${escapeHTML(data.alamat)}</div>
                </div>
               </div>`
            : '';

        const kontakValue = data.kontak ? escapeHTML(data.kontak) : '';
        const kontakHtml = (isAdmin || data.kontak) ? `
            <div class="info-row" style="align-items:flex-start;">
                <div class="info-row-icon"><i data-lucide="phone"></i></div>
                <div style="flex:1;">
                    <div class="info-row-label">Kontak Pengurus</div>
                    ${isAdmin ? `
                    <div style="display:flex;align-items:center;gap:8px;margin-top:6px;">
                        <input type="text" id="edit_kontak_${data.id}"
                            value="${kontakValue}" placeholder="No HP/WA (Opsional)"
                            style="flex:1;padding:6px 8px;border-radius:4px;border:1px solid rgba(255,255,255,0.15);background:rgba(0,0,0,0.2);color:var(--c-text);font-size:12px;font-family:var(--font-body);outline:none;">
                        <button onclick="window._ibadahSimpanKontak(${data.id})"
                            style="padding:6px 10px;background:rgba(124,58,237,.15);border:1px solid rgba(168,85,247,.4);border-radius:6px;color:#a855f7;font-size:12px;font-weight:700;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:4px;"
                            onmouseover="this.style.background='rgba(124,58,237,.25)'"
                            onmouseout="this.style.background='rgba(124,58,237,.15)'">
                            <i data-lucide="save" style="width:12px;height:12px;"></i> Simpan
                        </button>
                    </div>
                    <div id="kontak_status_${data.id}" style="font-size:11px;margin-top:4px;font-family:var(--font-mono);"></div>
                    ` : `<div class="info-row-value" style="margin-top:4px;">${kontakValue || '—'}</div>`}
                </div>
            </div>` : '';

        return `<div class="info-popup">
            <div class="info-popup-header">
                <div class="info-popup-icon" style="background:rgba(124,58,237,.2);">${emoji}</div>
                <div>
                    <div class="info-popup-name">${escapeHTML(data.nama)}</div>
                    <div class="info-popup-id">#${data.id} · ${escapeHTML(jenis)}</div>
                </div>
            </div>
            ${ownOperatorIbadah ? `
            <div class="info-row" style="border-color:rgba(245,158,11,.35);background:rgba(245,158,11,.10);">
                <div class="info-row-icon" style="color:#f59e0b;"><i data-lucide="star"></i></div>
                <div>
                    <div class="info-row-label" style="color:#92400e;">Rumah ibadah Anda</div>
                    <div class="info-row-value" style="font-size:12px;line-height:1.4;color:#78350f;">Akun operator ini terhubung ke rumah ibadah tersebut.</div>
                </div>
            </div>` : ''}
            ${alamatHtml}
            ${kontakHtml}
            ${isAdmin ? `
            <div class="info-row" style="align-items:flex-start;">
                <div class="info-row-icon"><i data-lucide="ruler"></i></div>
                <div style="flex:1;">
                    <div class="info-row-label">Radius Wilayah</div>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:6px;">
                        <input type="range" id="edit_radius_${data.id}"
                            value="${r}" min="100" max="5000" step="50"
                            oninput="document.getElementById('edit_radius_val_${data.id}').textContent=this.value; window._ibadahPreviewRadiusById(${data.id},this.value)"
                            style="flex:1;height:4px;accent-color:#38bdf8;cursor:pointer;">
                        <span id="edit_radius_val_${data.id}"
                            style="min-width:45px;text-align:right;font-family:var(--font-mono);
                                   font-size:12px;color:#38bdf8;font-weight:700;">${r}m</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--c-muted);margin-top:2px;">
                        <span>100m</span><span>5000m</span>
                    </div>
                    <button onclick="window._ibadahSimpanRadius(${data.id})"
                        style="margin-top:8px;width:100%;padding:6px;background:rgba(14,165,233,.12);
                               border:1px solid rgba(56,189,248,.4);border-radius:7px;
                               color:#38bdf8;font-size:12px;font-weight:700;cursor:pointer;
                               font-family:var(--font-body);transition:all .2s;display:inline-flex;align-items:center;justify-content:center;gap:4px;"
                        onmouseover="this.style.background='rgba(14,165,233,.25)'"
                        onmouseout="this.style.background='rgba(14,165,233,.12)'">
                        <i data-lucide="save" style="width:12px;height:12px;"></i> Simpan Radius
                    </button>
                    <div id="radius_status_${data.id}" style="font-size:11px;margin-top:4px;font-family:var(--font-mono);"></div>
                </div>
            </div>` : `
            <div class="info-row">
                <div class="info-row-icon"><i data-lucide="ruler"></i></div>
                <div>
                    <div class="info-row-label">Radius Wilayah</div>
                    <div class="info-row-value" style="font-family:var(--font-mono);color:#38bdf8;">${r}m</div>
                </div>
            </div>`}
            <div class="info-row">
                <div class="info-row-icon"><i data-lucide="users"></i></div>
                <div>
                    <div class="info-row-label">Warga Binaan <span style="font-size:9px;color:var(--c-muted);font-weight:400;">(terverifikasi)</span></div>
                    <div id="kkCount_${data.id}" class="info-row-value" style="font-family:var(--font-mono);">
                        <span style="color:#38bdf8;font-weight:700;">${parseInt(data.total_kk) || 0}</span> KK
                        &nbsp;·&nbsp;
                        <span style="color:#38bdf8;font-weight:700;">${parseInt(data.total_jiwa) || 0}</span> jiwa
                    </div>
                    ${parseInt(data.jiwa_terjangkau) > 0 || parseInt(data.jiwa_blankspot) > 0 ? `
                    <div style="font-size:10px;color:var(--c-muted);margin-top:3px;">
                        <span style="color:#22c55e;">● ${parseInt(data.jiwa_terjangkau)||0} terjangkau</span>
                        &nbsp;·&nbsp;
                        <span style="color:#ef4444;">● ${parseInt(data.jiwa_blankspot)||0} blank spot</span>
                    </div>` : ''}
                </div>
            </div>
            <div class="info-row">
                <div class="info-row-icon"><i data-lucide="globe"></i></div>
                <div>
                    <div class="info-row-label">Koordinat</div>
                    <div class="info-row-value" style="font-family:var(--font-mono);font-size:11px;">
                        ${parseFloat(data.lat).toFixed(6)}, ${parseFloat(data.lng).toFixed(6)}
                    </div>
                </div>
            </div>
            ${(window.APP_USER && window.APP_USER.loggedIn) ? `
            <div style="margin-top:8px;">
                <div class="info-row-label" style="margin-bottom:6px;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">
                    Daftar KK Binaan <span style="font-weight:400;color:var(--c-muted);">(klik untuk fokus)</span>
                </div>
                <div id="kkListBody_${data.id}" style="max-height:180px;overflow-y:auto;">
                    <span style="color:var(--c-muted);font-size:12px;display:inline-flex;align-items:center;gap:4px;"><i data-lucide="loader" class="animate-spin" style="width:12px;height:12px;"></i> Memuat...</span>
                </div>
            </div>` : `
            <div style="margin-top:10px;padding:8px 10px;background:rgba(56,139,253,.06);
                        border:1px solid rgba(56,139,253,.15);border-radius:8px;font-size:11px;
                        color:var(--c-muted);line-height:1.5;text-align:center;">
                <i data-lucide="lock" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> Data detail warga hanya tersedia untuk petugas yang login.<br>
                <a href="auth/login.php" style="color:#38bdf8;text-decoration:none;font-weight:700;">Masuk →</a>
            </div>`}

            ${(window.APP_USER && window.APP_USER.isAdmin) ? `
            <div style="display:flex;gap:8px;margin-top:8px;">
                <a href="api/export/csv.php?ibadah_id=${data.id}"
                   style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;
                          padding:9px;background:rgba(56,139,253,.1);color:#79c0ff;
                          border:1px solid rgba(56,139,253,.3);border-radius:7px;
                          font-size:12px;font-weight:700;text-decoration:none;transition:all .2s;"
                   onmouseover="this.style.background='rgba(56,139,253,.2)'"
                   onmouseout="this.style.background='rgba(56,139,253,.1)'">
                    <i data-lucide="file-text" style="width:12px;height:12px;"></i> Export CSV
                </a>
                <a href="api/export/pdf.php?ibadah_id=${data.id}" target="_blank"
                   style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;
                          padding:9px;background:rgba(248,81,73,.08);color:#ffb3ad;
                          border:1px solid rgba(248,81,73,.25);border-radius:7px;
                          font-size:12px;font-weight:700;text-decoration:none;transition:all .2s;"
                   onmouseover="this.style.background='rgba(248,81,73,.18)'"
                   onmouseout="this.style.background='rgba(248,81,73,.08)'">
                    <i data-lucide="printer" style="width:12px;height:12px;"></i> Print PDF
                </a>
            </div>` : ''}
            ${isAdmin ? `<button class="btn-hapus" onclick="window._ibadahHapus(${data.id}, this)" style="display:inline-flex;align-items:center;justify-content:center;gap:4px;"><i data-lucide="trash-2" style="width:12px;height:12px;"></i> Hapus Rumah Ibadah</button>` : ''}
        </div>`;
    }

    // ── Update global list ────────────────────────────────────────────
    function updateIbadahList() {
        window._ibadahList = Object.values(allMarkers).map(entry => {
            const m = entry.marker;
            const ll = m.getLatLng();
            return {
                id: m._ibadahId,
                lat: ll.lat,
                lng: ll.lng,
                nama: m._ibadahNama,
                jenis: m._ibadahJenis,
                radius: m._ibadahRadius
            };
        });
        if (typeof window._statsUpdate === 'function') window._statsUpdate();
    }

    // ── Load data ─────────────────────────────────────────────────────
    function loadData() {
        layerGroup.clearLayers();
        allMarkers = {};
        window._ibadahList = [];

        fetch('api/ibadah/ambil.php?_=' + Date.now(), { cache: 'no-store' })
            .then(r => r.json())
            .then(j => {
                if (j.status !== 'success') throw new Error(j.message);
                j.data.forEach(addToMap);
                updateIbadahList();
                refreshList(j.data);
                // Setelah load ibadah, recalculate penduduk
                if (typeof window._pendudukRecalcAll === 'function') {
                    window._pendudukRecalcAll();
                }
            })
            .catch(err => {
                showToast('Gagal memuat data ibadah.', 'error');
                console.error(err);
            });
    }

    // ── Refresh panel list ────────────────────────────────────────────
    function refreshList(data) {
        if (typeof window.dlRefreshList !== 'function') return;
        const items = (data || []).map(d => ({
            id: d.id,
            name: d.nama,
            badge: d.jenis,
            badgeColor: '#a855f7',
            dotColor: '#a855f7',
        }));
        window.dlRefreshList('Ibadah', items);
    }

    // ── Hapus ─────────────────────────────────────────────────────────
    window._ibadahHapus = function (id, btnEl) {
        // Step 1: cek operator terkait (confirmed=0)
        const fd0 = new FormData();
        fd0.append('id', id);
        fd0.append('confirmed', '0');

        fetch('api/ibadah/hapus.php', { method: 'POST', body: appendCsrf(fd0) })
            .then(r => r.json())
            .then(j => {
                if (j.status === 'warning') {
                    showDeleteConfirm(j.message + '\n\nLanjutkan hapus?').then(ok => {
                        if (ok) _doHapusIbadah(id, btnEl);
                    });
                } else if (j.status === 'success') {
                    _applyHapusIbadah(id);
                    showToast('Rumah ibadah berhasil dihapus.');
                } else {
                    showToast(j.message, 'error');
                }
            })
            .catch(() => showToast('Gagal menghubungi server.', 'error'));
    };

    function _doHapusIbadah(id, btnEl) {
        showDeleteConfirm('Yakin ingin menghapus permanen rumah ibadah ini?').then(ok => {
            if (!ok) return;
            btnEl.disabled = true;
            btnEl.innerHTML = '<i data-lucide="loader" class="animate-spin" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> Menghapus...';
            lucide.createIcons();
            const fd = new FormData();
            fd.append('id', id);
            fd.append('confirmed', '1');
            fetch('api/ibadah/hapus.php', { method: 'POST', body: appendCsrf(fd) })
                .then(r => r.json())
                .then(j => {
                    if (j.status === 'success') {
                        map.closePopup();
                        _applyHapusIbadah(id);
                        showToast('Rumah ibadah berhasil dihapus.');
                        if (typeof window._pendudukRecalcAll === 'function') window._pendudukRecalcAll();
                    } else {
                        showToast(j.message, 'error');
                        btnEl.disabled = false;
                        btnEl.innerHTML = '<i data-lucide="trash-2" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> Hapus Rumah Ibadah';
                        lucide.createIcons();
                    }
                });
        });
    }

    function _applyHapusIbadah(id) {
        if (allMarkers[id]) {
            layerGroup.removeLayer(allMarkers[id].marker);
            layerGroup.removeLayer(allMarkers[id].circle);
            delete allMarkers[id];
        }
        updateIbadahList();
        refreshList(Object.values(allMarkers).map(e => ({
            id: e.marker._ibadahId,
            nama: e.marker._ibadahNama,
            jenis: e.marker._ibadahJenis,
        })));
    }

    // ── Simpan perubahan kontak (dari popup info) ─────────────────────
    window._ibadahSimpanKontak = function (id) {
        const inputEl = document.getElementById('edit_kontak_' + id);
        const statusEl = document.getElementById('kontak_status_' + id);
        if (!inputEl) return;

        const kontak = inputEl.value.trim();

        statusEl.innerHTML = '<i data-lucide="loader" class="animate-spin" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> Menyimpan...';
        statusEl.style.color = 'var(--c-muted)';
        lucide.createIcons();

        const fd = new FormData();
        fd.append('id', id);
        fd.append('kontak', kontak);

        fetch('api/ibadah/update_kontak.php', { method: 'POST', body: appendCsrf(fd) })
            .then(r => r.json())
            .then(j => {
                if (j.status === 'success') {
                    if (allMarkers[id]) {
                        allMarkers[id].data.kontak = kontak;
                        allMarkers[id].marker.setPopupContent(buildInfoPopup(allMarkers[id].data));
                        _refreshKkList(id);
                    }
                    showToast(`Kontak berhasil diperbarui.`);
                } else throw new Error(j.message);
            })
            .catch(err => {
                statusEl.innerHTML = '<i data-lucide="x" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> ' + escapeHTML(err.message);
                statusEl.style.color = 'var(--c-danger)';
                lucide.createIcons();
            });
    };

    // ── Simpan perubahan radius (dari popup info) ─────────────────────
    window._ibadahSimpanRadius = function (id) {
        const inputEl = document.getElementById('edit_radius_' + id);
        const statusEl = document.getElementById('radius_status_' + id);
        if (!inputEl) return;

        const radius = Math.max(100, Math.min(5000, parseInt(inputEl.value) || 500));
        inputEl.value = radius; // normalize

        statusEl.innerHTML = '<i data-lucide="loader" class="animate-spin" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> Menyimpan...';
        statusEl.style.color = 'var(--c-muted)';
        lucide.createIcons();

        const fd = new FormData();
        fd.append('id', id);
        fd.append('radius_m', radius);

        fetch('api/ibadah/update_radius.php', { method: 'POST', body: appendCsrf(fd) })
            .then(r => r.json())
            .then(j => {
                if (j.status === 'success') {
                    if (allMarkers[id]) {
                        allMarkers[id].circle.setRadius(radius);
                        allMarkers[id].marker._ibadahRadius = radius;

                        // Update objek data yang disimpan
                        allMarkers[id].data.radius_m = radius;

                        // Update isi popup agar saat dibuka lagi nilainya sudah yang baru
                        allMarkers[id].marker.setPopupContent(buildInfoPopup(allMarkers[id].data));
                        _refreshKkList(id);
                    }

                    // Update _ibadahList
                    const entry = window._ibadahList.find(x => parseInt(x.id) === id);
                    if (entry) entry.radius = radius;

                    // Recalculate semua penduduk dengan radius baru
                    if (typeof window._pendudukRecalcAll === 'function') {
                        window._pendudukRecalcAll();
                    }

                    // Alert jika ada warga baru yang jadi blank spot akibat radius diperkecil
                    setTimeout(() => {
                        const newBlankCount = (window._pendudukAll || window._pendudukList || [])
                            .filter(p => !p.ibadah).length;
                        if (newBlankCount > 0) {
                            showToast(
                                `⚠ Radius diperkecil — ${newBlankCount} warga kini menjadi Blank Spot.`,
                                'error'
                            );
                        }
                        if (typeof window._updateBlankSpotCounter === 'function') {
                            window._updateBlankSpotCounter();
                        }
                    }, 200);

                    showToast(`Radius berhasil diubah ke ${radius}m.`);
                } else throw new Error(j.message);
            })
            .catch(err => {
                statusEl.innerHTML = '<i data-lucide="x" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> ' + escapeHTML(err.message);
                statusEl.style.color = 'var(--c-danger)';
                lucide.createIcons();
            });
    };

    // ── Klik peta: Form tambah ibadah ─────────────────────────────────
    function onMapClick(e) {
        if (typeof activeTool === 'undefined' || activeTool !== 'ibadah') return;

        const lat = e.latlng.lat.toFixed(8);
        const lng = e.latlng.lng.toFixed(8);

        if (tempMarker) map.removeLayer(tempMarker);
        tempMarker = L.marker([lat, lng], { icon: iconTemp }).addTo(map);

        // Buka popup dengan alamat "Loading..."
        const popup = L.popup({ maxWidth: 340, closeOnClick: false })
            .setLatLng(e.latlng)
            .setContent(buildFormPopup(lat, lng, 'Mengambil alamat...'))
            .openOn(map);

        // Reverse Geocoding via Nominatim
        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=id`, {
            headers: { 'Accept-Language': 'id' }
        })
            .then(r => r.json())
            .then(geo => {
                const alamat = geo.display_name || '';
                // Update field alamat di form
                const el = document.getElementById('f_ibadah_alamat');
                if (el) el.value = alamat;
            })
            .catch(() => {
                const el = document.getElementById('f_ibadah_alamat');
                if (el) el.value = '';
            });

        setTimeout(() => { const el = document.getElementById('f_ibadah_nama'); if (el) el.focus(); }, 200);

        // Tampilkan preview circle radius saat popup terbuka
        if (_previewCircle) { layerGroup.removeLayer(_previewCircle); _previewCircle = null; }
        _previewCircle = L.circle([lat, lng], {
            radius: 500, color: '#38bdf8', weight: 1.5, opacity: 0.6,
            fillColor: '#0ea5e9', fillOpacity: 0.05, dashArray: '6,4'
        }).addTo(layerGroup);

        // Hapus preview circle & temp marker saat popup ditutup
        const _markerThisClick = tempMarker;
        const _circleThisClick = _previewCircle;
        map.once('popupclose', function () {
            if (_circleThisClick) { try { layerGroup.removeLayer(_circleThisClick); } catch(_){} }
            if (_previewCircle === _circleThisClick) _previewCircle = null;
            if (_markerThisClick) { try { map.removeLayer(_markerThisClick); } catch(_){} }
            if (tempMarker === _markerThisClick) tempMarker = null;
        });
    }

    function buildFormPopup(lat, lng, alamatPlaceholder) {
        return `<div class="form-popup">
            <div class="form-popup-header">
                <div class="form-popup-icon" style="background:rgba(14,165,233,.12);"><i data-lucide="map-pin"></i></div>
                <div>
                    <div class="form-popup-title">Tambah Rumah Ibadah</div>
                    <div class="form-popup-coords">${lat}, ${lng}</div>
                </div>
            </div>
            <div class="form-group">
                <label>Nama Tempat *</label>
                <input type="text" id="f_ibadah_nama" placeholder="cth. Masjid Al-Ikhlas" maxlength="100" autocomplete="off">
            </div>
            <div class="form-group">
                <label>Jenis</label>
                <select id="f_ibadah_jenis">
                    <option value="Masjid">🕌 Masjid</option>
                    <option value="Mushola">🕌 Mushola</option>
                    <option value="Gereja">⛪ Gereja</option>
                    <option value="Pura">🛕 Pura</option>
                    <option value="Vihara">🏯 Vihara</option>
                    <option value="Klenteng">🏮 Klenteng</option>
                </select>
            </div>
            <div class="form-group">
                <label><i data-lucide="ruler" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> Radius Wilayah</label>
                <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                    <input type="range" id="f_ibadah_radius" value="500" min="100" max="5000" step="50"
                        oninput="document.getElementById('f_ibadah_radius_val').textContent=this.value+'m'; window._ibadahPreviewRadius(this.value)"
                        style="flex:1;height:4px;accent-color:#38bdf8;cursor:pointer;">
                    <span id="f_ibadah_radius_val"
                        style="min-width:50px;text-align:right;font-family:var(--font-mono);
                               font-size:12px;color:#38bdf8;font-weight:700;">500m</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--c-muted);margin-top:2px;">
                    <span>100m</span><span>5000m</span>
                </div>
            </div>
            <div class="form-group">
                <label>Alamat (Otomatis)</label>
                <input type="text" id="f_ibadah_alamat" value="${escapeHTML(alamatPlaceholder)}" placeholder="Mendeteksi alamat..." autocomplete="off">
            </div>
            <div class="form-group">
                <label>Kontak Pengurus</label>
                <input type="text" id="f_ibadah_kontak" placeholder="No HP/WA (Opsional)" maxlength="100" autocomplete="off">
            </div>
            <input type="hidden" id="f_ibadah_lat" value="${lat}">
            <input type="hidden" id="f_ibadah_lng" value="${lng}">
            <button class="btn-save" id="btnSimpanIbadah" onclick="window._ibadahSimpan()" style="background:linear-gradient(135deg,#7c3aed,#a855f7);display:inline-flex;align-items:center;justify-content:center;gap:4px;">
                <i data-lucide="save" style="width:14px;height:14px;"></i> Simpan Rumah Ibadah
            </button>
            <div class="form-status" id="ibadahStatus"></div>
        </div>`;
    }

    // ── Live preview radius (form tambah baru) ────────────────────────
    window._ibadahPreviewRadius = function (val) {
        const r = Math.max(100, Math.min(5000, parseInt(val) || 500));
        if (_previewCircle) _previewCircle.setRadius(r);
    };

    // ── Live preview radius (edit marker yg sudah ada) ────────────────
    let _recalcDebounce = null;
    window._ibadahPreviewRadiusById = function (id, val) {
        const r = Math.max(100, Math.min(5000, parseInt(val) || 500));
        const targetId = parseInt(id);

        // 1. Update visual circle & marker property
        if (allMarkers[targetId]) {
            allMarkers[targetId].circle.setRadius(r);
            allMarkers[targetId].marker._ibadahRadius = r;
        }

        // 2. Paksa sinkronisasi window._ibadahList agar penduduk.js melihat radius terbaru
        if (window._ibadahList) {
            const entry = window._ibadahList.find(x => parseInt(x.id) === targetId);
            if (entry) {
                entry.radius = r;
            } else {
                // Jika tidak ketemu (jarang terjadi), panggil sync full
                updateIbadahList();
            }
        }

        // 3. Recalculate warna penduduk (debounce agar ringan)
        clearTimeout(_recalcDebounce);
        _recalcDebounce = setTimeout(() => {
            console.log(`[Ibadah] Recalculating proximity for Radius: ${r}m`);
            if (typeof window._pendudukRecalcAll === 'function') {
                window._pendudukRecalcAll();
            }
        }, 50); // Dipercepat ke 50ms untuk feel yang lebih instan
    };

    // ── Simpan ────────────────────────────────────────────────────────
    window._ibadahSimpan = function () {
        const nama = document.getElementById('f_ibadah_nama').value.trim();
        const jenis = document.getElementById('f_ibadah_jenis').value;
        const radius = Math.max(100, Math.min(5000, parseInt(document.getElementById('f_ibadah_radius')?.value) || 500));
        const alamat = document.getElementById('f_ibadah_alamat').value.trim();
        const kontak = document.getElementById('f_ibadah_kontak')?.value.trim() || '';
        const lat = document.getElementById('f_ibadah_lat').value;
        const lng = document.getElementById('f_ibadah_lng').value;
        const status = document.getElementById('ibadahStatus');
        const btn = document.getElementById('btnSimpanIbadah');

        if (!nama) {
            status.textContent = '⚠ Nama wajib diisi.';
            status.className = 'form-status error';
            document.getElementById('f_ibadah_nama').focus();
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader" class="animate-spin" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> Menyimpan...';
        lucide.createIcons();

        const fd = new FormData();
        fd.append('nama', nama);
        fd.append('jenis', jenis);
        fd.append('alamat', alamat);
        fd.append('kontak', kontak);
        fd.append('lat', lat);
        fd.append('lng', lng);
        fd.append('radius_m', radius);

        fetch('api/ibadah/simpan.php', { method: 'POST', body: appendCsrf(fd) })
            .then(r => r.json())
            .then(j => {
                if (j.status === 'success') {
                    map.closePopup();
                    if (_previewCircle) { layerGroup.removeLayer(_previewCircle); _previewCircle = null; }
                    addToMap(j.data);
                    updateIbadahList();
                    refreshList(Object.values(allMarkers).map(e => ({
                        id: e.marker._ibadahId,
                        nama: e.marker._ibadahNama,
                        jenis: e.marker._ibadahJenis,
                    })));
                    showToast(`"${escapeHTML(nama)}" berhasil ditambahkan!`);
                    // Recalculate penduduk setelah ibadah baru ditambah
                    if (typeof window._pendudukRecalcAll === 'function') {
                        window._pendudukRecalcAll();
                    }
                } else throw new Error(j.message);
            })
            .catch(err => {
                status.innerHTML = '<i data-lucide="x" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> ' + escapeHTML(err.message);
                status.className = 'form-status error';
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="save" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i> Simpan Rumah Ibadah';
                lucide.createIcons();
            });
    };

    // ── Recalculate All proximity (Admin only, with race-condition guard) ──
    let _recalcInProgress = false;

    window._recalcAll = function () {
        if (_recalcInProgress) {
            showToast('Proses hitung ulang sedang berjalan...', 'error');
            return;
        }
        showDeleteConfirm(
            'Proses ini dapat mengubah assignment warga ke rumah ibadah/operator berdasarkan radius terkini. Lanjutkan?',
            { type: 'warn', iconName: 'refresh-cw', title: 'Konfirmasi Hitung Ulang Proximity', btnLabel: 'Ya, Hitung Ulang' }
        ).then(confirmed => {
            if (!confirmed) return;
            _recalcInProgress = true;

            const allBtns = document.querySelectorAll(
                '#btnToolIbadah, #btnToolPenduduk, #btnRecalcAll, .btn-hapus, .btn-save'
            );
            allBtns.forEach(b => { b.disabled = true; });

            const btn = document.getElementById('btnRecalcAll');
            const icon = btn ? btn.querySelector('.tb-icon') : null;
            if (btn) {
                if (icon) icon.textContent = '⏳';
                btn.style.color = 'var(--c-muted)';
            }

            showToast('Menghitung ulang proximity...');

            fetch('api/ibadah/recalculate.php', { method: 'POST', body: appendCsrf(new FormData()) })
                .then(r => r.json())
                .then(j => {
                    if (j.status === 'success') {
                        showToast(`✓ ${j.message}`);
                        if (typeof window._pendudukReload === 'function') {
                            window._pendudukReload();
                        } else if (typeof window._pendudukRecalcAll === 'function') {
                            window._pendudukRecalcAll();
                        }
                    } else {
                        showToast(j.message || 'Gagal menghitung ulang.', 'error');
                    }
                })
                .catch(() => showToast('Gagal menghubungi server.', 'error'))
                .finally(() => {
                    _recalcInProgress = false;
                    allBtns.forEach(b => { b.disabled = false; });
                    if (btn) {
                        if (icon) icon.textContent = '🔄';
                        btn.style.color = 'var(--c-warn)';
                    }
                });
        });
    };

    window._ibadahReload = loadData;

    // ── Init ─────────────────────────────────────────────────────────
    window.initIbadah = function () {
        if (active) { loadData(); return; }
        active = true;

        layerGroup.addTo(map);
        map.on('click', onMapClick);

        window._dlFocusFns['Ibadah'] = function (id) {
            const entry = allMarkers[id];
            if (!entry) return;
            map.setView(entry.marker.getLatLng(), Math.max(map.getZoom(), 17), { animate: true });
            setTimeout(() => entry.marker.openPopup(), 350);
        };

        loadData();
    };

    // ── Toggle visibilitas layer ──────────────────────────────────────
    window._ibadahToggleLayer = function (visible) {
        if (visible) {
            if (!map.hasLayer(layerGroup)) map.addLayer(layerGroup);
        } else {
            map.removeLayer(layerGroup);
            if (tempMarker) { map.removeLayer(tempMarker); tempMarker = null; }
        }
    };

    window._setBufferVisible = function (visible) {
        bufferVisible = visible;
        Object.values(allMarkers).forEach(function (entry) {
            if (visible) {
                if (!layerGroup.hasLayer(entry.circle)) entry.circle.addTo(layerGroup);
            } else {
                if (layerGroup.hasLayer(entry.circle)) layerGroup.removeLayer(entry.circle);
            }
        });
    };
})();
