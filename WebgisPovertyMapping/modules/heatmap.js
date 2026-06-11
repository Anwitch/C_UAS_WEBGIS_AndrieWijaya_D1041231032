// modules/heatmap.js — Heatmap kepadatan, intensitas proporsional jumlah_jiwa (F9)
(function () {
    let heatmapLayer   = null;
    let active         = false;
    let heatmapKategori = '';

    function canUseHeatmap() {
        return !!(window.APP_USER?.isOp || window.APP_USER?.isAdmin);
    }

    function buildHeatData() {
        const source = (window._pendudukAll || window._pendudukList || []).filter(function (p) {
            return !heatmapKategori || p.kategori === heatmapKategori;
        });
        const validSource = source
            .map(function (p) {
                const rawLat = p.lat;
                const rawLng = p.lng;
                const lat = Number(p.lat);
                const lng = Number(p.lng);
                return {
                    hasCoordinate: rawLat !== null && rawLat !== '' && rawLng !== null && rawLng !== '',
                    lat: lat,
                    lng: lng,
                    jumlah_jiwa: Number(p.jumlah_jiwa || 1)
                };
            })
            .filter(function (p) {
                const lat = p.lat;
                const lng = p.lng;
                return p.hasCoordinate && Number.isFinite(lat) && Number.isFinite(lng);
            });
        const maxJiwa = Math.max(1, ...validSource.map(function (p) {
            return p.jumlah_jiwa || 1;
        }));
        return validSource.map(function (p) {
            return [p.lat, p.lng, 0.2 + 0.8 * ((p.jumlah_jiwa || 1) / maxJiwa)];
        });
    }

    window._heatmapUpdate = function () {
        if (!window.APP_USER?.isOp && !window.APP_USER?.isAdmin) {
            active = false;
            if (heatmapLayer && typeof map !== 'undefined' && map.hasLayer(heatmapLayer)) {
                map.removeLayer(heatmapLayer);
            }
            return;
        }
        if (!active || !heatmapLayer) return;
        heatmapLayer.setLatLngs(buildHeatData());
    };

    window._heatmapSetKategori = function (k) {
        heatmapKategori = k;
        window._heatmapUpdate();
    };

    window._toggleHeatmap = function (visible) {
        if (!canUseHeatmap() || typeof L === 'undefined' || !L.heatLayer || typeof map === 'undefined') {
            active = false;
            const checkbox = document.getElementById('chkHeatmap');
            if (checkbox) checkbox.checked = false;
            if (heatmapLayer && typeof map !== 'undefined' && map.hasLayer(heatmapLayer)) {
                map.removeLayer(heatmapLayer);
            }
            return;
        }
        active = visible;
        if (active) {
            if (!heatmapLayer) {
                heatmapLayer = L.heatLayer([], {
                    radius  : 25,
                    blur    : 15,
                    maxZoom : 17,
                    gradient: { 0.4: 'blue', 0.6: 'cyan', 0.7: 'lime', 0.8: 'yellow', 1.0: 'red' }
                });
            }
            heatmapLayer.addTo(map);
            window._heatmapUpdate();
            showToast('Heatmap Kepadatan diaktifkan.');
        } else {
            if (heatmapLayer) map.removeLayer(heatmapLayer);
            showToast('Heatmap dinonaktifkan.');
        }
    };
})();
