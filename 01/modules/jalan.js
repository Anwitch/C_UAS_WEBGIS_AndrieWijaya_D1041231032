// modules/jalan.js — Logika Pertemuan 2: Manajemen Data Jalan (Polyline)

(function () {
    const layerGroup = L.layerGroup();
    let allLayers = {}; // { id: polylineObject }
    let active = false;

    // ── Warna per status jalan ────────────────────────
    const JALAN_COLORS = {
        'Nasional': '#ef4444',
        'Provinsi': '#f97316',
        'Kabupaten': '#eab308'
    };

    function getColor(status) {
        return JALAN_COLORS[status] || '#888';
    }

    // ── Render polyline ───────────────────────────────
    function addPolylineToMap(row) {
        let coords;
        try {
            const gj = typeof row.geojson === 'string' ? JSON.parse(row.geojson) : row.geojson;
            // GeoJSON coordinates: [lng, lat] → Leaflet: [lat, lng]
            coords = gj.coordinates.map(c => [c[1], c[0]]);
        } catch (e) {
            console.error('GeoJSON parse error (jalan id=' + row.id + ')', e);
            return;
        }

        const poly = L.polyline(coords, {
            color: getColor(row.status_jalan),
            weight: 5,
            opacity: 0.85,
            pane: 'jalanPane'
        });

        poly._jalanId     = row.id;
        poly._jalanNama   = row.nama_jalan;
        poly._jalanStatus = row.status_jalan;
        allLayers[row.id] = poly;

        poly.bindPopup(buildInfoPopup(row), { maxWidth: 300 });
        layerGroup.addLayer(poly);
    }

    function buildInfoPopup(row) {
        const color = getColor(row.status_jalan);
        const panjang = row.panjang_meter
            ? parseFloat(row.panjang_meter) >= 1000
                ? (parseFloat(row.panjang_meter) / 1000).toFixed(2) + ' km'
                : parseFloat(row.panjang_meter).toFixed(1) + ' m'
            : '—';

        return `
            <div class="info-popup">
                <div class="info-popup-header">
                    <div class="info-popup-icon" style="background:${color}22;">
                        <i data-lucide="route" style="color:${color};width:18px;height:18px;"></i>
                    </div>
                    <div>
                        <div class="info-popup-name">${escapeHTML(row.nama_jalan)}</div>
                        <div class="info-popup-id">#${row.id}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-row-icon">
                        <i data-lucide="tag" style="width:14px;height:14px;color:var(--text-m)"></i>
                    </div>
                    <div>
                        <div class="info-row-label">Status Jalan</div>
                        <div class="info-row-value">
                            <span class="status-badge-inline"
                                  style="background:${color}22;color:${color};border:1px solid ${color}55;">
                                Jalan ${escapeHTML(row.status_jalan)}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-row-icon">
                        <i data-lucide="milestone" style="width:14px;height:14px;color:var(--text-m)"></i>
                    </div>
                    <div>
                        <div class="info-row-label">Panjang Jalan</div>
                        <div class="info-row-value" style="font-family:var(--font-mono);">${panjang}</div>
                    </div>
                </div>
                ${window._IS_ADMIN ? `<button class="btn-hapus" onclick="window._jalanHapus(${row.id}, this)"><i data-lucide="trash-2" style="width:13px;height:13px;margin-right:4px;"></i> Hapus Jalan</button>` : ''}
            </div>
        `;
    }

    // ── Load data ───────────────────────────────────────────────────
    function loadData() {
        layerGroup.clearLayers();
        allLayers = {};

        fetch('api/jalan/ambil.php?_=' + Date.now(), { cache: 'no-store' })
            .then(r => r.json())
            .then(j => {
                if (j.status !== 'success') throw new Error(j.message);
                updateCount(j.total, 'Jalan');
                j.data.forEach(addPolylineToMap);
                refreshJalanList(j.data);
            })
            .catch(err => { showToast('Gagal memuat data jalan.', 'error'); console.error(err); });
    }

    // ── Refresh panel list Data Jalan ──────────────────────────────────
    function refreshJalanList(data) {
        if (typeof window.dlRefreshList !== 'function') return;
        const COLORS = { 'Nasional': '#ef4444', 'Provinsi': '#f97316', 'Kabupaten': '#eab308' };
        const items = (data || []).map(row => ({
            id: row.id,
            name: row.nama_jalan,
            badge: row.status_jalan,
            badgeColor: COLORS[row.status_jalan] || '#888',
            dotColor:   COLORS[row.status_jalan] || '#888'
        }));
        window.dlRefreshList('Jalan', items);
    }

    // ── Hapus jalan ───────────────────────────────────
    window._jalanHapus = function (id, btnEl) {
        showDeleteConfirm('Yakin ingin menghapus data jalan ini?').then(confirmed => {
            if (!confirmed) return;
            btnEl.disabled = true;
            btnEl.innerHTML = '<span class="btn-spinner" style="margin-right: 6px; display: inline-block; vertical-align: middle;"></span> Menghapus...';

            const fd = new FormData();
            fd.append('id', id);
            fd.append('csrf_token', window._CSRF_TOKEN || '');

            fetch('api/jalan/hapus.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(j => {
                    if (j.status === 'success') {
                        map.closePopup();
                        if (allLayers[id]) { layerGroup.removeLayer(allLayers[id]); delete allLayers[id]; }
                        updateCount(Object.keys(allLayers).length, 'Jalan');
                        const COLORS = { 'Nasional': '#ef4444', 'Provinsi': '#f97316', 'Kabupaten': '#eab308' };
                        const remaining = Object.values(allLayers).map(l => ({
                            id: l._jalanId, name: l._jalanNama,
                            badge: l._jalanStatus,
                            badgeColor: COLORS[l._jalanStatus] || '#888',
                            dotColor:   COLORS[l._jalanStatus] || '#888'
                        }));
                        if (typeof window.dlRefreshList === 'function') window.dlRefreshList('Jalan', remaining);
                        showToast('Data jalan berhasil dihapus.');
                    } else throw new Error(j.message);
                })
                .catch(err => {
                    showToast(err.message || 'Gagal menghapus.', 'error');
                    btnEl.disabled = false;
                    btnEl.innerHTML = '<i data-lucide="trash-2" style="width:13px;height:13px;margin-right:4px;"></i> Hapus Jalan';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
        });
    };

    // ── Drawing: gambar polyline baru ────────────────
    let drawingPoints = [];
    let drawingLayer = null; // polyline permanen per point click
    let liveLineLayer = null; // polyline preview kursor ke ujung terakhir
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

        const shouldSave = save && drawingPoints.length >= 2;
        if (shouldSave) {
            showFormJalan(drawingPoints.slice());
        }
        drawingPoints = [];

        if (!shouldSave) {
            if (typeof window._resetActiveTool === 'function') window._resetActiveTool();
        }
    }

    function finishDrawing() {
        if (!isDrawing) return;
        if (drawingPoints.length >= 2) {
            stopDrawing(true);
        } else {
            showToast('Minimal 2 titik untuk membuat jalan (Polyline)', 'error');
        }
    }

    function onMapClick(e) {
        if (currentMode !== 2 || currentSubMode !== 'jalan') return;
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
        const m = L.circleMarker(latlng, { radius: 5, color: '#388bfd', fillColor: '#388bfd', fillOpacity: 1, pane: 'drawPane' });
        m.addTo(map);
        drawingMarkers.push(m);

        // Preview polyline
        if (drawingLayer) map.removeLayer(drawingLayer);
        if (drawingPoints.length >= 2) {
            drawingLayer = L.polyline(drawingPoints, { color: '#388bfd', weight: 4, opacity: .9, pane: 'drawPane' }).addTo(map);
        }
    }

    function onMapDoubleClick(e) {
        if (currentMode !== 2 || currentSubMode !== 'jalan' || !isDrawing) return;
        if (e.originalEvent) L.DomEvent.stop(e.originalEvent);
        finishDrawing();
    }

    function onMouseMove(e) {
        if (currentMode !== 2 || currentSubMode !== 'jalan' || !isDrawing) return;
        if (drawingPoints.length === 0) {
            if (liveTooltip) { map.removeLayer(liveTooltip); liveTooltip = null; }
            return;
        }

        const lastPoint = drawingPoints[drawingPoints.length - 1];
        const currentPoint = e.latlng;

        // Gambar garis tipis (preview) dari titik terakhir ke cursor
        if (liveLineLayer) map.removeLayer(liveLineLayer);
        liveLineLayer = L.polyline([lastPoint, currentPoint], {
            color: '#79c0ff', weight: 2, dashArray: '5,6', opacity: 0.6, pane: 'drawPane'
        }).addTo(map);

        // Hitung total panjang sampai titik ini
        let totalMeter = 0;
        for (let i = 0; i < drawingPoints.length - 1; i++) {
            totalMeter += drawingPoints[i].distanceTo(drawingPoints[i + 1]);
        }
        totalMeter += lastPoint.distanceTo(currentPoint);

        const txt = (totalMeter >= 1000) 
            ? (totalMeter / 1000).toFixed(2) + ' km' 
            : totalMeter.toFixed(1) + ' m';

        // Tampilkan tooltip berjalan
        if (!liveTooltip) {
            liveTooltip = L.tooltip({ permanent: true, className: 'live-tooltip', direction: 'auto', offset: [15, 15], pane: 'drawPane' })
                           .setLatLng(currentPoint)
                           .setContent('<b>➕ Lanjut</b> · <kbd>Enter</kbd> / 2×klik selesai<br>Panjang: ' + txt)
                           .addTo(map);
        } else {
            liveTooltip.setLatLng(currentPoint);
            liveTooltip.setContent('<b>➕ Lanjut</b> · <kbd>Enter</kbd> / 2×klik selesai<br>Panjang: ' + txt);
        }
    }

    function onKeyDown(e) {
        if (currentMode !== 2 || currentSubMode !== 'jalan' || !isDrawing) return;
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            finishDrawing();
        }
        if (e.key === 'Escape') stopDrawing(false);
    }

    // ── Form popup setelah gambar selesai ─────────────
    function showFormJalan(points) {
        // Hitung panjang via Leaflet
        let totalMeter = 0;
        for (let i = 0; i < points.length - 1; i++) {
            totalMeter += points[i].distanceTo(points[i + 1]);
        }
        totalMeter = totalMeter.toFixed(2);

        // Build GeoJSON LineString
        const geojson = JSON.stringify({
            type: 'LineString',
            coordinates: points.map(p => [p.lng, p.lat])
        });

        // Popup di titik tengah
        const midIdx = Math.floor(points.length / 2);
        const midPt = points[midIdx];

        L.popup({ maxWidth: 340, closeOnClick: false, closeButton: true })
            .setLatLng(midPt)
            .setContent(`
                <div class="form-popup">
                    <div class="form-popup-header">
                        <div class="form-popup-icon blue"><i data-lucide="route" style="width:18px;height:18px;"></i></div>
                        <div>
                            <div class="form-popup-title">Tambah Data Jalan</div>
                            <div class="form-popup-coords">${points.length} titik · ${totalMeter} m</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nama Jalan *</label>
                        <input type="text" id="fj_nama" placeholder="cth. Jl. Ahmad Yani" maxlength="100" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Status Jalan *</label>
                        <select id="fj_status">
                            <option value="Nasional">Jalan Nasional</option>
                            <option value="Provinsi">Jalan Provinsi</option>
                            <option value="Kabupaten" selected>Jalan Kabupaten</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Panjang Jalan (otomatis)</label>
                        <div class="input-readonly">${totalMeter} meter</div>
                    </div>
                    <input type="hidden" id="fj_geojson" value='${escapeHTML(geojson)}'>
                    <input type="hidden" id="fj_panjang" value="${totalMeter}">
                    <button class="btn-save" id="btnSimpanJalan" onclick="window._jalanSimpan()"><i data-lucide="save" style="width:14px;height:14px;margin-right:4px;"></i> Simpan Jalan</button>
                    <div class="form-status" id="jalanStatus"></div>
                </div>
            `)
            .openOn(map);

        setTimeout(() => { const el = document.getElementById('fj_nama'); if (el) el.focus(); }, 200);

        // Reset active tool when popup is closed (either by saving or clicking close/outside)
        map.once('popupclose', () => {
            if (typeof window._resetActiveTool === 'function') {
                window._resetActiveTool();
            }
        });
    }

    // ── Simpan jalan ──────────────────────────────────
    window._jalanSimpan = function () {
        const nama = document.getElementById('fj_nama').value.trim();
        const status = document.getElementById('fj_status').value;
        const geojson = document.getElementById('fj_geojson').value;
        const panjang = document.getElementById('fj_panjang').value;
        const statusEl = document.getElementById('jalanStatus');
        const btn = document.getElementById('btnSimpanJalan');

        if (!nama) {
            statusEl.innerHTML = '<i data-lucide="alert-triangle" style="width:14px;height:14px;margin-right:4px;display:inline-block;vertical-align:middle;"></i> Nama jalan wajib diisi.';
            statusEl.className = 'form-status error';
            if (typeof lucide !== 'undefined') lucide.createIcons();
            document.getElementById('fj_nama').focus();
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="btn-spinner" style="margin-right: 6px; display: inline-block; vertical-align: middle;"></span> Menyimpan...';

        const fd = new FormData();
        fd.append('nama_jalan', nama);
        fd.append('status_jalan', status);
        fd.append('geojson', geojson);
        fd.append('panjang_meter', panjang);
        fd.append('csrf_token', window._CSRF_TOKEN || '');

        fetch('api/jalan/simpan.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(j => {
                if (j.status === 'success') {
                    map.closePopup();
                    addPolylineToMap(j.data);
                    updateCount(Object.keys(allLayers).length, 'Jalan');
                    const COLORS = { 'Nasional': '#ef4444', 'Provinsi': '#f97316', 'Kabupaten': '#eab308' };
                    const allItems = Object.values(allLayers).map(l => ({
                        id: l._jalanId, name: l._jalanNama,
                        badge: l._jalanStatus,
                        badgeColor: COLORS[l._jalanStatus] || '#888',
                        dotColor:   COLORS[l._jalanStatus] || '#888'
                    }));
                    if (typeof window.dlRefreshList === 'function') window.dlRefreshList('Jalan', allItems);
                    showToast(`"${escapeHTML(nama)}" berhasil disimpan!`);
                } else throw new Error(j.message);
            })
            .catch(err => {
                statusEl.innerHTML = '<i data-lucide="x-circle" style="width:14px;height:14px;margin-right:4px;display:inline-block;vertical-align:middle;"></i> ' + escapeHTML(err.message);
                statusEl.className = 'form-status error';
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="save" style="width:14px;height:14px;margin-right:4px;"></i> Simpan Jalan';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
    };

    // ── Init / cleanup ───────────────────────────────────────────
    window.initJalan = function () {
        if (!active) {
            active = true;
            map.on('click', onMapClick);
            map.on('dblclick', onMapDoubleClick);
            map.on('mousemove', onMouseMove);
            document.addEventListener('keydown', onKeyDown);
        }

        // Daftarkan fungsi focus untuk panel list
        window._dlFocusFns['Jalan'] = function(id) {
            const layer = allLayers[id];
            if (!layer) return;
            map.fitBounds(layer.getBounds(), { padding: [40, 40], animate: true });
            setTimeout(() => layer.openPopup(layer.getBounds().getCenter()), 400);
        };

        layerGroup.addTo(map);
        loadData();
    };

    // Ekspos ke global agar bisa dipanggil dari index.php
    window._jalanStartDraw = startDrawing;
    window._jalanFinishDraw = finishDrawing;
    window._jalanStopDraw = function() { stopDrawing(false); };

    // Filter Toggle
    window._jalanToggleLayer = function(visible) {
        if (visible) {
            map.addLayer(layerGroup);
        } else {
            map.removeLayer(layerGroup);
            stopDrawing(false); // Batalkan mode draw jika layer disembunyikan
        }
    };

})();
