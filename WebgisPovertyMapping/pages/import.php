<?php
require_once '../config.php';
require_once '../auth/helper.php';
require_once '../koneksi.php';
if (!is_logged_in() || !has_role('administrator')) { header('Location: ../auth/login.php'); exit; }
require_password_changed('../auth/change_password.php');
$page_title    = 'Import CSV';
$page_subtitle = 'Upload data penduduk miskin dari file CSV';
$active_nav    = 'import';
include '../includes/page-start.php';
?>

<div style="max-width:700px;">
    <div class="dash-card">
        <div class="dash-card-title">Import Data Penduduk via CSV</div>

        <div style="display:flex;gap:10px;margin-bottom:20px;align-items:center;flex-wrap:wrap;">
            <a href="../api/import/template.php" class="btn-primary" style="text-decoration:none;padding:9px 16px;font-size:13px;display:inline-flex;align-items:center;gap:6px;"><i data-lucide="download"></i> Download Template CSV</a>
            <span style="font-size:12px;color:var(--text-muted);">Maks. 500 baris per upload</span>
            <span style="font-size:12px;color:var(--text-muted);">Data valid otomatis masuk sebagai Terverifikasi.</span>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:11px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Pilih File CSV</label>
            <input type="file" id="importFile" accept=".csv" style="font-size:13px;color:var(--text-primary);" onchange="previewCSV()">
        </div>

        <div id="importPreviewArea" style="display:none;">
            <div id="importSummary" style="margin-bottom:12px;font-size:13px;"></div>
            <div id="importPreviewTable" style="overflow-x:auto;margin-bottom:12px;"></div>
            <div id="importErrorList" style="display:none;margin-bottom:12px;"></div>
            <button id="btnDoImport" class="btn-primary" style="font-size:14px;display:inline-flex;align-items:center;gap:6px;" onclick="doImport()"><i data-lucide="check"></i> Import Sekarang</button>
        </div>
        <div id="importResultMsg" style="font-size:13px;margin-top:12px;min-height:16px;font-family:var(--font);"></div>
    </div>
</div>

<script>
function esc(s){const d=document.createElement('div');d.appendChild(document.createTextNode(String(s??'')));return d.innerHTML;}

async function previewCSV() {
    const file = document.getElementById('importFile').files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('csv_file', file);
    fd.append('mode', 'preview');
    document.getElementById('importPreviewArea').style.display = 'none';
    const msg = document.getElementById('importResultMsg');
    msg.style.color = 'var(--text-muted)';
    msg.innerHTML = '<span class="spinner"></span> Menganalisis...';
    try {
        const res = await fetch('../api/import/csv.php', {method:'POST', body: appendCsrf(fd)});
        const j   = await res.json();
        msg.textContent = '';
        if (j.status !== 'success') {
            msg.style.color = '#ef4444';
            msg.innerHTML = '<i data-lucide="x-circle" style="color:#ef4444;vertical-align:middle;margin-right:4px;"></i> ' + esc(j.message || 'Error tidak diketahui.');
            if (typeof lucide !== 'undefined') lucide.createIcons();
            return;
        }
        document.getElementById('importSummary').innerHTML =
            `<span style="color:#16a34a;font-weight:600;">${esc(String(j.total_ok))} baris valid</span>` +
            (j.total_error > 0
                ? ` &nbsp;·&nbsp; <span style="color:#ef4444;font-weight:600;">${esc(String(j.total_error))} baris error</span>`
                : '');
        if (j.preview && j.preview.length) {
            const cols = ['nama_kk','nik','jumlah_jiwa','kategori','lat','lng'];
            document.getElementById('importPreviewTable').innerHTML =
                `<table style="width:100%;border-collapse:collapse;background:#fafaf9;border:1px solid var(--card-border);border-radius:8px;overflow:hidden;font-size:12px;">
                    <thead><tr>${cols.map(c=>`<th style="padding:6px 10px;font-size:10px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;background:#ede8e2;text-align:left;">${esc(c)}</th>`).join('')}</tr></thead>
                    <tbody>${j.preview.map(row=>'<tr>'+cols.map(c=>`<td style="padding:5px 10px;border-top:1px solid var(--card-border);">${esc(String(row[c]??''))}</td>`).join('')+'</tr>').join('')}</tbody>
                </table>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Menampilkan ${esc(String(j.preview.length))} dari ${esc(String(j.total_ok))} baris valid</div>`;
        } else {
            document.getElementById('importPreviewTable').innerHTML =
                '<div style="font-size:12px;color:var(--text-muted);padding:10px 0;">Tidak ada baris valid untuk ditampilkan.</div>';
        }
        if (j.errors && j.errors.length) {
            document.getElementById('importErrorList').innerHTML =
                `<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;">
                    ${j.errors.slice(0,10).map(e=>`<div style="font-size:11px;color:#ef4444;padding:2px 0;">Baris ${esc(String(e.row))}: ${esc(e.error)}</div>`).join('')}
                    ${j.errors.length>10?`<div style="font-size:11px;color:var(--text-muted);">...dan ${esc(String(j.errors.length-10))} error lainnya</div>`:''}
                </div>`;
            document.getElementById('importErrorList').style.display = '';
        } else {
            document.getElementById('importErrorList').style.display = 'none';
        }
        document.getElementById('importPreviewArea').style.display = '';
        document.getElementById('btnDoImport').disabled = (j.total_ok === 0);
    } catch(e) {
        msg.style.color = '#ef4444';
        msg.innerHTML = '<i data-lucide="x-circle" style="color:#ef4444;vertical-align:middle;margin-right:4px;"></i> Gagal menghubungi server.';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

async function doImport() {
    const file = document.getElementById('importFile').files[0];
    if (!file) return;
    const btn = document.getElementById('btnDoImport');
    const msg = document.getElementById('importResultMsg');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Mengimport...';
    msg.textContent = '';
    try {
        const fd = new FormData();
        fd.append('csv_file', file);
        fd.append('mode', 'import');
        const res = await fetch('../api/import/csv.php', {method:'POST', body: appendCsrf(fd)});
        const j   = await res.json();
        if (j.status === 'success') {
            msg.style.color = '#16a34a';
            msg.innerHTML = '<i data-lucide="check-circle" style="color:#16a34a;vertical-align:middle;margin-right:4px;"></i> ' + esc(j.message);
            document.getElementById('importFile').value = '';
            document.getElementById('importPreviewArea').style.display = 'none';
            document.getElementById('importErrorList').style.display  = 'none';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } else {
            msg.style.color = '#ef4444';
            msg.innerHTML = '<i data-lucide="x-circle" style="color:#ef4444;vertical-align:middle;margin-right:4px;"></i> ' + esc(j.message || 'Import gagal.');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    } catch(e) {
        msg.style.color = '#ef4444';
        msg.innerHTML = '<i data-lucide="x-circle" style="color:#ef4444;vertical-align:middle;margin-right:4px;"></i> Gagal menghubungi server.';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
    btn.disabled    = false;
    btn.innerHTML = '<i data-lucide="check"></i> Import Sekarang';
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
</script>

<?php include '../includes/page-end.php'; ?>
