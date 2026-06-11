<?php
require_once '../config.php';
require_once '../auth/helper.php';
require_once '../koneksi.php';
if (!is_logged_in() || !has_role('administrator')) { header('Location: ../auth/login.php'); exit; }
require_password_changed('../auth/change_password.php');
$page_title    = 'Laporan & Export';
$page_subtitle = 'Export data binaan per rumah ibadah';
$active_nav    = 'laporan';
include '../includes/page-start.php';
?>

<div style="max-width:640px;">
    <div class="dash-card" style="margin-bottom:20px;">
        <div class="dash-card-title">Pilih Rumah Ibadah</div>
        <select id="lapIbadah" style="width:100%;padding:10px 12px;border:1px solid var(--card-border);border-radius:8px;font-family:var(--font);font-size:14px;color:var(--text-primary);background:#fafaf9;cursor:pointer;margin-bottom:12px;outline:none;" onchange="loadPreview()">
            <option value="">-- Pilih Rumah Ibadah --</option>
        </select>
        <div id="lapPreview" style="display:none;">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;" id="lapCards"></div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a id="btnCsv" href="" aria-disabled="true" onclick="return this.getAttribute('aria-disabled') !== 'true';" class="btn-primary disabled" style="text-decoration:none;padding:10px 20px;display:inline-flex;align-items:center;gap:6px;"><i data-lucide="download"></i> Download CSV</a>
                <a id="btnPdf" href="" aria-disabled="true" onclick="return this.getAttribute('aria-disabled') !== 'true';" target="_blank" class="btn-secondary disabled" style="text-decoration:none;padding:10px 20px;display:inline-flex;align-items:center;gap:6px;"><i data-lucide="printer"></i> Cetak PDF</a>
            </div>
            <p style="font-size:11px;color:var(--text-muted);margin-top:10px;">CSV tidak menyertakan NIK untuk meminimalkan risiko penyebaran data sensitif.</p>
        </div>
        <div id="lapMsg" style="font-size:13px;margin-top:8px;color:var(--text-muted);"></div>
    </div>
</div>

<script>
function esc(s){const d=document.createElement('div');d.appendChild(document.createTextNode(s??''));return d.innerHTML;}
function fmt(n){return Number(n||0).toLocaleString('id-ID');}

async function loadIbadahList() {
    try {
        const r = await fetch('../api/ibadah/ambil.php?_='+Date.now());
        const j = await r.json();
        if (j.status !== 'success') return;
        const sel = document.getElementById('lapIbadah');
        j.data.forEach(ib => {
            const o = document.createElement('option');
            o.value = ib.id;
            o.textContent = '['+ib.jenis+'] '+ib.nama+' ('+fmt(ib.total_kk||0)+' KK)';
            o.dataset.kk   = ib.total_kk   || 0;
            o.dataset.jiwa = ib.total_jiwa  || 0;
            sel.appendChild(o);
        });
    } catch(e) {
        document.getElementById('lapMsg').textContent = 'Gagal memuat daftar ibadah.';
    }
}

async function loadPreview() {
    const sel = document.getElementById('lapIbadah');
    const id  = sel.value;
    const preview = document.getElementById('lapPreview');
    if (!id) { preview.style.display = 'none'; return; }

    const opt  = sel.selectedOptions[0];
    const kk   = opt.dataset.kk   || 0;
    const jiwa = opt.dataset.jiwa  || 0;

    document.getElementById('lapMsg').innerHTML = '<span class="spinner"></span> Memuat statistik...';
    preview.style.display = 'none';

    try {
        const r = await fetch('../api/stats/ambil.php?_='+Date.now());
        const j = await r.json();
        document.getElementById('lapMsg').textContent = '';
        let pct = 0;
        if (j.status === 'success') {
            const stat = (j.data.rekap_ibadah || []).find(x => String(x.id) === String(id));
            if (stat && parseInt(stat.jml_kk) > 0) {
                pct = Math.round(parseInt(stat.jml_ditangani) / parseInt(stat.jml_kk) * 100);
            }
        }
        document.getElementById('lapCards').innerHTML =
            `<div class="need-stat"><div class="need-stat-val blue">${fmt(kk)}</div><div class="need-stat-lbl">KK Binaan</div></div>
             <div class="need-stat"><div class="need-stat-val">${fmt(jiwa)}</div><div class="need-stat-lbl">Jiwa Binaan</div></div>
             <div class="need-stat"><div class="need-stat-val green">${esc(String(pct))}%</div><div class="need-stat-lbl">Sudah Ditangani</div></div>`;
        document.getElementById('btnCsv').href = '../api/export/csv.php?ibadah_id=' + encodeURIComponent(id);
        document.getElementById('btnPdf').href = '../api/export/pdf.php?ibadah_id=' + encodeURIComponent(id);
        document.getElementById('btnCsv').setAttribute('aria-disabled', 'false');
        document.getElementById('btnPdf').setAttribute('aria-disabled', 'false');
        document.getElementById('btnCsv').classList.remove('disabled');
        document.getElementById('btnPdf').classList.remove('disabled');
        preview.style.display = '';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch(e) {
        document.getElementById('lapMsg').textContent = 'Gagal memuat statistik.';
    }
}

loadIbadahList();
</script>

<?php include '../includes/page-end.php'; ?>
