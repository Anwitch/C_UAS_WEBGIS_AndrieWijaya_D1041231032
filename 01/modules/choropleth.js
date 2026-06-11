// modules/choropleth.js — Choropleth visualizer untuk Project 01

(function () {
    const PALETTES = {
        blue:   ['#c6dbef', '#9ecae1', '#6baed6', '#3182bd', '#08519c'],
        orange: ['#fdd0a2', '#fdae6b', '#fd8d3c', '#e6550d', '#a63603'],
        green:  ['#c7e9c0', '#a1d99b', '#74c476', '#31a354', '#006d2c'],
        purple: ['#dadaeb', '#bcbddc', '#9e9ac8', '#6a51a3', '#3f007d'],
    };

    // Qualitative palette — 10 distinct colors for categorical data
    const CAT_COLORS = [
        '#4e79a7','#f28e2b','#e15759','#76b7b2',
        '#59a14f','#edc948','#b07aa1','#ff9da7',
        '#9c755f','#bab0ac',
    ];

    let layers  = [];   // { meta, leafletLayer, colorMode, categoryMap, breaks, pal }
    let allMeta = [];   // metadata rows from API (no geojson blob)

    // ── Color scale helpers ──────────────────────────────────────────
    function quantileBreaks(values) {
        const sorted = values.filter(v => isFinite(v)).sort((a, b) => a - b);
        if (sorted.length === 0) return [0, 0, 0, 0, 0, 0];
        const breaks = [];
        for (let i = 0; i <= 5; i++) {
            breaks.push(sorted[Math.round(i * (sorted.length - 1) / 5)]);
        }
        return breaks;
    }

    function getColor(val, breaks, pal) {
        for (let i = 0; i < pal.length; i++) {
            if (val <= breaks[i + 1]) return pal[i];
        }
        return pal[pal.length - 1];
    }

    // Returns {colorMode:'categorical'|'quantile', categoryMap, breaks}
    function buildColorConfig(features, attrKey, pal) {
        const rawVals = features.map(f => f.properties[attrKey]);
        const numVals = rawVals.map(v => parseFloat(v));
        const allNumeric = numVals.every(v => isFinite(v));
        const uniqueRaw  = [...new Set(rawVals.map(String))];

        // Use categorical when: any non-numeric value, OR ≤ 10 unique values
        if (!allNumeric || uniqueRaw.length <= 10) {
            const categoryMap = {};
            uniqueRaw.sort().forEach((v, i) => { categoryMap[v] = CAT_COLORS[i % CAT_COLORS.length]; });
            return { colorMode: 'categorical', categoryMap, breaks: null };
        }
        const breaks = quantileBreaks(numVals);
        return { colorMode: 'quantile', categoryMap: null, breaks };
    }

    function fmtVal(v) {
        const n = parseFloat(v);
        if (isFinite(n)) return n.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        return String(v ?? '—');
    }

    function isClassicDrawingActive() {
        const modeActive = typeof window.currentMode !== 'undefined' && Number(window.currentMode) !== 0;
        const toolActive = window.activeTool === 'jalan' || window.activeTool === 'parsil' || window.activeTool === 'point';
        const drawHint = document.getElementById('drawHint');
        const hintVisible = !!drawHint && drawHint.classList.contains('visible');
        return modeActive || toolActive || hintVisible;
    }

    function stopLayerEvent(e) {
        if (e.originalEvent) {
            L.DomEvent.stop(e.originalEvent);
        } else {
            L.DomEvent.stopPropagation(e);
        }
    }

    function forwardDrawingEventToMap(type, e) {
        const active = isClassicDrawingActive();
        console.log("choropleth.js forwardDrawingEventToMap: type =", type, ", isClassicDrawingActive =", active);
        if (!active) return false;
        stopLayerEvent(e);
        map.fire(type, {
            latlng: e.latlng,
            layerPoint: e.layerPoint || map.latLngToLayerPoint(e.latlng),
            containerPoint: e.containerPoint || map.latLngToContainerPoint(e.latlng),
            originalEvent: e.originalEvent,
        });
        return true;
    }

    function setLayerHitTesting(leafletLayer, enabled) {
        if (!leafletLayer || typeof leafletLayer.eachLayer !== 'function') return;
        leafletLayer.eachLayer(layer => {
            if (layer._path && layer._path.style) {
                layer._path.style.pointerEvents = enabled ? '' : 'none';
            }
        });
    }

    function setAllHitTesting(enabled) {
        layers.forEach(rendered => setLayerHitTesting(rendered.leafletLayer, enabled));
    }

    // ── Render one GeoJSON layer on map ─────────────────────────────
    function renderLeafletLayer(meta, geojson) {
        const pal = PALETTES[meta.palette] || PALETTES.blue;
        const { colorMode, categoryMap, breaks } = buildColorConfig(geojson.features, meta.attribute_key, pal);

        function featureColor(f) {
            const raw = f.properties[meta.attribute_key];
            if (colorMode === 'categorical') {
                return categoryMap[String(raw)] || '#cccccc';
            }
            const val = parseFloat(raw);
            return isFinite(val) ? getColor(val, breaks, pal) : '#cccccc';
        }

        const lyr = L.geoJSON(geojson, {
            pane: 'choroplethPane',
            style: function (f) {
                return {
                    fillColor:   featureColor(f),
                    weight:      1,
                    color:       '#ffffff',
                    fillOpacity: 0.78,
                    opacity:     0.9,
                };
            },
            onEachFeature: function (f, layer) {
                const props = f.properties || {};

                function buildPopup(closeBtn) {
                    const rows = Object.entries(props)
                        .map(([k, v]) => `<tr><td class="pp-k">${escapeHTML(String(k))}</td><td class="pp-v">${escapeHTML(String(v ?? '—'))}</td></tr>`)
                        .join('');
                    return `<div class="choro-popup"><table>${rows}</table></div>`;
                }

                layer.on('mouseover', function (e) {
                    if (isClassicDrawingActive()) return;
                    L.popup({ closeButton: false, className: 'choro-popup-wrap', offset: [0, -4] })
                        .setLatLng(e.latlng)
                        .setContent(buildPopup(false))
                        .openOn(map);
                    layer.setStyle({ weight: 2, color: '#1c1612' });
                });
                layer.on('mouseout', function () {
                    if (isClassicDrawingActive()) return;
                    map.closePopup();
                    lyr.resetStyle(layer);
                });
                layer.on('click', function (e) {
                    if (forwardDrawingEventToMap('click', e)) return;
                    L.popup({ closeButton: true, className: 'choro-popup-wrap' })
                        .setLatLng(e.latlng)
                        .setContent(buildPopup(true))
                        .openOn(map);
                    stopLayerEvent(e);
                });
                layer.on('dblclick', function (e) {
                    if (forwardDrawingEventToMap('dblclick', e)) return;
                });
            },
        });

        return { meta, leafletLayer: lyr, colorMode, categoryMap, breaks, pal };
    }

    // ── Legend ───────────────────────────────────────────────────────
    function buildLegends() {
        const container = document.getElementById('legendContainer');
        if (!container) return;

        const visible = layers.filter(l => l.meta.is_visible);
        if (visible.length === 0) {
            container.innerHTML = '';
            container.style.display = 'none';
            return;
        }

        container.style.display = 'block';
        container.innerHTML = visible.map(({ meta, colorMode, categoryMap, breaks, pal }) => {
            let classes;
            if (colorMode === 'categorical') {
                classes = Object.entries(categoryMap).map(([val, color]) => `
                <div class="leg-row">
                    <span class="leg-swatch" style="background:${color}"></span>
                    <span class="leg-range">${escapeHTML(String(val))}</span>
                </div>`).join('');
            } else {
                classes = pal.map((color, i) => `
                <div class="leg-row">
                    <span class="leg-swatch" style="background:${color}"></span>
                    <span class="leg-range">${fmtVal(breaks[i])} – ${fmtVal(breaks[i + 1])}</span>
                </div>`).join('');
            }
            return `<div class="leg-block">
                <div class="leg-title">${escapeHTML(meta.nama)}</div>
                <div class="leg-attr">${escapeHTML(meta.attribute_key)}</div>
                ${classes}
            </div>`;
        }).join('');
    }

    // ── Layer panel ──────────────────────────────────────────────────
    function refreshPanel() {
        const list = document.getElementById('layerList');
        if (!list) return;

        if (allMeta.length === 0) {
            list.innerHTML = '<div class="layer-empty">Belum ada layer.<br>Upload GeoJSON dari ArcGIS.</div>';
            return;
        }

        list.innerHTML = allMeta.map(m => {
            const isVisible  = m.is_visible;
            const eyeIcon    = isVisible ? 'eye' : 'eye-off';
            const palDots    = (PALETTES[m.palette] || PALETTES.blue)
                .map(c => `<span class="pal-dot" style="background:${c}"></span>`).join('');
            const delBtn     = window._IS_ADMIN
                ? `<button class="layer-del" data-layer-delete="${m.id}" title="Hapus"><i data-lucide="trash-2"></i></button>`
                : '';
            return `
            <div class="layer-item ${isVisible ? '' : 'is-hidden'}" id="li${m.id}">
                <div class="layer-item-row">
                    <button class="layer-eye" onclick="window._choro.toggleLayer(${m.id})" title="${isVisible ? 'Sembunyikan' : 'Tampilkan'}">
                        <i data-lucide="${eyeIcon}" id="eye${m.id}"></i>
                    </button>
                    <div class="layer-info">
                        <div class="layer-name">${escapeHTML(m.nama)}</div>
                        <div class="layer-attr">${escapeHTML(m.attribute_key)}</div>
                    </div>
                    ${delBtn}
                </div>
                <div class="layer-pal">${palDots}</div>
            </div>`;
        }).join('');

        list.querySelectorAll('[data-layer-delete]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = Number(btn.getAttribute('data-layer-delete'));
                const meta = allMeta.find(item => item.id === id);
                if (meta) window._choro.deleteLayer(id, meta.nama);
            });
        });

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // ── Load GeoJSON for one layer, add to map ───────────────────────
    function loadLayerGeojson(meta) {
        return fetch('api/choropleth/geojson.php?id=' + meta.id + '&_=' + Date.now())
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(geojson => {
                const rendered = renderLeafletLayer(meta, geojson);
                if (meta.is_visible) rendered.leafletLayer.addTo(map);
                layers.push(rendered);
                setLayerHitTesting(rendered.leafletLayer, !isClassicDrawingActive());
                return rendered;
            });
    }

    // ── Initial load ─────────────────────────────────────────────────
    function loadAll() {
        layers = [];
        return fetch('api/choropleth/ambil.php?_=' + Date.now())
            .then(r => r.json())
            .then(j => {
                if (j.status !== 'success') throw new Error(j.message || 'Gagal memuat layer.');
                allMeta = j.data;
                refreshPanel();
                return Promise.all(allMeta.map(meta => loadLayerGeojson(meta)));
            })
            .then(() => buildLegends())
            .catch(err => {
                showToast('Gagal memuat data: ' + err.message, 'error');
                console.error(err);
            });
    }

    // ── Public API ───────────────────────────────────────────────────
    window._choro = {

        toggleLayer: function (id) {
            const rendered = layers.find(l => l.meta.id === id);
            const meta     = allMeta.find(m => m.id === id);
            if (!meta) return;

            const newVisible  = !meta.is_visible;
            meta.is_visible   = newVisible;
            if (rendered) {
                rendered.meta.is_visible = newVisible;
                if (newVisible) {
                    rendered.leafletLayer.addTo(map);
                    setLayerHitTesting(rendered.leafletLayer, !isClassicDrawingActive());
                }
                else            map.removeLayer(rendered.leafletLayer);
            }

            refreshPanel();
            buildLegends();
            showToast(escapeHTML(meta.nama) + (newVisible ? ' ditampilkan.' : ' disembunyikan.'));

            if (window._IS_ADMIN) {
                const fd = new FormData();
                fd.append('id', id);
                fd.append('csrf_token', window._CSRF_TOKEN || '');
                fetch('api/choropleth/toggle.php', { method: 'POST', body: fd }).catch(() => {});
            }
        },

        deleteLayer: function (id, nama) {
            showDeleteConfirm('Yakin ingin menghapus layer "' + nama + '"?').then(ok => {
                if (!ok) return;
                const fd = new FormData();
                fd.append('id', id);
                fd.append('csrf_token', window._CSRF_TOKEN || '');
                fetch('api/choropleth/hapus.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(j => {
                        if (j.status !== 'success') throw new Error(j.message);
                        const idx = layers.findIndex(l => l.meta.id === id);
                        if (idx !== -1) { map.removeLayer(layers[idx].leafletLayer); layers.splice(idx, 1); }
                        allMeta = allMeta.filter(m => m.id !== id);
                        refreshPanel();
                        buildLegends();
                        showToast('Layer "' + escapeHTML(nama) + '" berhasil dihapus.');
                    })
                    .catch(err => showToast(err.message || 'Gagal menghapus.', 'error'));
            });
        },

        // Called after successful upload to add new layer without full reload
        addLayer: function (meta) {
            allMeta.push(meta);
            loadLayerGeojson(meta).then(() => {
                refreshPanel();
                buildLegends();
                // Zoom to new layer
                const rendered = layers.find(l => l.meta.id === meta.id);
                if (rendered) {
                    try { map.fitBounds(rendered.leafletLayer.getBounds(), { padding: [40, 40] }); } catch (_) {}
                }
            }).catch(err => showToast('Layer tersimpan tapi gagal render: ' + err.message, 'error'));
        },

        setHitTesting: setAllHitTesting,
    };

    window.initChoropleth = loadAll;
})();
