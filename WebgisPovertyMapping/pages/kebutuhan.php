<?php
require_once '../config.php';
require_once '../auth/helper.php';
require_once '../koneksi.php';
if (!is_logged_in() || !has_role('operator')) { header('Location: ../auth/login.php'); exit; }
require_password_changed('../auth/change_password.php');
$is_admin  = has_role('administrator');
$ibadah_id = get_ibadah_id();
$page_title    = 'Kebutuhan & Papan Publik';
$page_subtitle = 'Kelola kebutuhan warga dan pantau papan publik';
$active_nav    = 'kebutuhan';
include '../includes/page-start.php';
?>
<script>window._IBADAH_ID=<?= $ibadah_id?(int)$ibadah_id:'null'?>;window._IS_ADMIN=<?= $is_admin?'true':'false'?>;</script>

<style>
.keb-wrap{display:grid;grid-template-columns:1fr 360px;gap:16px;align-items:start;}
@media(max-width:900px){.keb-wrap{grid-template-columns:1fr;}}
.keb-list-item{padding:10px 16px;border-bottom:1px solid var(--hd-border);cursor:pointer;transition:background .15s;}
.keb-list-item:hover{background:#ede8e2;}
.keb-list-item.active{background:#ede8e2;border-left:3px solid #0d7490;padding-left:13px;}
.keb-open-badge{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;border-radius:9999px;background:#fef2f2;color:#ef4444;font-size:11px;font-weight:500;padding:0 6px;}
.keb-status-sel{padding:5px 10px;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;border:1px solid var(--card-border);background:#fafaf9;font-family:var(--font);color:var(--text-secondary);}
.keb-status-sel.belum{background:#f5f0eb;color:#7a7067;border-color:#c5bdb5;}
.keb-status-sel.proses{background:#fffbeb;color:#d97706;border-color:#fcd34d;}
.keb-status-sel.terpenuhi{background:#f0fdf4;color:#16a34a;border-color:#86efac;}
        /* ── Tab navigation ─────────────────────────────────────────── */
        .tab-nav{display:flex;gap:2px;margin-bottom:16px;border-bottom:2px solid var(--card-border);padding-bottom:0;}
        .tab-btn{background:none;border:none;padding:10px 18px;font-family:var(--font);font-size:13px;font-weight:500;
            color:var(--text-muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;
            display:inline-flex;align-items:center;gap:6px;transition:color .15s;}
        .tab-btn:hover{color:var(--text-primary);}
        .tab-btn.active{color:#0d7490;border-bottom-color:#0d7490;}
        .tab-badge{background:#ef4444;color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;
            border-radius:9999px;display:inline-flex;align-items:center;justify-content:center;padding:0 5px;}
        /* ── Donor inbox cards ──────────────────────────────────────── */
        .donor-card{padding:14px 16px;border-bottom:1px solid var(--hd-border);}
        .donor-card.unread{border-left:3px solid #0d7490;padding-left:13px;background:#f7fbfd;}
        .donor-card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;}
        .donor-nama{font-weight:600;font-size:14px;}
        .donor-meta{font-size:11px;color:var(--text-muted);}
        .donor-kontak{font-size:12px;color:var(--text-secondary);margin-bottom:6px;display:flex;align-items:center;gap:4px;}
        .keb-copyable{cursor:pointer;border-radius:5px;padding:2px 5px;margin:-2px -5px;transition:background .15s,color .15s;}
        .keb-copyable:hover{background:#ede8e2;color:var(--text-primary);}
        .donor-pesan{font-size:13px;line-height:1.5;margin-bottom:8px;}
        .donor-actions{display:flex;gap:8px;flex-wrap:wrap;}
        .btn-hubungi{background:#16a34a;color:#fff;border:none;border-radius:6px;padding:5px 12px;
            font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:4px;text-decoration:none;}
        .btn-hubungi:hover{background:#15803d;}
        .btn-hapus{background:transparent;color:#ef4444;border:1px solid #fca5a5;border-radius:6px;
            padding:5px 12px;font-size:12px;font-weight:600;cursor:pointer;}
        .btn-hapus:hover{background:#fef2f2;}
        /* ── Toast ──────────────────────────────────────────────────── */
        .keb-toast{position:fixed;bottom:24px;right:24px;z-index:10000;display:flex;align-items:center;gap:10px;
            padding:12px 18px;border-radius:10px;font-size:13px;font-weight:500;box-shadow:0 4px 16px rgba(32,21,21,.15);
            transform:translateY(12px);opacity:0;pointer-events:none;transition:opacity .2s,transform .2s;}
        .keb-toast.show{opacity:1;transform:translateY(0);pointer-events:auto;}
        .keb-toast.success{background:#f0fdf4;color:#15803d;border:1px solid #86efac;}
        .keb-toast.error{background:#fef2f2;color:#dc2626;border:1px solid #fca5a5;}
</style>

<div class="tab-nav">
    <button class="tab-btn active" data-tab="kebutuhan" onclick="switchTab('kebutuhan')">
        <i data-lucide="heart" style="width:14px;height:14px;"></i> Kebutuhan Warga
    </button>
    <button class="tab-btn" data-tab="donatur" onclick="switchTab('donatur')">
        <i data-lucide="mail" style="width:14px;height:14px;"></i> Pesan Donatur
        <span class="tab-badge" id="donaturBadge" style="display:none;"></span>
    </button>
</div>

<div id="tabKebutuhan" class="tab-panel">
<div class="keb-wrap">
    <!-- Left: penduduk list -->
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">Penduduk dengan Kebutuhan</div>
            <a href="../papan-kebutuhan.php" target="_blank" class="btn-see-all">🌐 Papan Publik</a>
        </div>
        <div style="padding:10px 14px;border-bottom:1px solid var(--hd-border);">
            <input type="text" id="kebSearch" style="width:100%;padding:7px 12px;border:1px solid var(--card-border);border-radius:8px;font-family:var(--font);font-size:13px;outline:none;" placeholder="Cari nama KK..." oninput="filterKebList()">
        </div>
        <div id="kebList" style="max-height:65vh;overflow-y:auto;">
            <div style="padding:20px;text-align:center;color:var(--text-muted);"><span class="spinner"></span> Memuat...</div>
        </div>
    </div>

    <!-- Right: kebutuhan detail -->
    <div class="dash-card" id="kebDetail" style="position:sticky;top:16px;">
        <div class="dash-card-title" id="kebDetailTitle" style="margin-bottom:12px;">Detail Kebutuhan</div>
        <div id="kebDetailBody" style="font-size:13px;color:var(--text-muted);text-align:center;padding:32px 0;">
            Pilih warga di sebelah kiri untuk melihat dan mengelola kebutuhannya.
        </div>
    </div>
</div>
</div><!-- #tabKebutuhan -->

<div id="kebToast" class="keb-toast" role="alert" aria-live="polite"></div>

<div id="tabDonatur" class="tab-panel" style="display:none;">
<?php if ($is_admin): ?>
    <div class="table-card">
        <div class="table-card-header">
            <div>
                <div class="table-card-title">Pesan dari Calon Donatur</div>
                <div class="table-card-count" id="donaturCount"></div>
            </div>
            <button class="btn-primary" id="btnMarkAll" onclick="markAllRead()"
                style="font-size:12px;padding:6px 14px;white-space:nowrap;">
                Tandai Semua Sudah Dibaca
            </button>
        </div>
        <div id="donaturList" style="padding:16px;">
            <span class="spinner"></span> Memuat...
        </div>
    </div>
<?php else: ?>
    <div class="dash-card" style="text-align:center;padding:48px 24px;color:var(--text-muted);">
        <div style="font-size:13px;">Hanya administrator yang dapat melihat pesan donatur.</div>
    </div>
<?php endif; ?>
</div><!-- #tabDonatur -->

<script>
let _kebAll = [], _kebSelectedId = null;
function esc(s){const d=document.createElement('div');d.appendChild(document.createTextNode(s??''));return d.innerHTML;}
function attr(s){return String(s??'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

async function loadKeb() {
    try {
        const r = await fetch('../api/penduduk/ambil.php?_='+Date.now());
        const j = await r.json();
        if (j.status !== 'success') return;
        let data = j.data.filter(p => p.status_verifikasi === 'Terverifikasi');
        if (window._IBADAH_ID && !window._IS_ADMIN) {
            data = data.filter(p => parseInt(p.ibadah_id) === window._IBADAH_ID);
        }
        _kebAll = data;
        filterKebList();
    } catch(e) {
        document.getElementById('kebList').innerHTML = '<div style="padding:16px;color:#ef4444;">Gagal memuat data.</div>';
    }
}

function filterKebList() {
    const q = document.getElementById('kebSearch').value.toLowerCase();
    const rows = _kebAll.filter(p => (p.nama_kk||'').toLowerCase().includes(q));
    const el = document.getElementById('kebList');
    if (!rows.length) {
        el.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted);">Tidak ada data.</div>';
        return;
    }
    el.innerHTML = rows.map(p => {
        const open = parseInt(p.kebutuhan_open||0) + parseInt(p.kebutuhan_proses||0);
        const active = _kebSelectedId === p.id ? ' active' : '';
        return `<div class="keb-list-item${active}" data-id="${attr(parseInt(p.id, 10) || 0)}" data-nama="${attr(p.nama_kk||'?')}" onclick="selectPenduduk(parseInt(this.dataset.id, 10), this.dataset.nama)">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-weight:600;font-size:13px;">${esc(p.nama_kk||'—')}</span>
                <span class="keb-open-badge">${open}</span>
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">${esc(p.nama_ibadah||'Blank Spot')} · ${esc(p.kategori||'—')}</div>
        </div>`;
    }).join('');
}

const KEB_KATEGORI = ['Sembako','Biaya Sekolah','Biaya Kesehatan','Modal Usaha','Renovasi Rumah','Perlengkapan Rumah','Pakaian','Lainnya'];

async function selectPenduduk(id, nama) {
    _kebSelectedId = id;
    filterKebList();
    document.getElementById('kebDetailTitle').textContent = nama;
    document.getElementById('kebDetailBody').innerHTML = '<span class="spinner"></span> Memuat...';
    try {
        const r = await fetch('../api/kebutuhan/ambil.php?penduduk_id='+id+'&_='+Date.now());
        const j = await r.json();
        const clsMap = {'Belum Terpenuhi':'belum','Dalam Proses':'proses','Terpenuhi':'terpenuhi'};

        const listHtml = (!j.data || !j.data.length)
            ? '<p style="color:var(--text-muted);font-size:13px;margin-bottom:4px;">Belum ada kebutuhan tercatat.</p>'
            : j.data.map(k => `
                <div style="padding:10px 0;border-bottom:1px solid var(--hd-border);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                        <span style="font-weight:600;font-size:13px;">${esc(k.kategori)}</span>
                        <select class="keb-status-sel ${clsMap[k.status]||''}"
                            data-id="${attr(parseInt(k.id,10)||0)}" data-orig="${attr(k.status||'')}" onchange="updateKeb(this)">
                            <option ${k.status==='Belum Terpenuhi'?'selected':''}>Belum Terpenuhi</option>
                            <option ${k.status==='Dalam Proses'?'selected':''}>Dalam Proses</option>
                            <option ${k.status==='Terpenuhi'?'selected':''}>Terpenuhi</option>
                        </select>
                    </div>
                    ${k.deskripsi?`<div style="font-size:12px;color:var(--text-muted);">${esc(k.deskripsi)}</div>`:''}
                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Oleh ${esc(k.created_by_nama||'—')} · ${esc((k.created_at||'').slice(0,10))}</div>
                </div>`).join('');

        const addFormHtml = `
            <div style="margin-top:14px;padding-top:14px;border-top:2px dashed var(--hd-border);">
                <div style="font-size:11px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Tambah Kebutuhan</div>
                <select id="kebAddKat_${id}" style="width:100%;padding:7px 10px;border:1px solid var(--card-border);border-radius:7px;font-family:var(--font);font-size:13px;background:#fafaf9;color:var(--text-primary);outline:none;margin-bottom:8px;">
                    ${KEB_KATEGORI.map(k=>`<option value="${esc(k)}">${esc(k)}</option>`).join('')}
                </select>
                <input type="text" id="kebAddDesc_${id}" placeholder="Deskripsi (opsional, maks 300 karakter)" maxlength="300"
                    style="width:100%;padding:7px 10px;border:1px solid var(--card-border);border-radius:7px;font-family:var(--font);font-size:13px;background:#fafaf9;color:var(--text-primary);outline:none;margin-bottom:8px;box-sizing:border-box;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <button onclick="addKebAPI(${id})" id="kebAddBtn_${id}" class="btn-primary" style="padding:7px 18px;font-size:13px;">Simpan</button>
                    <span id="kebAddMsg_${id}" style="font-size:12px;color:#ef4444;"></span>
                </div>
            </div>`;

        document.getElementById('kebDetailBody').innerHTML = listHtml + addFormHtml;
    } catch(e) {
        document.getElementById('kebDetailBody').innerHTML = '<span style="color:#ef4444;">Gagal memuat kebutuhan.</span>';
    }
}

async function addKebAPI(id) {
    const kat  = document.getElementById(`kebAddKat_${id}`).value;
    const desc = document.getElementById(`kebAddDesc_${id}`).value.trim();
    const btn  = document.getElementById(`kebAddBtn_${id}`);
    const msg  = document.getElementById(`kebAddMsg_${id}`);
    btn.disabled = true; btn.textContent = 'Menyimpan...'; msg.textContent = '';
    const fd = new FormData();
    fd.append('penduduk_id', id); fd.append('kategori', kat); fd.append('deskripsi', desc);
    try {
        const r = await fetch('../api/kebutuhan/simpan.php', { method:'POST', body: appendCsrf(fd) });
        const j = await r.json();
        if (j.status === 'success') {
            loadKeb();
            selectPenduduk(id, document.getElementById('kebDetailTitle').textContent);
        } else {
            msg.textContent = j.message || 'Gagal menyimpan.';
            btn.disabled = false; btn.textContent = 'Simpan';
        }
    } catch(e) {
        msg.textContent = 'Gagal terhubung ke server.';
        btn.disabled = false; btn.textContent = 'Simpan';
    }
}

async function updateKeb(sel) {
    const id  = sel.dataset.id;
    const orig = sel.dataset.orig;
    const catatan = prompt('Catatan perubahan kebutuhan (opsional):', '');
    if (catatan === null) {
        sel.value = orig;
        return;
    }
    const fd  = new FormData();
    fd.append('id', id); fd.append('status', sel.value); fd.append('catatan', catatan);
    try {
        const r = await fetch('../api/kebutuhan/update_status.php', {method:'POST', body: appendCsrf(fd)});
        const j = await r.json();
        if (j.status === 'success') {
            sel.dataset.orig = sel.value;
            const clsMap = {'Belum Terpenuhi':'belum','Dalam Proses':'proses','Terpenuhi':'terpenuhi'};
            sel.className = 'keb-status-sel ' + (clsMap[sel.value]||'');
            loadKeb();
        } else { alert(j.message||'Gagal update.'); }
    } catch(e) { alert('Gagal terhubung ke server.'); }
}

loadKeb();
if (window._IS_ADMIN) loadDonorInbox();

// ── Toast ─────────────────────────────────────────────────────────────
let _kebToastTimer = null;
function showKebToast(msg, type) {
    const el = document.getElementById('kebToast');
    if (!el) return;
    clearTimeout(_kebToastTimer);
    el.className = 'keb-toast ' + (type || 'success');
    el.textContent = msg;
    el.classList.add('show');
    _kebToastTimer = setTimeout(function() { el.classList.remove('show'); }, 3500);
}

// ── Confirm dialog ────────────────────────────────────────────────────
function showKebConfirm(title, message) {
    return new Promise(function(resolve) {
        const el = document.createElement('div');
        el.className = 'confirm-overlay';
        el.innerHTML = '<div class="confirm-box">'
            + '<div class="confirm-title">' + esc(title) + '</div>'
            + '<p class="confirm-msg">' + esc(message) + '</p>'
            + '<div class="confirm-actions">'
            +   '<button class="btn-secondary" id="_kc_cancel">Batal</button>'
            +   '<button class="btn-danger" id="_kc_ok">Ya, Hapus</button>'
            + '</div></div>';
        document.body.appendChild(el);
        const done = function(val) { el.remove(); resolve(val); };
        el.querySelector('#_kc_ok').onclick     = function() { done(true); };
        el.querySelector('#_kc_cancel').onclick = function() { done(false); };
        el.addEventListener('click', function(e) { if (e.target === el) done(false); });
    });
}

// ── Tab system ────────────────────────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(function(b) {
        b.classList.toggle('active', b.dataset.tab === tab);
    });
    document.getElementById('tabKebutuhan').style.display = tab === 'kebutuhan' ? '' : 'none';
    document.getElementById('tabDonatur').style.display   = tab === 'donatur'   ? '' : 'none';
    if (tab === 'donatur' && window._IS_ADMIN && !_donorLoaded) loadDonorInbox();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// ── Donor inbox ───────────────────────────────────────────────────────
function copyKontak(el) {
    const text = el.dataset.copy || '';
    if (!text) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showKebToast('Tersalin: ' + text, 'success');
        }).catch(function() { _fallbackCopy(text); });
    } else {
        _fallbackCopy(text);
    }
}
function _fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0;';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); showKebToast('Tersalin: ' + text, 'success'); }
    catch(e) { showKebToast('Gagal menyalin.', 'error'); }
    ta.remove();
}

function buildKontakUrl(kontak) {
    const trimmed = (kontak || '').trim();
    if (trimmed.includes('@')) return 'mailto:' + trimmed;
    const digits = trimmed.replace(/\D/g, '');
    if (digits.length >= 8) {
        const normalized = digits.startsWith('0') ? '62' + digits.slice(1) : digits;
        return 'https://wa.me/' + normalized;
    }
    return 'mailto:' + trimmed;
}

let _donorData = [], _donorLoaded = false;

async function loadDonorInbox() {
    const list = document.getElementById('donaturList');
    if (!list) return;
    list.innerHTML = '<span class="spinner"></span> Memuat...';
    try {
        const r = await fetch('../api/papan/donatur.php?_=' + Date.now());
        const j = await r.json();
        if (j.status !== 'success') throw new Error(j.message || 'Gagal memuat.');
        _donorData = j.data || [];
        _donorLoaded = true;
        renderDonorInbox(_donorData);
    } catch (e) {
        list.innerHTML = '<div style="color:#ef4444;padding:8px;">Gagal memuat pesan: ' + esc(e.message) + '</div>';
    }
}

function renderDonorInbox(data) {
    const list    = document.getElementById('donaturList');
    const badge   = document.getElementById('donaturBadge');
    const counter = document.getElementById('donaturCount');
    if (!list) return;

    const unread = data.filter(function(d) { return !parseInt(d.is_read); }).length;
    if (badge) { badge.textContent = unread; badge.style.display = unread > 0 ? '' : 'none'; }
    if (counter) counter.textContent = data.length + ' pesan' + (unread > 0 ? ', ' + unread + ' belum dibaca' : '');

    if (!data.length) {
        list.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted);font-size:13px;">Belum ada pesan masuk dari calon donatur.</div>';
        return;
    }

    list.innerHTML = data.map(function(d) {
        const isUnread  = !parseInt(d.is_read);
        const kontakUrl = buildKontakUrl(d.kontak || '');
        const isWa      = kontakUrl.startsWith('https://wa.me');
        const safeId    = parseInt(d.id, 10) || 0;
        return '<div class="donor-card' + (isUnread ? ' unread' : '') + '" id="dc-' + safeId + '">'
            + '<div class="donor-card-header">'
            +   '<div style="display:flex;align-items:center;gap:8px;">'
            +     '<span class="donor-nama">' + esc(d.nama) + '</span>'
            +     (isUnread ? '<span style="display:inline-block;width:7px;height:7px;background:#0d7490;border-radius:50%;"></span>' : '')
            +     (d.kategori_minat ? '<span class="badge-pill" style="background:#eff6ff;color:#2563eb;">' + esc(d.kategori_minat) + '</span>' : '')
            +   '</div>'
            +   '<span class="donor-meta">' + esc((d.created_at || '').slice(0, 10)) + '</span>'
            + '</div>'
            + '<div class="donor-kontak keb-copyable" data-copy="' + attr(d.kontak || '') + '" onclick="copyKontak(this)" title="Klik untuk salin">'
            +   '<i data-lucide="copy" style="width:11px;height:11px;opacity:.5;flex-shrink:0;"></i> '
            +   esc(d.kontak)
            + '</div>'
            + (d.pesan ? '<div class="donor-pesan">' + esc(d.pesan) + '</div>' : '')
            + '<div class="donor-actions">'
            +   '<a href="' + attr(kontakUrl) + '" target="_blank" rel="noopener" class="btn-hubungi">'
            +     (isWa ? 'WhatsApp' : 'Email')
            +   '</a>'
            +   '<button class="btn-hapus" onclick="hapusDonatur(' + safeId + ')">Hapus</button>'
            + '</div>'
            + '</div>';
    }).join('');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function markAllRead() {
    const btn = document.getElementById('btnMarkAll');
    if (btn) { btn.disabled = true; btn.textContent = 'Memproses...'; }
    const fd = new FormData();
    fd.append('action', 'mark_all');
    try {
        const r = await fetch('../api/papan/donatur.php', { method: 'POST', body: appendCsrf(fd) });
        const j = await r.json();
        if (j.status !== 'success') throw new Error(j.message || 'Gagal.');
        _donorData = _donorData.map(function(d) { return Object.assign({}, d, { is_read: '1' }); });
        renderDonorInbox(_donorData);
        showKebToast('Semua pesan ditandai sudah dibaca.', 'success');
    } catch (e) {
        showKebToast('Gagal menandai: ' + e.message, 'error');
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Tandai Semua Sudah Dibaca'; }
    }
}

async function hapusDonatur(id) {
    const ok = await showKebConfirm('Hapus Pesan', 'Hapus pesan donatur ini secara permanen?');
    if (!ok) return;
    const fd = new FormData();
    fd.append('action', 'hapus');
    fd.append('id', id);
    try {
        const r = await fetch('../api/papan/donatur.php', { method: 'POST', body: appendCsrf(fd) });
        const j = await r.json();
        if (j.status !== 'success') throw new Error(j.message || 'Gagal hapus.');
        _donorData = _donorData.filter(function(d) { return parseInt(d.id) !== id; });
        renderDonorInbox(_donorData);
        showKebToast('Pesan dihapus.', 'success');
    } catch (e) {
        showKebToast('Gagal menghapus: ' + e.message, 'error');
    }
}
</script>

<?php include '../includes/page-end.php'; ?>
