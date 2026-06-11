// modules/parsil.js — Logika Pertemuan 2: Manajemen Parsil Tanah (Polygon)

(function () {
    const layerGroup = L.layerGroup();
    let allLayers = {}; // { id: polygonObject }
    let active = false;

    // ── Warna per status kepemilikan ──────────────────
    const PARSIL_COLORS = {
        'SHM': '#3b82f6',
        'HGB': '#8b5cf6',
        'HGU': '#ec4899',
        'HP':  '#14b8a6'
    };

    function getColor(status) {
        return PARSIL_COLORS[status] || '#888';
    }

    // ── Hitung Luas Polygon (Spherical Area) ──────────
    function calculatePolygonArea(latLngs) {
        let radius = 6378137; // Earth's radius in meters
        let area = 0;
        let d2r = Math.PI / 180;
        
        if (latLngs.length > 2) {
            for (let i = 0; i < latLngs.length; i++) {
                let p1 = latLngs[i];
                let p2 = latLngs[(i + 1) % latLngs.length];
                area += ((p2.lng - p1.lng) * d2r) * (2 + Math.sin(p1.lat * d2r) + Math.sin(p2.lat * d2r));
            }
            area = area * radius * radius / 2.0;
        }
        return Math.abs(area);
    }

    // ── Render polygon ────────────────────────────────
    function addPolygonToMap(row) {
        let coords;
        try {
            const gj = typeof row.geojson === 'string' ? JSON.parse(row.geojson) : row.geojson;
            // GeoJSON Polygon coordinates are MultiArray: [[[lng, lat], [lng, lat]...]]
            // We just need the exterior ring [0] maps to latlng
            let extRing = gj.coordinates[0];
            coords = extRing.map(c => [c[1], c[0]]);
        } catch (e) {
            console.error('GeoJSON parse error (parsil id=' + row.id + ')', e);
            return;
        }

        const poly = L.polygon(coords, {
            color: getColor(row.status_kepemilikan),
            fillColor: getColor(row.status_kepemilikan),
            weight: 2,
            fillOpacity: 0.4,
            pane: 'parsilPane'
        });

        poly._parsilId     = row.id;
        poly._parsilNama   = row.nama_parsil;
        poly._parsilStatus = row.status_kepemilikan;
        allLayers[row.id]  = poly;

        poly.bindPopup(buildInfoPopup(row), { maxWidth: 300 });
        layerGroup.addLayer(poly);
    }

    function buildInfoPopup(row) {
        const color = getColor(row.status_kepemilikan);
        const luas = row.luas_m2
            ? parseFloat(row.luas_m2).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 }) + ' m²'
            : '—';

        return `
            <div class="info-popup">
                <div class="info-popup-header">
                    <div class="info-popup-icon" style="background:${color}22;">🏘️</div>
                    <div>
                        <div class="info-popup-name">${escapeHTML(row.nama_parsil)}</div>
                        <div class="info-popup-id">#${row.id}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-row-icon">📜</div>
                    <div>
                        <div class="info-row-label">Status Kepemilikan</div>
                        <div class="info-row-value">
                            <span class="status-badge-inline"
                                  style="background:${color}22;color:${color};border:1px solid ${color}55;">
                                ${escapeHTML(row.status_kepemilikan)}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-row-icon">📐</div>
                    <div>
                        <div class="info-row-label">Luas Tanah</div>
                        <div class="info-row-value" style="font-family:var(--font-mono);">${luas}</div>
                    </div>
                </div>
                <button class="btn-hapus" onclick="window._parsilHapus(${row.id}, this)">🗑 Hapus Parsil</button>
            </div>
        `;
    }

    // ── Load data ───────────────────────────────────────────────────
    function loadData() {
        layerGroup.clearLayers();
        allLayers = {};

        fetch('api/parsil/ambil.php?_=' + Date.now(), { cache: 'no-store' })
            .then(r => r.json())
            .then(j => {
                if (j.status !== 'success') throw new Error(j.message);
                updateCount(j.total, '🏘️');
                j.data.forEach(addPolygonToMap);
                refreshParsilList(j.data);
            })
            .catch(err => { showToast('Gagal memuat data parsil.', 'error'); console.error(err); });
    }

    // ── Refresh panel list Parsil Tanah ─────────────────────────────────
    function refreshParsilList(data) {
        if (typeof window.dlRefreshList !== 'function') return;
        const items = (data || []).map(row => ({
            id: row.id,
            name: row.nama_parsil,
            badge: row.status_kepemilikan,
            badgeColor: getColor(row.status_kepemilikan),
            dotColor:   getColor(row.status_kepemilikan)
        }));
        window.dlRefreshList('Parsil', items);
    }

    // ── Hapus parsil ──────────────────────────────────
    window._parsilHapus = function (id, btnEl) {
        showDeleteConfirm('Yakin ingin menghapus data parsil tanah ini?').then(confirmed => {
            if (!confirmed) return;
            btnEl.disabled = true;
            btnEl.textContent = '⏳ Menghapus...';

            const fd = new FormData();
            fd.append('id', id);

            fetch('api/parsil/hapus.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(j => {
                    if (j.status === 'success') {
                        map.closePopup();
                        if (allLayers[id]) { layerGroup.removeLayer(allLayers[id]); delete allLayers[id]; }
                        updateCount(Object.keys(allLayers).length, '🏘️');
                        const remaining = Object.values(allLayers).map(l => ({
                            id: l._parsilId, name: l._parsilNama,
                            badge: l._parsilStatus,
                            badgeColor: getColor(l._parsilStatus),
                            dotColor:   getColor(l._parsilStatus)
                        }));
                        if (typeof window.dlRefreshList === 'function') window.dlRefreshList('Parsil', remaining);
                        showToast('Data parsil berhasil dihapus.');
                    } else throw new Error(j.message);
                })
                .catch(err => {
                    showToast(err.message || 'Gagal menghapus.', 'error');
                    btnEl.disabled = false;
                    btnEl.textContent = '🗑 Hapus Parsil';
                });
        });
    };

    // ── Drawing: gambar polygon baru ──────────────────
    let drawingPoints = [];
    let drawingLayer = null; // polyline/polygon sementara
    let liveLineLayer = null; // polyline preview kursor
    let liveTooltip = null;   // tooltip info ukuran
    let drawingMarkers = [];  // titik-titik sementara
    let isDrawing = false;
    let lastClickAt = 0;

    function startDrawing() {
        isDrawing = true;
        drawingPoints = [];
        lastClickAt = 0;
        document.getElementById('drawHint').classList.add('visible');
        map.doubleClickZoom.disable();
    }

    function stopDrawing(save) {
        isDrawing = false;
        document.getElementById('drawHint').classList.remove('visible');
        map.doubleClickZoom.enable();

        // Bersihkan preview
        if (drawingLayer) { map.removeLayer(drawingLayer); drawingLayer = null; }
        if (liveLineLayer) { map.removeLayer(liveLineLayer); liveLineLayer = null; }
        if (liveTooltip) { map.removeLayer(liveTooltip); liveTooltip = null; }
        drawingMarkers.forEach(m => map.removeLayer(m));
        drawingMarkers = [];

        if (save && drawingPoints.length >= 3) {
            showFormParsil(drawingPoints.slice());
        } else if (save && drawingPoints.length < 3) {
            showToast('Minimal 3 titik untuk membuat parsil (Polygon)', 'error');
        }
        drawingPoints = [];

        if (typeof window._resetActiveTool === 'function') window._resetActiveTool();
    }

    function finishDrawing() {
        if (!isDrawing) return;
        if (drawingPoints.length >= 3) {
            stopDrawing(true);
        } else {
            showToast('Minimal 3 titik untuk membuat parsil (Polygon)', 'error');
        }
    }

    function onMapClick(e) {
        if (currentMode !== 2 || currentSubMode !== 'parsil') return;
        if (!isDrawing) return;

        const now = Date.now();
        // Deteksi double-click: klik kedua dalam 350ms → finalisasi tanpa menambah titik
        if (now - lastClickAt < 350) {
            lastClickAt = 0;
            finishDrawing();
            return;
        }
        lastClickAt = now;

        const latlng = e.latlng;
        drawingPoints.push(latlng);

        // Titik visual
        const m = L.circleMarker(latlng, { radius: 5, color: '#eab308', fillColor: '#eab308', fillOpacity: 1, pane: 'drawPane' });
        m.addTo(map);
        drawingMarkers.push(m);

        // Preview polygon
        if (drawingLayer) map.removeLayer(drawingLayer);
        if (drawingPoints.length >= 3) {
            drawingLayer = L.polygon(drawingPoints, { color: '#eab308', weight: 2, fillOpacity: 0.3, pane: 'drawPane' }).addTo(map);
        } else if (drawingPoints.length === 2) {
            drawingLayer = L.polyline(drawingPoints, { color: '#eab308', weight: 2, dashArray: '6,4', opacity: 0.7, pane: 'drawPane' }).addTo(map);
        }
    }

    function onMapDoubleClick(e) {
        if (currentMode !== 2 || currentSubMode !== 'parsil' || !isDrawing) return;
        if (e.originalEvent) L.DomEvent.stop(e.originalEvent);
        finishDrawing();
    }

    function onMouseMove(e) {
        if (currentMode !== 2 || currentSubMode !== 'parsil' || !isDrawing) return;
        if (drawingPoints.length === 0) {
            if (liveTooltip) { map.removeLayer(liveTooltip); liveTooltip = null; }
            return;
        }

        const lastPoint = drawingPoints[drawingPoints.length - 1];
        const currentPoint = e.latlng;

        // Gambar garis tipis preview dari titik terakhir ke cursor (dan dari kursor ke titik awal jika >= 2 titik)
        if (liveLineLayer) map.removeLayer(liveLineLayer);
        
        const previewCoords = [lastPoint, currentPoint];
        if (drawingPoints.length >= 2) {
            previewCoords.push(drawingPoints[0]); // Garis penutup bayangan
        }

        liveLineLayer = L.polyline(previewCoords, {
            color: '#eab308', weight: 2, dashArray: '5,6', opacity: 0.6, pane: 'drawPane'
        }).addTo(map);

        // Hitung total luas jika + titik baru ini (minimal 3 titik)
        let txt = '';
        if (drawingPoints.length >= 2) {
            const tempPoints = [...drawingPoints, currentPoint];
            let luasArea = calculatePolygonArea(tempPoints);
            txt = '<br>Luas: ' + parseFloat(luasArea.toFixed(2)).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 }) + ' m²';
        }

        // Tampilkan tooltip berjalan
        if (!liveTooltip) {
            liveTooltip = L.tooltip({ permanent: true, className: 'live-tooltip', direction: 'auto', offset: [15, 15], pane: 'drawPane' })
                           .setLatLng(currentPoint)
                           .setContent('<b>➕ Lanjut</b> · <kbd>Enter</kbd> / 2×klik selesai' + txt)
                           .addTo(map);
        } else {
            liveTooltip.setLatLng(currentPoint);
            liveTooltip.setContent('<b>➕ Lanjut</b> · <kbd>Enter</kbd> / 2×klik selesai' + txt);
        }
    }

    function onKeyDown(e) {
        if (currentMode !== 2 || currentSubMode !== 'parsil' || !isDrawing) return;
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            finishDrawing();
        }
        if (e.key === 'Escape') stopDrawing(false);
    }

    // ── Form popup setelah gambar selesai ─────────────
    function showFormParsil(points) {
        // Hitung luas area
        let luasArea = calculatePolygonArea(points);
        luasArea = luasArea.toFixed(2);
        
        let luasStr = parseFloat(luasArea).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 });

        // Build GeoJSON Polygon
        let geojsonPoints = points.map(p => [p.lng, p.lat]);
        // GeoJSON First and last position must be strictly equivalent
        geojsonPoints.push([points[0].lng, points[0].lat]);

        const geojson = JSON.stringify({
            type: 'Polygon',
            coordinates: [geojsonPoints]
        });

        // Popup di bounds / center polygon
        const tempPoly = L.polygon(points);
        const centerPt = tempPoly.getBounds().getCenter();

        L.popup({ maxWidth: 340, closeOnClick: false, closeButton: true })
            .setLatLng(centerPt)
            .setContent(`
                <div class="form-popup">
                    <div class="form-popup-header">
                        <div class="form-popup-icon amber">🏘️</div>
                        <div>
                            <div class="form-popup-title">Tambah Data Parsil (Kavling)</div>
                            <div class="form-popup-coords">${points.length} titik · Luas : ${luasStr} m²</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nama/Keterangan Parsil *</label>
                        <input type="text" id="fp_nama" placeholder="cth. Kavling Blok C" maxlength="100" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Status Kepemilikan *</label>
                        <select id="fp_status">
                            <option value="SHM" selected>Sertifikat Hak Milik (SHM)</option>
                            <option value="HGB">Sertifikat Hak Guna Bangunan (HGB)</option>
                            <option value="HGU">Sertifikat Hak Guna Usaha (HGU)</option>
                            <option value="HP">Hak Pakai (HP)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Luas Tanah (otomatis)</label>
                        <div class="input-readonly">${luasStr} m²</div>
                    </div>
                    <input type="hidden" id="fp_geojson" value='${escapeHTML(geojson)}'>
                    <input type="hidden" id="fp_luas" value="${luasArea}">
                    <button class="btn-save" id="btnSimpanParsil" style="background:var(--c-warn);color:#111" onclick="window._parsilSimpan()">💾 Simpan Parsil</button>
                    <div class="form-status" id="parsilStatus"></div>
                </div>
            `)
            .openOn(map);

        setTimeout(() => { const el = document.getElementById('fp_nama'); if (el) el.focus(); }, 200);

        // Kalau popup ditutup tanpa simpan: hapus preview
        map.once('popupclose', () => {
            // preview sudah di-clear di stopDrawing
        });
    }

    // ── Simpan parsil ─────────────────────────────────
    window._parsilSimpan = function () {
        const nama = document.getElementById('fp_nama').value.trim();
        const status = document.getElementById('fp_status').value;
        const geojson = document.getElementById('fp_geojson').value;
        const luas = document.getElementById('fp_luas').value;
        const statusEl = document.getElementById('parsilStatus');
        const btn = document.getElementById('btnSimpanParsil');

        if (!nama) {
            statusEl.textContent = '⚠ Nama parsil wajib diisi.';
            statusEl.className = 'form-status error';
            document.getElementById('fp_nama').focus();
            return;
        }

        btn.disabled = true;
        btn.textContent = '⏳ Menyimpan...';

        const fd = new FormData();
        fd.append('nama_parsil', nama);
        fd.append('status_kepemilikan', status);
        fd.append('geojson', geojson);
        fd.append('luas_m2', luas);

        fetch('api/parsil/simpan.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(j => {
                if (j.status === 'success') {
                    map.closePopup();
                    addPolygonToMap(j.data);
                    updateCount(Object.keys(allLayers).length, '🏘️');
                    const allItems = Object.values(allLayers).map(l => ({
                        id: l._parsilId, name: l._parsilNama,
                        badge: l._parsilStatus,
                        badgeColor: getColor(l._parsilStatus),
                        dotColor:   getColor(l._parsilStatus)
                    }));
                    if (typeof window.dlRefreshList === 'function') window.dlRefreshList('Parsil', allItems);
                    showToast(`"${escapeHTML(nama)}" berhasil disimpan!`);
                } else throw new Error(j.message);
            })
            .catch(err => {
                statusEl.textContent = '✕ ' + err.message;
                statusEl.className = 'form-status error';
                btn.disabled = false;
                btn.textContent = '💾 Simpan Parsil';
            });
    };

    // ── Init / cleanup ────────────────────────────────────────────
    window.initParsil = function () {
        if (!active) {
            active = true;
            map.on('click', onMapClick);
            map.on('dblclick', onMapDoubleClick);
            map.on('mousemove', onMouseMove);
            document.addEventListener('keydown', onKeyDown);
        }

        // Daftarkan fungsi focus untuk panel list
        window._dlFocusFns['Parsil'] = function(id) {
            const layer = allLayers[id];
            if (!layer) return;
            map.fitBounds(layer.getBounds(), { padding: [60, 60], animate: true });
            setTimeout(() => layer.openPopup(layer.getBounds().getCenter()), 400);
        };

        layerGroup.addTo(map);
        loadData();
    };

    // Ekspos ke global agar bisa dipanggil dari index.php
    window._parsilStartDraw = startDrawing;
    window._parsilFinishDraw = finishDrawing;
    window._parsilStopDraw = function() { stopDrawing(false); };

    // Filter Toggle
    window._parsilToggleLayer = function(visible) {
        if (visible) {
            map.addLayer(layerGroup);
        } else {
            map.removeLayer(layerGroup);
            stopDrawing(false); // Batalkan mode draw jika layer disembunyikan
        }
    };

})();
