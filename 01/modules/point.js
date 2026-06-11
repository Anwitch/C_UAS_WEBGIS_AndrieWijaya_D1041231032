// modules/point.js — Logika Pertemuan 1: Point of Interest (POI)
// Dipanggil oleh index.php saat mode = 1

(function () {
    // ── Layer group ──────────────────────────────────
    const layerGroup = L.layerGroup();
    let allMarkers   = {}; // { id: markerObject }
    let tempMarker   = null;
    let active       = false;

    // ── Custom Icons ─────────────────────────────────
    function createIcon(color = '#2ea043') {
        return L.divIcon({
            className: '',
            html: `<svg width="28" height="38" viewBox="0 0 28 38" xmlns="http://www.w3.org/2000/svg">
                <filter id="sh"><feDropShadow dx="0" dy="2" stdDeviation="2" flood-opacity=".4"/></filter>
                <path d="M14 0C6.268 0 0 6.268 0 14C0 24.5 14 38 14 38C14 38 28 24.5 28 14C28 6.268 21.732 0 14 0Z"
                      fill="${color}" filter="url(#sh)"/>
                <circle cx="14" cy="14" r="6" fill="white" opacity=".9"/>
            </svg>`,
            iconSize: [28, 38],
            iconAnchor: [14, 38],
            popupAnchor: [0, -40]
        });
    }

    const iconDefault = createIcon('#2ea043');
    const iconClosed  = createIcon('#f85149');
    const iconTemp    = createIcon('#e3b341');

    // ── Render marker on map ─────────────────────────
    function addMarkerToMap(poi) {
        const isOpen = Number(poi.buka_24jam) === 1;
        const marker = L.marker(
            [poi.latitude, poi.longitude],
            { icon: isOpen ? iconDefault : iconClosed, draggable: true, pane: 'pointPane' }
        );

        marker._poiId   = poi.id;
        marker._poiNama = poi.nama_tempat;
        marker._poiBuka = Number(poi.buka_24jam) === 1;
        allMarkers[poi.id] = marker;

        // Drag to update position
        marker.on('dragend', function (e) {
            const { lat, lng } = e.target.getLatLng();
            const fd = new FormData();
            fd.append('id', poi.id);
            fd.append('latitude',  lat.toFixed(8));
            fd.append('longitude', lng.toFixed(8));

            fetch('api/point/update_posisi.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(j => {
                    if (j.status === 'success') showToast(`Posisi "${escapeHTML(poi.nama_tempat)}" diperbarui!`);
                    else throw new Error(j.message);
                })
                .catch(err => showToast(err.message || 'Gagal update posisi.', 'error'));
        });

        // Popup info
        const noWa = poi.no_wa ? poi.no_wa.replace(/\D/g, '') : '';
        const waBtn = noWa
            ? `<a class="btn-wa" href="https://wa.me/${noWa}" target="_blank" rel="noopener"
                   style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;margin-top:14px;padding:9px;background:#25d366;color:#fff;border:none;border-radius:7px;font-family:var(--font-body);font-size:13px;font-weight:700;text-decoration:none;">
                💬 Chat WhatsApp
               </a>`
            : `<div style="font-size:12px;color:var(--c-muted);text-align:center;padding:8px 0;margin-top:8px;">Tidak ada nomor WA</div>`;

        const buka24Badge = isOpen
            ? `<span class="status-badge-inline" style="background:rgba(46,160,67,.2);color:#3fb950;border:1px solid rgba(46,160,67,.35);">🟢 Buka 24 Jam</span>`
            : `<span class="status-badge-inline" style="background:rgba(248,81,73,.12);color:#f85149;border:1px solid rgba(248,81,73,.3);">🔴 Tidak 24 Jam</span>`;

        marker.bindPopup(`
            <div class="info-popup">
                <div class="info-popup-header">
                    <div class="info-popup-icon">📍</div>
                    <div>
                        <div class="info-popup-name">${escapeHTML(poi.nama_tempat)}</div>
                        <div class="info-popup-id">#${poi.id}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-row-icon">📞</div>
                    <div>
                        <div class="info-row-label">No. WhatsApp</div>
                        <div class="info-row-value">${poi.no_wa ? escapeHTML(poi.no_wa) : '—'}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-row-icon">🕐</div>
                    <div>
                        <div class="info-row-label">Jam Operasional</div>
                        <div class="info-row-value">${buka24Badge}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-row-icon">🌐</div>
                    <div>
                        <div class="info-row-label">Koordinat</div>
                        <div class="info-row-value" style="font-family:var(--font-mono);font-size:11px;">
                            ${parseFloat(poi.latitude).toFixed(6)}, ${parseFloat(poi.longitude).toFixed(6)}
                        </div>
                    </div>
                </div>
                ${waBtn}
                <button class="btn-hapus" onclick="window._pointHapus(${poi.id}, this)">🗑 Hapus Lokasi</button>
            </div>
        `, { maxWidth: 300 });

        layerGroup.addLayer(marker);
    }

    // ── Load semua POI dari server ──────────────────
    function loadData() {
        layerGroup.clearLayers();
        allMarkers = {};

        fetch('api/point/ambil.php?_=' + Date.now(), { cache: 'no-store' })
            .then(r => r.json())
            .then(j => {
                if (j.status !== 'success') throw new Error(j.message);
                updateCount(j.total, '📍');
                j.data.forEach(addMarkerToMap);
                refreshPointList(j.data);
            })
            .catch(err => {
                showToast('Gagal memuat data POI.', 'error');
                console.error(err);
            });
    }

    // ── Refresh panel list Lokasi Usaha ──────────────────
    function refreshPointList(data) {
        if (typeof window.dlRefreshList !== 'function') return;
        const items = (data || []).map(poi => ({
            id: poi.id,
            name: poi.nama_tempat,
            badge: Number(poi.buka_24jam) === 1 ? '24 Jam' : 'Non-24',
            badgeColor: Number(poi.buka_24jam) === 1 ? '#2ea043' : '#f85149',
            dotColor: Number(poi.buka_24jam) === 1 ? '#2ea043' : '#f85149'
        }));
        window.dlRefreshList('Point', items);
    }

    // ── Hapus marker ────────────────────────────────────
    window._pointHapus = function (id, btnEl) {
        showDeleteConfirm('Yakin ingin menghapus lokasi ini?').then(confirmed => {
            if (!confirmed) return;
            btnEl.disabled = true;
            btnEl.textContent = '⏳ Menghapus...';

            const fd = new FormData();
            fd.append('id', id);

            fetch('api/point/hapus.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(j => {
                    if (j.status === 'success') {
                        map.closePopup();
                        if (allMarkers[id]) { layerGroup.removeLayer(allMarkers[id]); delete allMarkers[id]; }
                        updateCount(Math.max(0, Object.keys(allMarkers).length), '📍');
                        // rebuild list dari allMarkers (ambil nama dari marker)
                        const remaining = Object.keys(allMarkers).map(k => ({
                            id: k,
                            name: allMarkers[k]._poiNama || ('#' + k),
                            badge: allMarkers[k]._poiBuka ? '24 Jam' : 'Non-24',
                            badgeColor: allMarkers[k]._poiBuka ? '#2ea043' : '#f85149',
                            dotColor: allMarkers[k]._poiBuka ? '#2ea043' : '#f85149'
                        }));
                        if (typeof window.dlRefreshList === 'function') window.dlRefreshList('Point', remaining);
                        showToast('Lokasi berhasil dihapus.');
                    } else throw new Error(j.message);
                })
                .catch(err => {
                    showToast(err.message || 'Gagal menghapus.', 'error');
                    btnEl.disabled = false;
                    btnEl.textContent = '🗑 Hapus Lokasi';
                });
        });
    };

    // ── Klik peta = form tambah POI ──────────────────
    function onMapClick(e) {
        if (currentMode !== 1) return;

        const lat = e.latlng.lat.toFixed(8);
        const lng = e.latlng.lng.toFixed(8);

        if (tempMarker) map.removeLayer(tempMarker);
        tempMarker = L.marker([lat, lng], { icon: iconTemp, pane: 'drawPane' }).addTo(map);

        L.popup({ maxWidth: 320, closeOnClick: false })
            .setLatLng(e.latlng)
            .setContent(`
                <div class="form-popup">
                    <div class="form-popup-header">
                        <div class="form-popup-icon">➕</div>
                        <div>
                            <div class="form-popup-title">Tambah Lokasi Baru</div>
                            <div class="form-popup-coords">${lat}, ${lng}</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nama Tempat *</label>
                        <input type="text" id="f_nama" placeholder="cth. Warung Kopi Aroma" maxlength="100" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>No. WhatsApp</label>
                        <input type="text" id="f_wa" placeholder="628xxxxxxxxxx" maxlength="15" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Buka 24 Jam?</label>
                        <select id="f_buka24">
                            <option value="0">❌ Tidak</option>
                            <option value="1">✅ Ya</option>
                        </select>
                    </div>
                    <input type="hidden" id="f_lat" value="${lat}">
                    <input type="hidden" id="f_lng" value="${lng}">
                    <button class="btn-save" id="btnSimpanPoi" onclick="window._pointSimpan()">💾 Simpan Lokasi</button>
                    <div class="form-status" id="poiStatus"></div>
                </div>
            `)
            .openOn(map);

        setTimeout(() => { const el = document.getElementById('f_nama'); if (el) el.focus(); }, 200);
    }

    // ── Simpan POI ────────────────────────────────────
    window._pointSimpan = function () {
        const nama   = document.getElementById('f_nama').value.trim();
        const wa     = document.getElementById('f_wa').value.trim();
        const buka24 = document.getElementById('f_buka24').value;
        const lat    = document.getElementById('f_lat').value;
        const lng    = document.getElementById('f_lng').value;
        const status = document.getElementById('poiStatus');
        const btn    = document.getElementById('btnSimpanPoi');

        if (!nama) {
            status.textContent = '⚠ Nama tempat wajib diisi.';
            status.className = 'form-status error';
            document.getElementById('f_nama').focus();
            return;
        }

        btn.disabled = true;
        btn.textContent = '⏳ Menyimpan...';

        const fd = new FormData();
        fd.append('nama_tempat', nama);
        fd.append('no_wa',       wa);
        fd.append('buka_24jam',  buka24);
        fd.append('latitude',    lat);
        fd.append('longitude',   lng);

        fetch('api/point/simpan.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(j => {
                if (j.status === 'success') {
                    map.closePopup();
                    if (tempMarker) { map.removeLayer(tempMarker); tempMarker = null; }
                    addMarkerToMap(j.data);
                    updateCount(Object.keys(allMarkers).length, '📍');
                    // Tambahkan item baru ke list
                    const allItems = Object.values(allMarkers).map(m => ({
                        id: m._poiId,
                        name: m._poiNama || ('#' + m._poiId),
                        badge: m._poiBuka ? '24 Jam' : 'Non-24',
                        badgeColor: m._poiBuka ? '#2ea043' : '#f85149',
                        dotColor: m._poiBuka ? '#2ea043' : '#f85149'
                    }));
                    if (typeof window.dlRefreshList === 'function') window.dlRefreshList('Point', allItems);
                    showToast(`"${escapeHTML(nama)}" berhasil disimpan!`);
                } else throw new Error(j.message);
            })
            .catch(err => {
                status.textContent = '✕ ' + err.message;
                status.className = 'form-status error';
                btn.disabled = false;
                btn.textContent = '💾 Simpan Lokasi';
            });
    };

    // ── Init / cleanup ────────────────────────────────────
    window.initPoint = function () {
        if (active) { /* already added to map, just refresh data if needed */ loadData(); return; }
        active = true;

        layerGroup.addTo(map);
        map.on('click', onMapClick);

        // Daftarkan fungsi focus untuk panel list
        window._dlFocusFns['Point'] = function(id) {
            const marker = allMarkers[id];
            if (!marker) return;
            map.setView(marker.getLatLng(), Math.max(map.getZoom(), 17), { animate: true });
            setTimeout(() => marker.openPopup(), 350);
        };

        loadData();
    };

    // Filter Toggle
    window._pointToggleLayer = function(visible) {
        if (visible) {
            map.addLayer(layerGroup);
        } else {
            map.removeLayer(layerGroup);
            // Juga tutup popup & bersihkan titik sementara jika ada
            if (tempMarker) { map.removeLayer(tempMarker); tempMarker = null; }
        }
    };

})();
