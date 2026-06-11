// modules/penduduk.js — Manajemen Penduduk Miskin dengan Proximity Logic (Haversine)
(function () {
    const layerGroup = L.layerGroup();
    window._pendudukLayer = layerGroup;
    window._pendudukAll = []; // Dataset penuh dari API.
    window._pendudukVisible = []; // Dataset yang sedang terlihat/listed.
    window._pendudukList = window._pendudukVisible; // Alias kompatibilitas.

    let allMarkers = {}; // { id: circleMarker }
    let filterState    = { search: '', kategori: '', keterjangkauan: '', jenisIbadah: '', statusBantuan: '', kebutuhanKategori: '', kebutuhanStatus: '' };
    let subLayerVisible = { terjangkau: true, blankspot: true };
    let tempMarker = null;
    let active = false;

    // ── Warna ─────────────────────────────────────────────────────────
    const COLOR_TERJANGKAU = '#22c55e'; // Hijau — dalam radius (terjangkau)
    const COLOR_BLANK_SPOT = '#ef4444'; // Merah — di luar semua radius (blank spot)
    const STATUS_COLOR = { 'Belum Ditangani': '#ef4444', 'Dalam Proses': '#e3b341', 'Sudah Ditangani': '#3fb950' };

    // ── Formula Haversine (return jarak dalam meter) ──────────────────
    function haversine(lat1, lng1, lat2, lng2) {
        const R   = 6371000; // radius bumi dalam meter
        const toR = x => x * Math.PI / 180;
        const dLat = toR(lat2 - lat1);
        const dLng = toR(lng2 - lng1);
        const a = Math.sin(dLat / 2) ** 2
                + Math.cos(toR(lat1)) * Math.cos(toR(lat2)) * Math.sin(dLng / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    // ── Cari ibadah terdekat dalam radius 500m ────────────────────────
    function findNearestIbadah(lat, lng) {
        let nearest  = null;
        let minDist  = Infinity;
        const list   = window._ibadahList || [];

        for (const ib of list) {
            const d = haversine(lat, lng, ib.lat, ib.lng);
            // Gunakan ib.radius yang sudah di-update real-time oleh ibadah.js
            const activeRadius = parseInt(ib.radius) || 500;
            if (d <= activeRadius && d < minDist) {
                minDist = d;
                nearest = { ...ib, jarak: Math.round(d), radius: activeRadius };
            }
        }
        return nearest; // null = di luar semua radius
    }

    // ── Render / update warna satu marker ────────────────────────────
    function applyColor(cm, ibadah) {
        const color = ibadah ? COLOR_TERJANGKAU : COLOR_BLANK_SPOT;
        cm.setStyle({
            color      : color,
            fillColor  : color,
            fillOpacity: 0.55,
            weight     : 2,
        });
        cm._nearestIbadah = ibadah;
    }

    // ── Buat popup teks ───────────────────────────────────────────────
    function buildInfoPopup(data, ibadah) {
        const isAdmin = window.APP_USER && window.APP_USER.isAdmin;

        const ibadahSection = ibadah
            ? `<div class="info-row" style="background:rgba(34,197,94,.06);border-radius:8px;padding:6px 8px;margin:2px 0;">
                <div class="info-row-icon"><i data-lucide="map-pin"></i></div>
                <div style="flex:1;">
                    <div class="info-row-label">Rumah Ibadah Terdekat</div>
                    <div class="info-row-value" style="color:#22c55e;font-weight:600;">${escapeHTML(ibadah.nama)}</div>
                    <div style="font-size:11px;color:var(--c-muted);margin-top:3px;display:inline-flex;align-items:center;gap:3px;flex-wrap:wrap;">
                        <i data-lucide="ruler" style="width:12px;height:12px;vertical-align:middle;margin-right:2px;"></i> ${ibadah.jarak}m · <i data-lucide="circle" style="width:10px;height:10px;vertical-align:middle;margin-right:2px;"></i> Radius ${ibadah.radius || 500}m · ${ibadah.jenis || ''}
                    </div>
                </div>
               </div>`
            : `<div class="info-row" style="background:rgba(239,68,68,.06);border-radius:8px;padding:6px 8px;margin:2px 0;">
                <div class="info-row-icon" style="color:#ef4444;"><i data-lucide="alert-triangle"></i></div>
                <div>
                    <div class="info-row-label">Status Wilayah</div>
                    <div class="info-row-value" style="color:#ef4444;font-weight:600;">Di luar semua radius ibadah (Blank Spot)</div>
                </div>
               </div>`;

        const nikHtml = (data.nik && window.APP_USER && window.APP_USER.loggedIn)
            ? `<div style="font-size:11px;color:var(--c-muted);margin-top:2px;">NIK: ${escapeHTML(data.nik)}</div>` : '';

        const catatanHtml = (data.catatan && window.APP_USER && window.APP_USER.loggedIn)
            ? `<div class="info-row">
                <div class="info-row-icon"><i data-lucide="file-text"></i></div>
                <div>
                    <div class="info-row-label">Catatan Kondisi</div>
                    <div class="info-row-value" style="font-size:12px;line-height:1.4;">${escapeHTML(data.catatan)}</div>
                </div>
               </div>` : '';

        const isOp = window.APP_USER && window.APP_USER.isOp;
        const st   = data.status_bantuan || 'Belum Ditangani';
        const stC  = STATUS_COLOR[st] || '#8b949e';
        const sv     = data.status_verifikasi || 'Pending';
        const statusBtns = sv === 'Terverifikasi'
            ? ['Belum Ditangani', 'Dalam Proses', 'Sudah Ditangani'].map(s =>
            `<button onclick="window._updateStatus(${data.id},'${s}',this)"
                style="font-size:11px;padding:3px 8px;border-radius:4px;cursor:pointer;
                    border:1px solid ${STATUS_COLOR[s]};
                    background:${s === st ? STATUS_COLOR[s] + '33' : 'transparent'};
                    color:${STATUS_COLOR[s]};font-weight:${s === st ? '700' : '400'};">${escapeHTML(s)}</button>`
            ).join('')
            : `<span style="font-size:11px;color:var(--c-muted);font-weight:600;">Menunggu verifikasi admin</span>`;

        const statusSection = isOp
            ? `<div class="info-row" style="align-items:flex-start;">
                <div class="info-row-icon"><i data-lucide="clipboard-list"></i></div>
                <div style="flex:1;">
                    <div class="info-row-label">Status Bantuan</div>
                    <span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;
                        background:${stC}22;color:${stC}0d;margin-top:3px;border:1px solid ${stC}44;color:${stC};">${escapeHTML(st)}</span>
                    <div data-sg="${data.id}" style="display:flex;gap:4px;margin-top:6px;flex-wrap:wrap;">${statusBtns}</div>
                    <button onclick="window._showRiwayat(${data.id})"
                        style="font-size:11px;padding:3px 8px;margin-top:6px;border-radius:4px;cursor:pointer;
                            border:1px solid var(--c-border);background:transparent;color:var(--c-muted);display:inline-flex;align-items:center;gap:4px;">
                        <i data-lucide="history" style="width:11px;height:11px;"></i> Lihat Riwayat
                    </button>
                </div>
               </div>` : '';

        // ── Badge & aksi verifikasi ───────────────────────────────
        const svClr  = { Terverifikasi: '#22c55e', Pending: '#e3b341', Ditolak: '#ef4444' }[sv] || '#8b949e';
        const svIcon = {
            Terverifikasi: '<i data-lucide="check-circle" style="width:11px;height:11px;vertical-align:middle;margin-right:4px;"></i>',
            Pending: '<i data-lucide="clock" style="width:11px;height:11px;vertical-align:middle;margin-right:4px;"></i>',
            Ditolak: '<i data-lucide="x-circle" style="width:11px;height:11px;vertical-align:middle;margin-right:4px;"></i>'
        }[sv] || '';
        const verifBadge = (isAdmin || isOp)
            ? `<div style="display:inline-flex;align-items:center;font-size:10px;font-weight:700;
                   padding:2px 8px;border-radius:20px;margin-top:4px;
                   background:${svClr}20;color:${svClr};border:1px solid ${svClr}44;">
                ${svIcon}<span>${sv}</span>
               </div>` : '';

        const verifActions = (isAdmin && sv === 'Pending')
            ? `<div style="display:flex;gap:6px;margin-top:8px;padding-top:10px;border-top:1px solid rgba(255,255,255,.08);">
                <button onclick="window._verifikasiPenduduk(${data.id},'approve',this)"
                    style="flex:1;padding:7px;border-radius:7px;cursor:pointer;font-size:12px;font-weight:700;
                        border:1px solid #22c55e;background:rgba(34,197,94,.1);color:#22c55e;display:inline-flex;align-items:center;justify-content:center;gap:4px;">
                    <i data-lucide="check" style="width:12px;height:12px;"></i> Verifikasi
                </button>
                <button onclick="window._verifikasiPenduduk(${data.id},'reject',this)"
                    style="flex:1;padding:7px;border-radius:7px;cursor:pointer;font-size:12px;font-weight:700;
                        border:1px solid #ef4444;background:rgba(239,68,68,.1);color:#ef4444;display:inline-flex;align-items:center;justify-content:center;gap:4px;">
                    <i data-lucide="x" style="width:12px;height:12px;"></i> Tolak
                </button>
               </div>` : '';

        const actionBtns = isAdmin
            ? `<div style="display:flex;gap:8px;margin-top:8px;">
                <button class="btn-hapus" style="flex:1;background:rgba(227,179,65,.08);color:#f0c240;border-color:rgba(227,179,65,.3);display:inline-flex;align-items:center;justify-content:center;gap:4px;"
                    onclick="window._pendudukNonaktifkan(${data.id}, this)">
                    <i data-lucide="user-x" style="width:12px;height:12px;"></i> Nonaktifkan
                </button>
                <button class="btn-hapus" style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:4px;"
                    onclick="window._pendudukHapus(${data.id}, this)">
                    <i data-lucide="trash-2" style="width:12px;height:12px;"></i> Hapus Data
                </button>
               </div>` : '';

        const kebutuhanSection = (window.APP_USER && window.APP_USER.loggedIn && typeof window._kebutuhanBuildSection === 'function')
            ? window._kebutuhanBuildSection(data.id, data.kebutuhan_open, sv)
            : '';

        return `<div class="info-popup">
            <div class="info-popup-header">
                <div class="info-popup-icon" style="background:${ibadah ? 'rgba(34,197,94,.12)' : 'rgba(239,68,68,.12)'};"><i data-lucide="home"></i></div>
                <div>
                    <div class="info-popup-name">${escapeHTML(data.nama_kk || 'Warga #' + data.id)}</div>
                    ${nikHtml}
                    <div class="info-popup-id">#${data.id} · ${data.kategori || 'Miskin'}</div>
                    ${verifBadge}
                </div>
            </div>
            ${ibadahSection}
            <div class="info-row">
                <div class="info-row-icon"><i data-lucide="users"></i></div>
                <div><div class="info-row-label">Jumlah Jiwa</div>
                     <div class="info-row-value">${data.jumlah_jiwa} orang</div></div>
            </div>
            <div class="info-row">
                <div class="info-row-icon"><i data-lucide="map-pin"></i></div>
                <div><div class="info-row-label">Alamat</div>
                     <div class="info-row-value">${escapeHTML(data.alamat || '-')}</div></div>
            </div>
            ${catatanHtml}
            ${statusSection}
            ${kebutuhanSection}
            ${verifActions}
            ${actionBtns}
        </div>`;
    }

    // ── Tambah marker ke peta ─────────────────────────────────────────
    function addToMap(data) {
        const lat = parseFloat(data.lat);
        const lng = parseFloat(data.lng);

        // Koordinat null = publik — simpan ke list saja tanpa marker
        if (isNaN(lat) || isNaN(lng)) {
            const ibadahRef = data.ibadah_id
                ? { id: data.ibadah_id, nama: data.nama_ibadah, jenis: data.jenis_ibadah }
                : null;
            allMarkers[data.id] = { cm: null, dragHandle: null, data, _ibadahRef: ibadahRef };
            return;
        }

        const ibadah = findNearestIbadah(lat, lng);
        const color  = ibadah ? COLOR_TERJANGKAU : COLOR_BLANK_SPOT;

        const cm = L.circleMarker([lat, lng], {
            radius     : 8,
            color      : color,
            fillColor  : color,
            fillOpacity: 0.55,
            weight     : 2,
        });

        cm._pendudukId    = data.id;
        cm._pendudukNama  = data.nama_kk;
        cm._pendudukJiwa  = data.jumlah_jiwa;
        cm._nearestIbadah = ibadah;

        cm.bindPopup(buildInfoPopup(data, ibadah), { maxWidth: 280 });

        // Drag to update position
        // circleMarker tidak draggable by default, gunakan workaround dengan L.marker invisible
        cm.on('click', function () {
            cm.openPopup();
        });

        // Draggable via L.marker overlay (admin only)
        const isAdmin = window.APP_USER && window.APP_USER.isAdmin;
        const dragHandle = L.marker([lat, lng], {
            icon: L.divIcon({
                className: '',
                html: '<div style="width:16px;height:16px;cursor:move;"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8],
            }),
            draggable: isAdmin,
            zIndexOffset: -500,
            opacity: 0,
        });

        dragHandle._cmRef   = cm;
        dragHandle._dataRef = data;

        // Forward klik ke popup circleMarker
        dragHandle.on('click', function () {
            cm.openPopup();
        });
        dragHandle.on('drag', function (e) {
            const { lat: nLat, lng: nLng } = e.target.getLatLng();
            cm.setLatLng([nLat, nLng]);
            if (allMarkers[data.id]) {
                allMarkers[data.id].data.lat = nLat;
                allMarkers[data.id].data.lng = nLng;
            }
            updatePendudukList();
        });

        dragHandle.on('dragend', function (e) {
            const { lat: nLat, lng: nLng } = e.target.getLatLng();
            cm.setLatLng([nLat, nLng]);
            cm.closePopup();

            const ibadahBaru = findNearestIbadah(nLat, nLng);
            applyColor(cm, ibadahBaru);
            // Spread from allMarkers (live, may have updated status_bantuan etc.), not stale closure
            const updatedData = { ...allMarkers[data.id].data, lat: nLat, lng: nLng, is_blank_spot: ibadahBaru ? 0 : 1 };
            allMarkers[data.id].data = updatedData;
            cm.bindPopup(buildInfoPopup(updatedData, ibadahBaru), { maxWidth: 280 });
            updatePendudukList();
            refreshList();

            const fd = new FormData();
            fd.append('id',        data.id);
            fd.append('lat',  nLat.toFixed(8));
            fd.append('lng', nLng.toFixed(8));
            fd.append('ibadah_id', ibadahBaru ? ibadahBaru.id : '');

            fetch('api/penduduk/update_posisi.php', { method: 'POST', body: appendCsrf(fd) })
                .then(r => r.json())
                .then(j => {
                    if (j.status === 'success') {
                        showToast(`Posisi "${escapeHTML(data.nama_kk)}" diperbarui.`);
                    } else throw new Error(j.message);
                })
                .catch(err => showToast(err.message || 'Gagal update posisi.', 'error'));
        });

        cm.addTo(layerGroup);
        dragHandle.addTo(layerGroup);

        allMarkers[data.id] = { cm, dragHandle, data };
    }

    // ── Recalculate semua warna penduduk (dipanggil saat ibadah berubah) ──
    window._pendudukRecalcAll = function () {
        Object.values(allMarkers).forEach(({ cm, data }) => {
            if (!cm) return; // publik — tidak ada marker
            const ll     = cm.getLatLng();
            const ibadah = findNearestIbadah(ll.lat, ll.lng);
            applyColor(cm, ibadah);
            const updatedData = { ...data, lat: ll.lat, lng: ll.lng, is_blank_spot: ibadah ? 0 : 1 };
            allMarkers[data.id].data = updatedData;
            cm.bindPopup(buildInfoPopup(updatedData, ibadah), { maxWidth: 280 });
        });
        updatePendudukList();
        refreshList();
    };

    // ── Load data ─────────────────────────────────────────────────────
    function loadData() {
        // Reset blank spot filter so imported/reloaded data renders unfiltered
        _blankSpotFilterActive = false;
        const btn = document.getElementById('btnBlankSpot');
        if (btn) btn.style.background = 'transparent';

        layerGroup.clearLayers();
        allMarkers = {};

        fetch('api/penduduk/ambil.php?_=' + Date.now(), { cache: 'no-store' })
            .then(r => r.json())
            .then(j => {
                if (j.status !== 'success') throw new Error(j.message);
                j.data.forEach(addToMap);
                updatePendudukList();
                refreshList();
            })
            .catch(err => {
                showToast('Gagal memuat data penduduk.', 'error');
                console.error(err);
            });
    }

    // ── Refresh panel list ────────────────────────────────────────────
    window._pendudukSetFilter = function () {
        filterState.search            = (document.getElementById('searchPenduduk')?.value        || '').toLowerCase().trim();
        filterState.kategori          =  document.getElementById('filterKategori')?.value         || '';
        filterState.keterjangkauan    =  document.getElementById('filterKeterjangkauan')?.value   || '';
        filterState.jenisIbadah       =  document.getElementById('filterJenisIbadah')?.value      || '';
        filterState.statusBantuan     =  document.getElementById('filterStatusBantuan')?.value    || '';
        filterState.kebutuhanKategori =  document.getElementById('filterKebutuhanKategori')?.value || '';
        filterState.kebutuhanStatus   =  document.getElementById('filterKebutuhanStatus')?.value  || '';
        applyFilters();
    };

    // Update kebutuhan_open count in live data + badge (called by kebutuhan.js)
    window._pendudukRefreshKebutuhanCount = function (pendudukId, newCount) {
        const entry = allMarkers[pendudukId];
        if (!entry) return;
        entry.data.kebutuhan_open = newCount;
        const badgeEl = document.getElementById('kebutuhanBadge_' + pendudukId);
        if (badgeEl) {
            if (newCount > 0) {
                badgeEl.textContent = newCount + ' Belum Terpenuhi';
                badgeEl.style.cssText = 'background:#ef444422;color:#ef4444;border:1px solid #ef444444;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;margin-left:4px;';
            } else {
                badgeEl.textContent = '';
                badgeEl.style.cssText = '';
            }
        }
    };

    window._setPendudukSubLayerVisible = function (sub, visible) {
        subLayerVisible[sub] = visible;
        applyFilters();
    };

    function applyFilters() {
        Object.values(allMarkers).forEach(function (m) {
            // Entry publik tanpa marker — skip layer toggle
            if (!m.cm) return;
            const d = m.data;
            let show = true;

            // Sub-layer toggles
            if (parseInt(d.is_blank_spot) === 1) {
                if (!subLayerVisible.blankspot)   show = false;
            } else {
                if (!subLayerVisible.terjangkau)  show = false;
            }

            // Search: nama KK atau nama ibadah (OR), AND dengan filter lain
            if (show && filterState.search) {
                const q = filterState.search;
                const matchKK     = (d.nama_kk     || '').toLowerCase().includes(q);
                const matchIbadah = (d.nama_ibadah || '').toLowerCase().includes(q);
                if (!matchKK && !matchIbadah) show = false;
            }

            // Kategori
            if (show && filterState.kategori && d.kategori !== filterState.kategori) show = false;

            // Keterjangkauan
            if (show && filterState.keterjangkauan) {
                if (filterState.keterjangkauan === 'terjangkau' && parseInt(d.is_blank_spot) === 1)  show = false;
                if (filterState.keterjangkauan === 'blankspot'  && parseInt(d.is_blank_spot) !== 1)  show = false;
            }

            // Jenis ibadah terdekat
            if (show && filterState.jenisIbadah && d.jenis_ibadah !== filterState.jenisIbadah) show = false;

            // Status bantuan
            if (show && filterState.statusBantuan && d.status_bantuan !== filterState.statusBantuan) show = false;

            // Kebutuhan — kategori (cek apakah ada kebutuhan open di kategori tsb)
            if (show && filterState.kebutuhanKategori) {
                const openCats = (d.kebutuhan_kategori_open || '').split(',').filter(Boolean);
                if (!openCats.includes(filterState.kebutuhanKategori)) show = false;
            }

            // Kebutuhan — status (ada ≥1 kebutuhan dalam status tsb)
            if (show && filterState.kebutuhanStatus) {
                const ks = filterState.kebutuhanStatus;
                if (ks === 'Belum Terpenuhi' && !(parseInt(d.kebutuhan_open)      > 0)) show = false;
                if (ks === 'Dalam Proses'    && !(parseInt(d.kebutuhan_proses)    > 0)) show = false;
                if (ks === 'Terpenuhi'       && !(parseInt(d.kebutuhan_terpenuhi) > 0)) show = false;
            }

            if (show) {
                if (!layerGroup.hasLayer(m.cm))         m.cm.addTo(layerGroup);
                if (!layerGroup.hasLayer(m.dragHandle)) m.dragHandle.addTo(layerGroup);
            } else {
                if (layerGroup.hasLayer(m.cm))           layerGroup.removeLayer(m.cm);
                if (layerGroup.hasLayer(m.dragHandle))   layerGroup.removeLayer(m.dragHandle);
            }
        });

        updatePendudukList();
        refreshList();
    }

    function updatePendudukList() {
        function toListItem(m) {
            const hasMarker = !!m.cm;
            const ibadah    = hasMarker ? m.cm._nearestIbadah : m._ibadahRef;
            return {
                id                       : m.data.id,
                lat                      : hasMarker ? m.cm.getLatLng().lat : (m.data.lat ?? null),
                lng                      : hasMarker ? m.cm.getLatLng().lng : (m.data.lng ?? null),
                jiwa                     : parseInt(m.data.jumlah_jiwa) || 1,
                jumlah_jiwa              : parseInt(m.data.jumlah_jiwa) || 1,
                nama_kk                  : m.data.nama_kk,
                kategori                 : m.data.kategori       || 'Miskin',
                is_blank_spot            : parseInt(m.data.is_blank_spot) || 0,
                status_bantuan           : m.data.status_bantuan || 'Belum Ditangani',
                ibadah_jenis             : m.data.jenis_ibadah,
                ibadah_nama              : m.data.nama_ibadah,
                isRadius                 : !!ibadah,
                ibadah                   : ibadah,
                kebutuhan_open           : parseInt(m.data.kebutuhan_open)      || 0,
                kebutuhan_proses         : parseInt(m.data.kebutuhan_proses)    || 0,
                kebutuhan_terpenuhi      : parseInt(m.data.kebutuhan_terpenuhi) || 0,
                kebutuhan_kategori_open  : m.data.kebutuhan_kategori_open || '',
            };
        }

        const entries = Object.values(allMarkers);
        const visibleEntries = entries.filter(m => !m.cm || layerGroup.hasLayer(m.cm));

        window._pendudukAll = entries.map(toListItem);
        window._pendudukVisible = visibleEntries.map(toListItem);
        window._pendudukList = window._pendudukVisible;

        console.log('[Penduduk] List updated, all:', window._pendudukAll.length, 'visible:', window._pendudukVisible.length);
        if (typeof window._statsUpdate === 'function') window._statsUpdate();
        if (typeof window._heatmapUpdate === 'function') window._heatmapUpdate();
        if (typeof window._updateBlankSpotCounter === 'function') window._updateBlankSpotCounter();
    }

    function refreshList() {
        const listEl   = document.getElementById('dlListPenduduk');
        const countEl  = document.getElementById('dlCountPenduduk');
        const headerEl = document.getElementById('dlHeaderPenduduk');
        if (!listEl) return;

        const entries = Object.values(allMarkers).filter(m => !m.cm || layerGroup.hasLayer(m.cm));
        const total   = entries.length;

        if (countEl) countEl.textContent = total;

        if (!total) {
            listEl.innerHTML = '<div class="dl-empty">Belum ada data.</div>';
            return;
        }

        const cats = [
            { key: 'Sangat Miskin', color: '#ef4444' },
            { key: 'Miskin',        color: '#f59e0b' },
            { key: 'Hampir Miskin', color: '#e3b341' },
        ];

        const counts = {}, jiwaMap = {};
        entries.forEach(m => {
            const k = m.data.kategori || 'Miskin';
            counts[k]  = (counts[k]  || 0) + 1;
            jiwaMap[k] = (jiwaMap[k] || 0) + (parseInt(m.data.jumlah_jiwa) || 1);
        });

        listEl.innerHTML = cats
            .filter(c => counts[c.key])
            .map(c => {
                const n = counts[c.key];
                const j = jiwaMap[c.key];
                return '<div class="dl-item" style="cursor:default;">' +
                    '<span class="dl-item-dot" style="background:' + c.color + ';"></span>' +
                    '<span class="dl-item-name" style="color:var(--sb-text);">' + c.key + '</span>' +
                    '<span class="dl-item-badge" style="color:' + c.color + ';border-color:' + c.color + '22;background:' + c.color + '11;">' +
                        n + ' KK · ' + j + ' jiwa' +
                    '</span>' +
                '</div>';
            }).join('');

        if (headerEl) headerEl.classList.add('open');
        listEl.classList.add('open');
    }

    // ── Hapus (soft delete — untuk data yang keliru diinput) ──────────
    window._pendudukHapus = function (id, btnEl) {
        showDeleteConfirm('Hapus data ini? Tindakan ini untuk data yang diinput salah.').then(confirmed => {
            if (!confirmed) return;
            btnEl.disabled    = true;
            btnEl.innerHTML = '<i data-lucide="loader" class="animate-spin" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> Menghapus...';
            lucide.createIcons();

            const fd = new FormData();
            fd.append('id', id);

            fetch('api/penduduk/hapus.php', { method: 'POST', body: appendCsrf(fd) })
                .then(r => r.json())
                .then(j => {
                    if (j.status === 'success') {
                        map.closePopup();
                        if (allMarkers[id]) {
                            layerGroup.removeLayer(allMarkers[id].cm);
                            layerGroup.removeLayer(allMarkers[id].dragHandle);
                            delete allMarkers[id];
                        }
                        updatePendudukList();
                        refreshList();
                        showToast('Data penduduk dihapus (soft delete).');
                    } else throw new Error(j.message);
                })
                .catch(err => {
                    showToast(err.message || 'Gagal menghapus.', 'error');
                    btnEl.disabled    = false;
                    btnEl.innerHTML = '<i data-lucide="trash-2" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> Hapus Data';
                    lucide.createIcons();
                });
        });
    };

    // ── Nonaktifkan (warga meninggal / pindah — arsip tetap ada) ─────
    window._pendudukNonaktifkan = function (id, btnEl) {
        showDeleteConfirm(
            'Tandai warga ini sebagai tidak aktif (meninggal/pindah)? Data akan disimpan untuk arsip.',
            { type: 'warn', iconName: 'user-x', title: 'Konfirmasi Nonaktifkan', btnLabel: 'Ya, Nonaktifkan' }
        ).then(confirmed => {
            if (!confirmed) return;
            btnEl.disabled    = true;
            btnEl.innerHTML = '<i data-lucide="loader" class="animate-spin" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> Memproses...';
            lucide.createIcons();

            const fd = new FormData();
            fd.append('id', id);

            fetch('api/penduduk/nonaktifkan.php', { method: 'POST', body: appendCsrf(fd) })
                .then(r => r.json())
                .then(j => {
                    if (j.status === 'success') {
                        map.closePopup();
                        if (allMarkers[id]) {
                            layerGroup.removeLayer(allMarkers[id].cm);
                            layerGroup.removeLayer(allMarkers[id].dragHandle);
                            delete allMarkers[id];
                        }
                        updatePendudukList();
                        refreshList();
                        showToast('Warga dinonaktifkan (arsip tersimpan).');
                    } else throw new Error(j.message);
                })
                .catch(err => {
                    showToast(err.message || 'Gagal.', 'error');
                    btnEl.disabled    = false;
                    btnEl.innerHTML = '<i data-lucide="user-x" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> Nonaktifkan';
                    lucide.createIcons();
                });
        });
    };

    // ── Klik peta: Form tambah penduduk ──────────────────────────────
    function onMapClick(e) {
        if (typeof activeTool === 'undefined' || activeTool !== 'penduduk') return;

        const lat = e.latlng.lat.toFixed(8);
        const lng = e.latlng.lng.toFixed(8);

        if (tempMarker) map.removeLayer(tempMarker);

        // Preview warna sebelum disimpan
        const ibadahPreview = findNearestIbadah(parseFloat(lat), parseFloat(lng));
        const colorPreview  = ibadahPreview ? COLOR_TERJANGKAU : COLOR_BLANK_SPOT;
        const statusPreview = ibadahPreview
            ? `<span style="color:#22c55e;display:inline-flex;align-items:center;gap:4px;"><i data-lucide="check" style="width:12px;height:12px;"></i> Dalam radius <b>${escapeHTML(ibadahPreview.nama)}</b> (${ibadahPreview.jarak}m)</span>`
            : `<span style="color:#ef4444;display:inline-flex;align-items:center;gap:4px;"><i data-lucide="alert-triangle" style="width:12px;height:12px;"></i> Di luar semua radius ibadah (Blank Spot)</span>`;

        tempMarker = L.circleMarker([lat, lng], {
            radius: 10, color: colorPreview, fillColor: colorPreview, fillOpacity: 0.4, weight: 2
        }).addTo(map);

        L.popup({ maxWidth: 300, closeOnClick: false })
            .setLatLng(e.latlng)
            .setContent(`<div class="form-popup">
                <div class="form-popup-header">
                    <div class="form-popup-icon" style="background:rgba(34,197,94,.12);"><i data-lucide="home"></i></div>
                    <div>
                        <div class="form-popup-title">Tambah Penduduk Miskin</div>
                        <div class="form-popup-coords">${lat}, ${lng}</div>
                    </div>
                </div>
                <div style="padding:8px 12px;background:rgba(255,255,255,0.04);border-radius:7px;font-size:12px;margin-bottom:14px;color:var(--c-muted);">
                    ${statusPreview}
                </div>
                <div class="form-group">
                    <label>Nama Kepala Keluarga *</label>
                    <input type="text" id="f_pddk_nama" placeholder="cth. Budi Santoso" maxlength="150" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>NIK</label>
                    <input type="text" id="f_pddk_nik" placeholder="16 digit NIK" maxlength="16" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select id="f_pddk_kategori">
                        <option value="Sangat Miskin">Sangat Miskin</option>
                        <option value="Miskin" selected>Miskin</option>
                        <option value="Hampir Miskin">Hampir Miskin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah Jiwa</label>
                    <input type="number" id="f_pddk_jiwa" value="1" min="1" max="99">
                </div>
                <div class="form-group">
                    <label>Alamat (Otomatis)</label>
                    <textarea id="f_pddk_alamat" placeholder="Mendeteksi alamat..." rows="2">Mendeteksi alamat...</textarea>
                </div>
                <div class="form-group">
                    <label>Catatan Kondisi Khusus</label>
                    <textarea id="f_pddk_catatan" placeholder="Kebutuhan khusus, kondisi rumah, dll." rows="2"
                        style="width:100%;padding:8px 12px;background:var(--c-bg);border:1px solid var(--c-border);
                               border-radius:7px;color:var(--c-text);font-family:var(--font-body);font-size:13px;
                               outline:none;resize:vertical;"></textarea>
                </div>
                <input type="hidden" id="f_pddk_lat" value="${lat}">
                <input type="hidden" id="f_pddk_lng" value="${lng}">
                <input type="hidden" id="f_pddk_ibadah_id" value="${ibadahPreview ? ibadahPreview.id : ''}">
                <button class="btn-save" id="btnSimpanPenduduk" onclick="window._pendudukSimpan()"
                    style="background:linear-gradient(135deg,#16a34a,#22c55e);display:inline-flex;align-items:center;justify-content:center;gap:4px;">
                    <i data-lucide="save" style="width:14px;height:14px;"></i> Simpan Penduduk
                </button>
                <div class="form-status" id="pendudukStatus"></div>
            </div>`)
            .openOn(map);

        // Hapus temp marker saat popup dibatalkan/ditutup
        const _markerThisClick = tempMarker;
        map.once('popupclose', function () {
            if (_markerThisClick) { try { map.removeLayer(_markerThisClick); } catch(_){} }
            if (tempMarker === _markerThisClick) tempMarker = null;
        });

        // Reverse Geocoding via Nominatim
        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=id`, {
            headers: { 'Accept-Language': 'id' }
        })
            .then(r => r.json())
            .then(geo => {
                const alamat = geo.display_name || '';
                const el = document.getElementById('f_pddk_alamat');
                if (el) el.value = alamat;
            })
            .catch(() => {
                const el = document.getElementById('f_pddk_alamat');
                if (el) el.value = '';
            });

        setTimeout(() => { const el = document.getElementById('f_pddk_nama'); if (el) el.focus(); }, 200);
    }

    // ── Simpan ────────────────────────────────────────────────────────
    window._pendudukSimpan = function () {
        const nama      = document.getElementById('f_pddk_nama').value.trim();
        const nik       = document.getElementById('f_pddk_nik').value.trim();
        const kategori  = document.getElementById('f_pddk_kategori').value;
        const alamat    = document.getElementById('f_pddk_alamat').value.trim();
        const jiwa      = parseInt(document.getElementById('f_pddk_jiwa').value) || 1;
        const lat       = document.getElementById('f_pddk_lat').value;
        const lng       = document.getElementById('f_pddk_lng').value;
        const ibadahId  = document.getElementById('f_pddk_ibadah_id').value;
        const status    = document.getElementById('pendudukStatus');
        const btn       = document.getElementById('btnSimpanPenduduk');

        if (!nama) {
            status.textContent = '⚠ Nama kepala keluarga wajib diisi.';
            status.className   = 'form-status error';
            document.getElementById('f_pddk_nama').focus();
            return;
        }

        btn.disabled    = true;
        btn.innerHTML = '<i data-lucide="loader" class="animate-spin" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> Menyimpan...';
        lucide.createIcons();

        const fd = new FormData();
        fd.append('nama_kk',     nama);
        fd.append('nik',         nik);
        fd.append('kategori',    kategori);
        fd.append('alamat',      alamat);
        fd.append('catatan',     document.getElementById('f_pddk_catatan')?.value?.trim() || '');
        fd.append('jumlah_jiwa', jiwa);
        fd.append('lat',         lat);
        fd.append('lng',         lng);
        fd.append('ibadah_id',   ibadahId);

        fetch('api/penduduk/simpan.php', { method: 'POST', body: appendCsrf(fd) })
            .then(r => r.json())
            .then(j => {
                if (j.status === 'success') {
                    map.closePopup();
                    if (tempMarker) { map.removeLayer(tempMarker); tempMarker = null; }
                    addToMap(j.data);
                    updatePendudukList();
                    refreshList();
                    if (j.data.is_blank_spot) {
                        showToast(`⚠ "${escapeHTML(nama)}" ditambahkan sebagai BLANK SPOT — tidak terjangkau ibadah manapun.`, 'error');
                    } else {
                        showToast(`"${escapeHTML(nama)}" berhasil ditambahkan!`);
                    }
                } else throw new Error(j.message);
            })
            .catch(err => {
                status.innerHTML = '<i data-lucide="x" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i> ' + escapeHTML(err.message);
                status.className   = 'form-status error';
                btn.disabled       = false;
                btn.innerHTML = '<i data-lucide="save" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i> Simpan Penduduk';
                lucide.createIcons();
            });
    };

    // ── Update status bantuan ─────────────────────────────────────────
    window._updateStatus = function (id, newStatus, btnEl) {
        btnEl.disabled = true;
        const catatan = prompt('Catatan perubahan status (opsional):', '');
        if (catatan === null) {
            btnEl.disabled = false;
            return;
        }

        const fd = new FormData();
        fd.append('id',     id);
        fd.append('status', newStatus);
        fd.append('catatan', catatan);

        fetch('api/penduduk/update_status.php', { method: 'POST', body: appendCsrf(fd) })
            .then(r => r.json())
            .then(j => {
                if (j.status !== 'success') throw new Error(j.message);
                const entry = allMarkers[id];
                if (entry && entry.cm) {
                    entry.data.status_bantuan = newStatus;
                    const ibadah = entry.cm._nearestIbadah;
                    entry.cm.bindPopup(buildInfoPopup(entry.data, ibadah), { maxWidth: 280 });
                    entry.cm.openPopup();
                }
                updatePendudukList();
                refreshList();
                if (typeof window.dispatchDataChanged === 'function') {
                    window.dispatchDataChanged('penduduk', { id, mutation: 'status_bantuan' });
                }
                showToast('Status bantuan diperbarui.');
            })
            .catch(err => {
                btnEl.disabled = false;
                showToast(err.message || 'Gagal memperbarui status.', 'error');
            });
    };

    // ── Lihat riwayat bantuan ─────────────────────────────────────────
    window._showRiwayat = function (id) {
        fetch('api/penduduk/riwayat.php?id=' + id + '&_=' + Date.now(), { cache: 'no-store' })
            .then(r => r.json())
            .then(j => {
                if (j.status !== 'success') throw new Error(j.message);
                const rows = j.data;

                const tbody = rows.length === 0
                    ? '<tr><td colspan="4" style="text-align:center;padding:12px;color:var(--c-muted);">Belum ada riwayat.</td></tr>'
                    : rows.map(r => {
                        const lama = r.status_lama || '—';
                        const baru = r.status_baru || '—';
                        const tgl  = r.created_at ? r.created_at.substring(0, 16) : '—';
                        return `<tr style="border-bottom:1px solid var(--c-border);">
                            <td style="padding:6px 8px;font-size:12px;">${escapeHTML(tgl)}</td>
                            <td style="padding:6px 8px;font-size:12px;color:${STATUS_COLOR[baru]||'#ccc'};font-weight:700;">${escapeHTML(baru)}</td>
                            <td style="padding:6px 8px;font-size:12px;color:var(--c-muted);">${escapeHTML(lama)}</td>
                            <td style="padding:6px 8px;font-size:12px;color:var(--c-muted);">${escapeHTML(r.user_nama||'—')}</td>
                        </tr>`;
                    }).join('');

                let overlay = document.getElementById('_riwayatOverlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.id = '_riwayatOverlay';
                    overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;';
                    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
                    document.body.appendChild(overlay);
                }
                overlay.innerHTML = `
                    <div style="background:var(--c-surface);border:1px solid var(--c-border);border-radius:12px;
                                padding:20px;width:min(480px,94vw);max-height:80vh;overflow-y:auto;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                            <div style="font-weight:700;font-size:14px;color:var(--c-text);">Riwayat Bantuan</div>
                            <button onclick="document.getElementById('_riwayatOverlay').remove()"
                                style="background:none;border:none;color:var(--c-muted);font-size:18px;cursor:pointer;">×</button>
                        </div>
                        <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="border-bottom:1px solid var(--c-border);">
                                    <th style="text-align:left;padding:6px 8px;font-size:11px;color:var(--c-muted);font-weight:600;">Waktu</th>
                                    <th style="text-align:left;padding:6px 8px;font-size:11px;color:var(--c-muted);font-weight:600;">Status Baru</th>
                                    <th style="text-align:left;padding:6px 8px;font-size:11px;color:var(--c-muted);font-weight:600;">Sebelumnya</th>
                                    <th style="text-align:left;padding:6px 8px;font-size:11px;color:var(--c-muted);font-weight:600;">Oleh</th>
                                </tr>
                            </thead>
                            <tbody>${tbody}</tbody>
                        </table>
                        </div>
                    </div>`;
            })
            .catch(err => showToast(err.message || 'Gagal memuat riwayat.', 'error'));
    };

    // ── Blank Spot counter + filter ───────────────────────────────────
    window._updateBlankSpotCounter = function () {
        const count = Object.values(allMarkers).filter(m => m.cm && !m.cm._nearestIbadah).length;
        const btn   = document.getElementById('btnBlankSpot');
        const lbl   = document.getElementById('blankSpotCount');
        if (!btn || !lbl) return;
        lbl.textContent    = count + ' Blank Spot';
        btn.style.display  = count > 0 ? '' : 'none';
    };

    let _blankSpotFilterActive = false;
    window._filterBlankSpot = function () {
        _blankSpotFilterActive = !_blankSpotFilterActive;
        const btn = document.getElementById('btnBlankSpot');
        if (btn) btn.style.background = _blankSpotFilterActive ? 'rgba(248,81,73,.15)' : 'transparent';

        Object.values(allMarkers).forEach(m => {
            if (!m.cm) return; // publik — tidak ada marker
            const show = !_blankSpotFilterActive || !m.cm._nearestIbadah;
            if (show) {
                if (!layerGroup.hasLayer(m.cm)) {
                    m.cm.addTo(layerGroup);
                    m.dragHandle.addTo(layerGroup);
                }
            } else {
                layerGroup.removeLayer(m.cm);
                layerGroup.removeLayer(m.dragHandle);
            }
        });
        updatePendudukList();
        refreshList();
    };

    // ── Reload dari server (dipanggil setelah recalculate massal) ────
    window._pendudukReload = function () {
        loadData();
    };

    // ── FlyTo penduduk marker (dipanggil dari popup ibadah KK list) ───
    window._pendudukFlyTo = function (id) {
        const entry = allMarkers[id];
        if (!entry || !entry.cm) return; // publik tidak punya marker
        map.closePopup();
        map.flyTo(entry.cm.getLatLng(), Math.max(map.getZoom(), 17), { animate: true, duration: 0.8 });
        setTimeout(() => entry.cm.openPopup(), 900);
    };

    // ── Verifikasi warga (admin only) ────────────────────────────────
    window._verifikasiPenduduk = function (id, aksi, btnEl) {
        const entry = allMarkers[id];
        const label = aksi === 'approve' ? 'Terverifikasi' : 'Ditolak';
        const nama = entry?.data?.nama_kk ? `"${entry.data.nama_kk}"` : `#${id}`;
        const title = aksi === 'approve' ? 'Konfirmasi Verifikasi' : 'Konfirmasi Tolak';
        const msg = `Ubah status verifikasi ${nama} menjadi <strong>${label}</strong>?`;
        showDeleteConfirm(
            `Ubah status verifikasi ${entry?.data?.nama_kk || '#'+id} menjadi ${label}?`,
            {
                type: 'warn',
                iconName: aksi === 'approve' ? 'check-circle' : 'x-circle',
                title,
                btnLabel: aksi === 'approve' ? 'Ya, Verifikasi' : 'Ya, Tolak'
            }
        ).then(confirmed => {
            if (!confirmed) return;
            if (btnEl) {
                btnEl.disabled = true;
                btnEl.innerHTML = '<i data-lucide="loader" class="animate-spin" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i>';
                lucide.createIcons();
            }
            const fd = new FormData();
            fd.append('id', id);
            fd.append('aksi', aksi);
            fetch('api/penduduk/verifikasi.php', { method: 'POST', body: appendCsrf(fd) })
                .then(r => r.json())
                .then(j => {
                    if (j.status !== 'success') throw new Error(j.message);
                    const lbl = aksi === 'approve' ? 'Terverifikasi' : 'Ditolak';
                    showToast(`Data #${id} → ${lbl}`, 'success');
                    if (entry) {
                        entry.data.status_verifikasi = j.status_verifikasi;
                        if (entry.cm) {
                            const ibadah = entry.cm._nearestIbadah;
                            entry.cm.bindPopup(buildInfoPopup(entry.data, ibadah), { maxWidth: 280 });
                            entry.cm.openPopup();
                        }
                    }
                    refreshList();
                })
                .catch(err => {
                    showToast(err.message || 'Gagal memperbarui verifikasi.', 'error');
                    if (btnEl) btnEl.disabled = false;
                });
        });
    };

    // ── Init ─────────────────────────────────────────────────────────
    window.initPenduduk = function () {
        if (active) { loadData(); return; }
        active = true;

        layerGroup.addTo(map);
        map.on('click', onMapClick);

        window._dlFocusFns['Penduduk'] = function (id) {
            const entry = allMarkers[id];
            if (!entry || !entry.cm) return; // publik tidak punya marker
            map.setView(entry.cm.getLatLng(), Math.max(map.getZoom(), 17), { animate: true });
            setTimeout(() => entry.cm.openPopup(), 350);
        };

        loadData();
    };

    // ── Toggle visibilitas layer ──────────────────────────────────────
    window._pendudukToggleLayer = function (visible) {
        if (visible) {
            if (!map.hasLayer(layerGroup)) map.addLayer(layerGroup);
        } else {
            map.removeLayer(layerGroup);
            if (tempMarker) { map.removeLayer(tempMarker); tempMarker = null; }
        }
    };
})();
