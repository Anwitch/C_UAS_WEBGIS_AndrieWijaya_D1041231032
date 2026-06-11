<?php
// auth/change_password.php — Paksa ganti password saat pertama login
require_once '../config.php';
require_once '../auth/helper.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ganti Password — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root { --c-bg:#f5f0eb; --c-surface:#fafaf9; --c-border:#ddd8d2;
          --c-text:#201515; --c-muted:#7a7067; --c-accent:#0d7490;
          --c-accent-h:#0a5f7a; --c-danger:#ef4444; --font-body:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; }
  body { min-height:100vh; background:var(--c-bg); color:var(--c-text);
         font-family:var(--font-body); display:flex; align-items:center;
         justify-content:center; padding:20px; }
  .card { width:min(420px,100%); background:var(--c-surface);
          border:1px solid var(--c-border); border-radius:12px;
          padding:32px; box-shadow:0 4px 12px rgba(32,21,21,.08); }
  .notice { background:#fffbeb; border:1px solid #fde68a;
            border-radius:8px; padding:12px 16px; font-size:13px;
            color:#d97706; margin-bottom:22px; }
  .form-group { margin-bottom:16px; }
  .form-group label { display:block; font-size:13px; font-weight:500;
                      color:#3d3530; margin-bottom:6px; }
  .form-group input { width:100%; padding:10px 14px; background:#fafaf9;
                      border:1px solid var(--c-border); border-radius:8px;
                      color:var(--c-text); font-size:14px; outline:none; height:40px;
                      transition:border-color .15s; font-family:var(--font-body); }
  .form-group input:focus { border-color:#0d7490; }
  .hint { font-size:12px; color:var(--c-muted); margin-top:5px; }
  .btn { width:100%; padding:11px; background:#0d7490; color:#fff;
         border:none; border-radius:8px; font-size:14px; font-weight:600;
         cursor:pointer; margin-top:8px; transition:background .15s;
         font-family:var(--font-body); }
  .btn:hover { background:#0a5f7a; }
  .btn:disabled { opacity:.5; cursor:not-allowed; }
  .msg { margin-top:12px; font-size:13px; text-align:center;
         min-height:18px; }
  .msg.error { color:#ef4444; }
  .msg.success { color:#16a34a; }
  
  /* Lucide & Spinner styles */
  .lucide { width: 16px; height: 16px; display: inline-block; vertical-align: middle; stroke-width: 2px; }
  .notice .lucide { color: #d97706; margin-right: 6px; }
  .spinner {
      display: inline-block; width: 14px; height: 14px;
      border: 2px solid #ddd8d2; border-top-color: #0d7490;
      border-radius: 50%; animation: spin .7s linear infinite;
      vertical-align: middle;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>
<div class="card">
  <div class="notice"><i data-lucide="alert-triangle"></i> Anda diminta mengganti password sebelum dapat mengakses sistem.</div>
  <form id="cpForm" onsubmit="doChange(event)">
    <div class="form-group">
      <label>Password Baru</label>
      <input type="password" id="new_password" name="new_password"
             placeholder="Min. 8 karakter" required>
      <div class="hint">Minimal 8 karakter, kombinasi huruf dan angka.</div>
    </div>
    <div class="form-group">
      <label>Konfirmasi Password</label>
      <input type="password" id="confirm_password" name="confirm_password"
             placeholder="Ulangi password baru" required>
    </div>
    <button type="submit" class="btn" id="btnSave">Simpan Password</button>
  </form>
  <div class="msg" id="cpMsg"></div>
</div>
<script>
window.APP_CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
function appendCsrf(fd) {
  const token = window.APP_CSRF_TOKEN || null;
  if (token) fd.append('csrf_token', token);
  return fd;
}
async function doChange(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSave');
  const msg = document.getElementById('cpMsg');
  btn.disabled    = true;
  btn.innerHTML   = '<span class="spinner"></span> Menyimpan...';
  msg.className   = 'msg';
  msg.textContent = '';

  const fd = new FormData(document.getElementById('cpForm'));
  try {
    const res = await fetch('../api/auth/change_password.php', { method:'POST', body: appendCsrf(fd) });
    const j   = await res.json();
    if (j.status === 'success') {
      msg.innerHTML = '<i data-lucide="check-circle" style="color:#16a34a;margin-right:4px;"></i> Password berhasil diubah. Mengalihkan...';
      msg.className   = 'msg success';
      if (typeof lucide !== 'undefined') lucide.createIcons();
      setTimeout(() => { window.location.href = '../index.php'; }, 1200);
    } else {
      msg.textContent = j.message;
      msg.className   = 'msg error';
      btn.disabled    = false;
      btn.innerHTML   = 'Simpan Password';
    }
  } catch (_) {
    msg.textContent = 'Gagal menghubungi server.';
    msg.className   = 'msg error';
    btn.disabled    = false;
    btn.innerHTML   = 'Simpan Password';
  }
}
// Boot Lucide on load
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body>
</html>
