<?php
require_once 'config.php';
require_once 'auth/helper.php';
$is_admin = is_logged_in() && has_role('administrator');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Kebutuhan Publik — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --c-bg:#f5f0eb;--c-surface:#fafaf9;--c-surface2:#ede8e2;
            --c-border:#ddd8d2;--c-text:#201515;--c-muted:#7a7067;
            --c-accent:#0d7490;--c-accent-h:#0a5f7a;--c-warn:#f59e0b;
            --c-danger:#ef4444;--c-info:#3b82f6;
            --font-body:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
        }
        body{background:var(--c-bg);color:var(--c-text);font-family:var(--font-body);min-height:100vh;padding:24px 16px;}
        .container{max-width:860px;margin:0 auto;}
        header{margin-bottom:32px;}
        header h1{font-size:22px;font-weight:600;letter-spacing:-0.5px;color:var(--c-text);margin-bottom:6px;display:flex;align-items:center;gap:8px;}
        header p{font-size:13px;color:var(--c-muted);line-height:1.6;max-width:600px;}
        .back-link{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--c-muted);
            text-decoration:none;margin-bottom:20px;transition:color .15s;}
        .back-link:hover{color:var(--c-text);}
        .board-grid{display:flex;flex-direction:column;gap:8px;margin-bottom:32px;}
        .board-row{background:var(--c-surface);border:1px solid var(--c-border);border-radius:12px;
            padding:14px 16px;display:flex;align-items:center;gap:14px;cursor:pointer;transition:border-color .15s,box-shadow .15s;}
        .board-row:hover{border-color:#0d7490;box-shadow:0 2px 8px rgba(32,21,21,.06);}
        .board-icon{flex-shrink:0;width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:var(--c-surface2);border-radius:8px;}
        .board-info{flex:1;}
        .board-title{font-size:14px;font-weight:600;color:var(--c-text);}
        .board-sub{font-size:12px;color:var(--c-muted);margin-top:3px;}
        .board-count{font-size:22px;font-weight:600;letter-spacing:-0.5px;color:#ef4444;flex-shrink:0;}
        .board-count-label{font-size:10px;color:var(--c-muted);text-align:right;}
        .btn-help{background:#0d7490;color:#fff;border:none;border-radius:8px;padding:8px 16px;
            font-size:12px;font-weight:600;cursor:pointer;transition:background .15s;white-space:nowrap;}
        .btn-help:hover{background:#0a5f7a;}
        .empty-state{text-align:center;padding:48px 24px;color:var(--c-muted);font-size:14px;}
        .section-title{font-size:11px;font-weight:600;color:var(--c-muted);text-transform:uppercase;
            letter-spacing:.6px;margin-bottom:12px;}

        /* Form donatur */
        .form-panel{background:var(--c-surface);border:1px solid var(--c-border);border-radius:12px;
            padding:20px;margin-top:24px;display:none;box-shadow:0 4px 12px rgba(32,21,21,.08);}
        .form-panel.show{display:block;}
        .form-title{font-size:15px;font-weight:600;letter-spacing:-0.3px;margin-bottom:4px;display:flex;align-items:center;gap:6px;}
        .form-sub{font-size:12px;color:var(--c-muted);margin-bottom:16px;}
        .form-group{margin-bottom:12px;}
        .form-group label{display:block;font-size:13px;font-weight:500;color:#3d3530;margin-bottom:5px;}
        .form-group input,.form-group select,.form-group textarea{
            width:100%;padding:9px 12px;background:#fafaf9;border:1px solid #ddd8d2;
            border-radius:8px;color:#201515;font-family:var(--font-body);font-size:13px;outline:none;
            transition:border-color .15s;}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#0d7490;}
        .form-group textarea{resize:vertical;min-height:80px;}
        .form-actions{display:flex;gap:10px;margin-top:16px;}
        .btn-submit{background:#0d7490;color:#fff;border:none;border-radius:8px;padding:9px 20px;
            font-size:13px;font-weight:600;cursor:pointer;flex:1;transition:background .15s;display:inline-flex;align-items:center;justify-content:center;gap:6px;}
        .btn-submit:hover:not(:disabled){background:#0a5f7a;}
        .btn-submit:disabled{opacity:.6;cursor:not-allowed;}
        .btn-cancel{background:transparent;color:var(--c-muted);border:1px solid var(--c-border);
            border-radius:8px;padding:9px 16px;font-size:13px;cursor:pointer;}
        .form-status{font-size:12px;margin-top:8px;padding:6px 10px;border-radius:6px;display:inline-flex;align-items:center;gap:6px;}
        .form-status.success{background:#f0fdf4;color:#16a34a;}
        .form-status.error{background:#fef2f2;color:#ef4444;}

        footer{background:#1c1612;color:#a89f96;padding:20px 24px;margin-top:48px;
            border-radius:12px;font-size:12px;text-align:center;}
        footer a{color:#a89f96;text-decoration:none;}
        footer a:hover{color:#ffffff;}

        /* Lucide Icons */
        .lucide { width: 16px; height: 16px; display: inline-block; vertical-align: middle; stroke-width: 2px; }
        .board-icon .lucide { width: 20px; height: 20px; color: var(--c-accent); }
        .spinner {
            display: inline-block; width: 14px; height: 14px;
            border: 2px solid var(--c-border); border-top-color: var(--c-accent);
            border-radius: 50%; animation: spin .7s linear infinite;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div class="container">
    <a href="index.php" class="back-link"><i data-lucide="arrow-left"></i> Kembali ke Peta</a>

    <header>
        <h1><i data-lucide="clipboard-list" style="width:24px;height:24px;color:var(--c-accent);"></i> Papan Kebutuhan Masyarakat</h1>
        <p>Daftar kebutuhan warga miskin yang belum terpenuhi — diagregasi per kategori dan wilayah. Data ditampilkan secara anonim untuk menjaga privasi warga.</p>
    </header>

    <div class="section-title">Kebutuhan yang Belum Terpenuhi</div>
    <div id="boardContainer" class="board-grid">
        <div class="empty-state"><span class="spinner"></span> Memuat data...</div>
    </div>

    <!-- Form hubungi admin -->
    <div id="formPanel" class="form-panel">
        <div class="form-title"><i data-lucide="handshake" style="color:var(--c-accent);"></i> Saya Ingin Membantu</div>
        <div class="form-sub" id="formSubtitle">Isi form ini untuk menghubungi kami. Kami akan segera menghubungi Anda.</div>
        <form id="donaturForm" onsubmit="submitDonatur(event)">
            <input type="hidden" id="f_kategori_minat" name="kategori_minat">
            <div class="form-group">
                <label>Nama Organisasi / Individu *</label>
                <input type="text" id="f_nama" name="nama" placeholder="cth. Yayasan Peduli, Bapak Ahmad" maxlength="150" required>
            </div>
            <div class="form-group">
                <label>WhatsApp / Email *</label>
                <input type="text" id="f_kontak" name="kontak" placeholder="cth. 0812-3456-7890 atau email@gmail.com" maxlength="150" required>
            </div>
            <div class="form-group">
                <label>Pesan (opsional)</label>
                <textarea id="f_pesan" name="pesan" placeholder="Ceritakan bentuk bantuan yang ingin Anda berikan..." maxlength="1000"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit" id="btnSubmit"><i data-lucide="send"></i> Kirim Pesan</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('formPanel').classList.remove('show')">Batal</button>
            </div>
            <div id="formStatus" class="form-status" style="display:none;"></div>
        </form>
    </div>

    <footer>
        <p>Data kebutuhan ini dikelola oleh <?= htmlspecialchars(APP_NAME, ENT_QUOTES) ?>.
        <?php if ($is_admin): ?>
            <a href="index.php">Buka Peta Admin</a> ·
        <?php endif; ?>
        Untuk informasi lebih lanjut, hubungi pengelola sistem.</p>
    </footer>
</div>

<script>
const KATEGORI_ICON = {
    'Sembako':'shopping-cart','Biaya Sekolah':'book-open','Biaya Kesehatan':'heart-pulse','Modal Usaha':'briefcase',
    'Renovasi Rumah':'home','Perlengkapan Rumah':'sofa','Pakaian':'shirt','Lainnya':'clipboard-list'
};

function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str ?? ''));
    return d.innerHTML;
}

fetch('api/papan/ambil.php?_=' + Date.now(), { cache: 'no-store' })
.then(r => r.json())
.then(j => {
    const container = document.getElementById('boardContainer');
    if (j.status !== 'success' || !j.data.length) {
        container.innerHTML = '<div class="empty-state"><i data-lucide="check-circle" style="color:var(--c-info);"></i> Saat ini tidak ada kebutuhan yang belum terpenuhi.</div>';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }
    container.innerHTML = j.data.map(row => {
        const icon = KATEGORI_ICON[row.kategori] || 'clipboard-list';
        return `<div class="board-row" data-kategori="${esc(row.kategori)}">
            <div class="board-icon"><i data-lucide="${icon}"></i></div>
            <div class="board-info">
                <div class="board-title">${esc(row.kategori)}</div>
                <div class="board-sub">di area ${esc(row.area)}</div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
                <div class="board-count">${row.jumlah_kk}</div>
                <div class="board-count-label">KK</div>
            </div>
            <button class="btn-help" type="button" data-kategori="${esc(row.kategori)}">
                Saya Ingin Membantu
            </button>
        </div>`;
    }).join('');
    if (typeof lucide !== 'undefined') lucide.createIcons();
})
.catch(() => {
    document.getElementById('boardContainer').innerHTML =
        '<div class="empty-state" style="color:#ef4444;"><i data-lucide="x-circle"></i> Gagal memuat data. Coba muat ulang halaman.</div>';
    if (typeof lucide !== 'undefined') lucide.createIcons();
});

document.getElementById('boardContainer').addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-kategori]');
    if (!trigger || !this.contains(trigger)) return;
    openForm(trigger.dataset.kategori || '');
});

function openForm(kategori) {
    document.getElementById('f_kategori_minat').value = kategori || '';
    document.getElementById('formSubtitle').textContent =
        kategori
            ? `Anda ingin membantu kebutuhan: ${kategori}. Isi form ini untuk dihubungi oleh pengelola.`
            : 'Isi form ini untuk menghubungi kami. Kami akan segera menghubungi Anda.';
    document.getElementById('formStatus').style.display = 'none';
    document.getElementById('donaturForm').reset();
    document.getElementById('f_kategori_minat').value = kategori || '';
    document.getElementById('formPanel').classList.add('show');
    document.getElementById('formPanel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function submitDonatur(e) {
    e.preventDefault();
    const btn    = document.getElementById('btnSubmit');
    const status = document.getElementById('formStatus');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Mengirim...';
    status.style.display = 'none';

    const fd = new FormData(e.target);
    fetch('api/papan/kontak_donatur.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(j => {
            if (j.status !== 'success') throw new Error(j.message || 'Gagal mengirim.');
            status.innerHTML = '<i data-lucide="check-circle" style="color:var(--c-accent);"></i> Pesan berhasil dikirim! Pengelola akan menghubungi Anda.';
            status.className = 'form-status success';
            status.style.display = '';
            btn.innerHTML = '<i data-lucide="check"></i> Terkirim';
            e.target.reset();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        })
        .catch(err => {
            status.innerHTML = '<i data-lucide="x-circle" style="color:var(--c-danger);"></i> ' + (err.message || 'Gagal mengirim, coba lagi.');
            status.className = 'form-status error';
            status.style.display = '';
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="send"></i> Kirim Pesan';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
}
// Boot
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body>
</html>
