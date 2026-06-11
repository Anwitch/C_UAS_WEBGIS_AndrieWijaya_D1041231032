// modules/kebutuhan.js — Manajemen Kebutuhan per Warga Miskin (F14)
(function () {
    const KATEGORI_ICON = {
        'Sembako'           : 'shopping-cart',
        'Biaya Sekolah'     : 'graduation-cap',
        'Biaya Kesehatan'   : 'heart-pulse',
        'Modal Usaha'       : 'briefcase',
        'Renovasi Rumah'    : 'home',
        'Perlengkapan Rumah': 'armchair',
        'Pakaian'           : 'shirt',
        'Lainnya'           : 'clipboard-list',
    };
    const STATUS_COLOR = {
        'Belum Terpenuhi': '#ef4444',
        'Dalam Proses'   : '#e3b341',
        'Terpenuhi'      : '#3fb950',
    };
    const KATEGORI_LIST = [
        'Sembako','Biaya Sekolah','Biaya Kesehatan','Modal Usaha',
        'Renovasi Rumah','Perlengkapan Rumah','Pakaian','Lainnya',
    ];

    function isOp()   { return window.APP_USER && (window.APP_USER.isOp || window.APP_USER.isAdmin); }
    function esc(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str ?? ''));
        return d.innerHTML;
    }

    // ── Bangun seksi kebutuhan untuk popup penduduk ───────────────────────────
    window._kebutuhanBuildSection = function (pendudukId, kebutuhanOpen, statusVerifikasi) {
        const count = parseInt(kebutuhanOpen) || 0;
        const canMutate = isOp() && statusVerifikasi === 'Terverifikasi';
        const badgeHtml = count > 0
            ? `<span id="kebutuhanBadge_${pendudukId}"
                 style="background:#ef444422;color:#ef4444;border:1px solid #ef444444;
                        font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;
                        margin-left:4px;">${count} Belum Terpenuhi</span>`
            : `<span id="kebutuhanBadge_${pendudukId}"></span>`;

        const formHtml = canMutate ? `
            <div id="kebutuhanForm_${pendudukId}" style="display:none;margin-top:8px;padding:8px;
                 background:rgba(255,255,255,.03);border-radius:6px;border:1px solid var(--c-border);">
                <select id="kf_kat_${pendudukId}"
                    style="width:100%;padding:5px 8px;background:var(--c-surface2);border:1px solid var(--c-border);
                           border-radius:6px;color:var(--c-text);font-size:12px;margin-bottom:6px;">
                    ${KATEGORI_LIST.map(k => `<option value="${esc(k)}">${esc(k)}</option>`).join('')}
                </select>
                <input type="text" id="kf_desc_${pendudukId}"
                    placeholder="Deskripsi opsional (maks 300 karakter)" maxlength="300"
                    style="width:100%;padding:5px 8px;background:var(--c-surface2);border:1px solid var(--c-border);
                                 <div style="display:flex;gap:6px;">
                    <button onclick="window._kebutuhanSimpan(${pendudukId})"
                        style="flex:1;padding:5px;font-size:11px;background:rgba(63,185,80,.1);color:#3fb950;
                               border:1px solid rgba(63,185,80,.3);border-radius:6px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:4px;">
                        <i data-lucide="save" style="width:12px;height:12px;"></i> Simpan
                    </button>
                    <button onclick="document.getElementById('kebutuhanForm_${pendudukId}').style.display='none'"
                        style="padding:5px 10px;font-size:11px;background:transparent;color:var(--c-muted);
                               border:1px solid var(--c-border);border-radius:6px;cursor:pointer;">
                        Batal
                    </button>
                </div>
            </div>
            <button id="btnTambahKebutuhan_${pendudukId}"
                onclick="document.getElementById('kebutuhanForm_${pendudukId}').style.display='';
                         document.getElementById('btnTambahKebutuhan_${pendudukId}').style.display='none';
                         if(typeof lucide !== 'undefined') lucide.createIcons();"
                style="font-size:11px;padding:3px 8px;margin-top:6px;border-radius:4px;cursor:pointer;
                       border:1px solid rgba(63,185,80,.3);background:transparent;color:#3fb950;">
                + Tambah Kebutuhan
            </button>` : (isOp() ? `<div style="font-size:11px;color:var(--c-muted);font-weight:600;margin-top:6px;">Menunggu verifikasi admin</div>` : '');
 
        return `<div class="info-row" style="align-items:flex-start;border-top:1px solid var(--c-border);padding-top:8px;margin-top:4px;">
            <div class="info-row-icon"><i data-lucide="clipboard-list"></i></div>
            <div style="flex:1;">
                <div class="info-row-label">Kebutuhan ${badgeHtml}</div>
                <div id="kebutuhanBody_${pendudukId}" data-verifikasi="${esc(statusVerifikasi || 'Pending')}" style="margin-top:4px;">
                    <button onclick="window._kebutuhanLoad(${pendudukId})"
                        style="font-size:11px;padding:2px 7px;border-radius:4px;cursor:pointer;
                               border:1px solid var(--c-border);background:transparent;color:var(--c-muted);">
                        Muat Kebutuhan
                    </button>
                </div>
                ${formHtml}
            </div>
        </div>`;
    };

    // ── Muat dan render daftar kebutuhan ─────────────────────────────────────
    window._kebutuhanLoad = function (pendudukId) {
        const body = document.getElementById('kebutuhanBody_' + pendudukId);
        if (!body) return;
        const statusVerifikasi = body.dataset.verifikasi || 'Pending';
        const canMutate = isOp() && statusVerifikasi === 'Terverifikasi';
        body.innerHTML = '<span style="font-size:11px;color:var(--c-muted);display:inline-flex;align-items:center;gap:4px;"><i data-lucide="loader-2" class="lucide animate-spin" style="width:12px;height:12px;"></i> Memuat...</span>';
        if (typeof lucide !== 'undefined') lucide.createIcons();

        fetch('api/kebutuhan/ambil.php?penduduk_id=' + pendudukId + '&_=' + Date.now(), { cache: 'no-store' })
            .then(r => r.json())
            .then(j => {
                if (j.status !== 'success') throw new Error(j.message);
                const rows = j.data;
                const openCount = rows.filter(r => r.status === 'Belum Terpenuhi').length;
                if (typeof window._pendudukRefreshKebutuhanCount === 'function') {
                    window._pendudukRefreshKebutuhanCount(pendudukId, openCount);
                }
                if (rows.length === 0) {
                    body.innerHTML = '<span style="font-size:11px;color:var(--c-muted);">Belum ada kebutuhan tercatat.</span>';
                    return;
                }
                body.innerHTML = rows.map(r => {
                    const sc   = STATUS_COLOR[r.status] || '#8b949e';
                    const icon = KATEGORI_ICON[r.kategori] || 'clipboard-list';
                    const iconHtml = `<i data-lucide="${icon}" class="lucide-inline" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i>`;
                    const statusBtns = canMutate
                        ? ['Belum Terpenuhi', 'Dalam Proses', 'Terpenuhi'].map(s =>
                            `<button onclick="window._kebutuhanUpdateStatus(${r.id},'${s}',${pendudukId})"
                                style="font-size:10px;padding:2px 6px;border-radius:4px;cursor:pointer;
                                    border:1px solid ${STATUS_COLOR[s]};
                                    background:${s === r.status ? STATUS_COLOR[s] + '33' : 'transparent'};
                                    color:${STATUS_COLOR[s]};font-weight:${s === r.status ? '700' : '400'};">${s}</button>`
                          ).join('')
                        : `<span style="font-size:11px;color:${sc};font-weight:700;">${esc(r.status)}</span>`;
                    return `<div style="padding:5px 0;border-bottom:1px solid rgba(255,255,255,.06);">
                        <div style="font-size:12px;font-weight:600;color:var(--c-text);display:flex;align-items:center;gap:2px;">
                            ${iconHtml} ${esc(r.kategori)}
                        </div>
                        ${r.deskripsi ? `<div style="font-size:11px;color:var(--c-muted);margin:2px 0;">${esc(r.deskripsi)}</div>` : ''}
                        <div style="display:flex;gap:4px;margin-top:4px;flex-wrap:wrap;">${statusBtns}</div>
                    </div>`;
                }).join('');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            })
            .catch(err => {
                const b = document.getElementById('kebutuhanBody_' + pendudukId);
                if (b) {
                    b.innerHTML = `<span style="font-size:11px;color:#ef4444;display:inline-flex;align-items:center;gap:4px;"><i data-lucide="x-circle" style="width:12px;height:12px;"></i> Gagal: ${esc(err.message)}</span>`;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });
    };

    // ── Simpan kebutuhan baru ─────────────────────────────────────────────────
    window._kebutuhanSimpan = function (pendudukId) {
        const kat  = document.getElementById('kf_kat_'  + pendudukId)?.value;
        const desc = document.getElementById('kf_desc_' + pendudukId)?.value?.trim() || '';
        if (!kat) return;

        const fd = new FormData();
        fd.append('penduduk_id', pendudukId);
        fd.append('kategori', kat);
        fd.append('deskripsi', desc);

        fetch('api/kebutuhan/simpan.php', { method: 'POST', body: appendCsrf(fd) })
            .then(r => r.json())
            .then(j => {
                if (j.status !== 'success') throw new Error(j.message);
                const formEl = document.getElementById('kebutuhanForm_' + pendudukId);
                const btnEl  = document.getElementById('btnTambahKebutuhan_' + pendudukId);
                const descEl = document.getElementById('kf_desc_' + pendudukId);
                if (formEl) formEl.style.display = 'none';
                if (btnEl)  btnEl.style.display  = '';
                if (descEl) descEl.value = '';
                window._kebutuhanLoad(pendudukId);
                if (typeof window.dispatchDataChanged === 'function') {
                    window.dispatchDataChanged('kebutuhan', { pendudukId, mutation: 'create' });
                }
                showToast('Kebutuhan berhasil disimpan.');
            })
            .catch(err => showToast(err.message || 'Gagal menyimpan kebutuhan.', 'error'));
    };

    // ── Update status kebutuhan ───────────────────────────────────────────────
    window._kebutuhanUpdateStatus = function (kebutuhanId, status, pendudukId) {
        const catatan = prompt('Catatan perubahan kebutuhan (opsional):', '');
        if (catatan === null) return;
        const fd = new FormData();
        fd.append('id',     kebutuhanId);
        fd.append('status', status);
        fd.append('catatan', catatan);

        fetch('api/kebutuhan/update_status.php', { method: 'POST', body: appendCsrf(fd) })
            .then(r => r.json())
            .then(j => {
                if (j.status !== 'success') throw new Error(j.message);
                window._kebutuhanLoad(pendudukId);
                if (typeof window.dispatchDataChanged === 'function') {
                    window.dispatchDataChanged('kebutuhan', { pendudukId, kebutuhanId, mutation: 'status' });
                }
                showToast('Status kebutuhan diperbarui.');
            })
            .catch(err => showToast(err.message || 'Gagal memperbarui status.', 'error'));
    };
})();
