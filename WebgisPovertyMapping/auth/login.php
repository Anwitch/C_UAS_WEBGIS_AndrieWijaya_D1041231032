<?php
// auth/login.php — Halaman login standalone
require_once '../config.php';
require_once '../auth/helper.php';

if (is_logged_in()) {
    $dest = (get_role() === 'administrator') ? '../dashboard.php' : '../index.php';
    header('Location: ' . $dest);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --sb-bg:      #1c1612;
    --body-bg:    #f5f0eb;
    --card-bg:    #fafaf9;
    --card-border:#ddd8d2;
    --accent:     #0d7490;
    --accent-h:   #0a5f7a;
    --accent-dim: rgba(13,116,144,.06);
    --text:       #201515;
    --text-sec:   #3d3530;
    --text-muted: #7a7067;
    --danger:     #ef4444;
    --danger-dim: rgba(239,68,68,.08);
    --danger-bdr: rgba(239,68,68,.25);
    --border:     #ddd8d2;
    --border-f:   #0d7490;
    --radius:     12px;
    --shadow:     0 4px 12px rgba(32,21,21,.08), 0 1px 2px rgba(32,21,21,.04);
    --font:       'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

html, body {
    height: 100%;
    font-family: var(--font);
    background: var(--body-bg);
    color: var(--text);
}

/* ── Layout: 2 kolom ────────────────────────────────── */
.page {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
}

@media (max-width: 768px) {
    .page { grid-template-columns: 1fr; }
    .brand-side { display: none; }
}

/* ── Sisi Kiri (brand) ──────────────────────────────── */
.brand-side {
    background: var(--sb-bg);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 48px 52px;
    position: relative;
    overflow: hidden;
}

.brand-side::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 50% at 30% 20%, rgba(14,165,233,.18) 0%, transparent 70%),
        radial-gradient(ellipse 50% 60% at 80% 80%, rgba(56,189,248,.12) 0%, transparent 70%);
    pointer-events: none;
}

.brand-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
}

.brand-logo-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #0d7490;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
    box-shadow: none;
}

.brand-logo-name {
    font-size: 15px;
    font-weight: 600;
    color: #ffffff;
    line-height: 1.3;
}

.brand-logo-sub {
    font-size: 11px;
    color: #a89f96;
}

.brand-body {
    position: relative;
}

.brand-headline {
    font-size: 30px;
    font-weight: 600;
    color: #ffffff;
    line-height: 1.35;
    margin-bottom: 16px;
}

.brand-headline span {
    color: #a89f96;
}

.brand-desc {
    font-size: 14px;
    color: #a89f96;
    line-height: 1.7;
    max-width: 360px;
}

.brand-stats {
    display: flex;
    gap: 28px;
    margin-top: 36px;
}

.brand-stat-num {
    font-size: 22px;
    font-weight: 600;
    color: #ffffff;
    letter-spacing: -0.5px;
}

.brand-stat-label {
    font-size: 11px;
    color: #a89f96;
    margin-top: 2px;
}

.brand-footer {
    font-size: 11px;
    color: #a89f96;
    position: relative;
}

/* ── Sisi Kanan (form) ──────────────────────────────── */
.form-side {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 32px;
    background: var(--body-bg);
}

.form-box {
    width: min(420px, 100%);
}

.form-header {
    margin-bottom: 32px;
}

.form-welcome {
    font-size: 24px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 6px;
}

.form-welcome-sub {
    font-size: 14px;
    color: var(--text-muted);
    line-height: 1.5;
}

/* ── Card ────────────────────────────────────────────── */
.login-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    padding: 28px 32px 32px;
    box-shadow: var(--shadow);
}

/* ── Alert ───────────────────────────────────────────── */
.alert {
    display: none;
    align-items: flex-start;
    gap: 10px;
    background: var(--danger-dim);
    border: 1px solid var(--danger-bdr);
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 20px;
    font-size: 13px;
    color: var(--danger);
    line-height: 1.5;
}
.alert.show { display: flex; }
.alert-icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }

/* ── Form Groups ─────────────────────────────────────── */
.form-group { margin-bottom: 18px; }

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-sec);
    margin-bottom: 7px;
    letter-spacing: .2px;
}

.input-wrap {
    position: relative;
}

.form-group input {
    width: 100%;
    padding: 11px 14px;
    background: #ede8e2;
    border: 1.5px solid var(--border);
    border-radius: 9px;
    color: var(--text);
    font-family: var(--font);
    font-size: 14px;
    outline: none;
    transition: border-color .2s, background .2s, box-shadow .2s;
}

.form-group input:focus {
    border-color: var(--accent);
    background: #fafaf9;
    box-shadow: 0 0 0 3px rgba(13,116,144,.08);
}

.form-group input::placeholder { color: var(--text-muted); }

/* Password toggle */
.input-wrap input[type="password"],
.input-wrap input[type="text"] {
    padding-right: 44px;
}

.pw-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-muted);
    font-size: 17px;
    line-height: 1;
    padding: 4px;
    border-radius: 5px;
    transition: color .2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.pw-toggle:hover { color: var(--text-sec); }

/* ── Submit button ───────────────────────────────────── */
.btn-login {
    width: 100%;
    padding: 12px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: 9px;
    font-family: var(--font);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 6px;
    transition: background .2s, transform .1s, box-shadow .2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    letter-spacing: .2px;
    box-shadow: none;
}
.btn-login:hover:not(:disabled) {
    background: var(--accent-h);
    box-shadow: none;
    transform: translateY(-1px);
}
.btn-login:active:not(:disabled) { transform: translateY(0); }
.btn-login:disabled { opacity: .6; cursor: not-allowed; transform: none; box-shadow: none; }

.btn-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    display: none;
    flex-shrink: 0;
}

.btn-login.loading .btn-spinner { display: block; }
.btn-login.loading .btn-label::after { content: '...'; }

@keyframes spin { to { transform: rotate(360deg); } }

/* ── Divider ─────────────────────────────────────────── */
.divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 22px 0;
    color: var(--text-muted);
    font-size: 12px;
}
.divider::before, .divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}

/* ── Public link ─────────────────────────────────────── */
.public-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 11px;
    background: transparent;
    color: var(--text-sec);
    border: 1.5px solid var(--border);
    border-radius: 9px;
    font-family: var(--font);
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: border-color .2s, color .2s, background .2s;
}
.public-btn:hover {
    border-color: var(--accent);
    color: var(--accent);
    background: var(--accent-dim);
}
.public-btn-icon { font-size: 16px; }

/* ── Footer note ─────────────────────────────────────── */
.form-note {
    text-align: center;
    margin-top: 24px;
    font-size: 12px;
    color: var(--text-muted);
    line-height: 1.6;
}

/* Lucide Icons */
.lucide {
    width: 16px;
    height: 16px;
    stroke-width: 2px;
    display: inline-block;
    vertical-align: middle;
}
.brand-logo-icon .lucide {
    width: 22px;
    height: 22px;
    color: #ffffff;
}
.brand-stat-num .lucide {
    color: #16a34a;
    width: 20px;
    height: 20px;
}
.alert-icon .lucide {
    color: var(--danger);
    width: 16px;
    height: 16px;
}
</style>
</head>
<body>
<div class="page">

    <!-- ═══ SISI KIRI: Brand ═══ -->
    <div class="brand-side">
        <div class="brand-logo">
            <div class="brand-logo-icon"><i data-lucide="map"></i></div>
            <div>
                <div class="brand-logo-name"><?= APP_NAME ?></div>
                <div class="brand-logo-sub">Sistem Pemetaan Kemiskinan</div>
            </div>
        </div>

        <div class="brand-body">
            <div class="brand-headline">
                Pemetaan Kemiskinan<br>Berbasis <span>Partisipasi</span><br>Rumah Ibadah
            </div>
            <div class="brand-desc">
                Platform kolaboratif untuk memetakan, memantau, dan menjangkau keluarga miskin
                melalui jaringan rumah ibadah di seluruh wilayah.
            </div>
            <div class="brand-stats">
                <div>
                    <div class="brand-stat-num">GIS</div>
                    <div class="brand-stat-label">Berbasis Peta</div>
                </div>
                <div>
                    <div class="brand-stat-num">RT</div>
                    <div class="brand-stat-label">Data Real-time</div>
                </div>
                <div>
                    <div class="brand-stat-num"><i data-lucide="check"></i></div>
                    <div class="brand-stat-label">Multi-peran</div>
                </div>
            </div>
        </div>

        <div class="brand-footer">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>
        </div>
    </div>

    <!-- ═══ SISI KANAN: Form Login ═══ -->
    <div class="form-side">
        <div class="form-box">

            <div class="form-header">
                <div class="form-welcome">Selamat datang 👋</div>
                <div class="form-welcome-sub">Masuk ke akun Anda untuk mengelola data pemetaan kemiskinan.</div>
            </div>

            <div class="login-card">
                <div class="alert" id="alertMsg" role="alert">
                    <span class="alert-icon"><i data-lucide="alert-triangle"></i></span>
                    <span id="alertText"></span>
                </div>

                <form id="loginForm" onsubmit="doLogin(event)" novalidate>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username"
                               autocomplete="username"
                               placeholder="Masukkan username Anda"
                               required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <input type="password" id="password" name="password"
                                   autocomplete="current-password"
                                   placeholder="Masukkan password Anda"
                                   required>
                            <input type="password" style="display:none;"> <!-- Prevent autofill bug -->
                            <button type="button" class="pw-toggle" id="pwToggle"
                                    aria-label="Tampilkan / Sembunyikan password"
                                    onclick="togglePw()">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="btnLogin">
                        <div class="btn-spinner" id="btnSpinner"></div>
                        <span class="btn-label">Masuk</span>
                    </button>
                </form>

                <div class="divider">atau</div>

                <a href="../index.php" class="public-btn">
                    <span class="public-btn-icon"><i data-lucide="map"></i></span>
                    Lihat Peta sebagai Pengunjung
                </a>
            </div>

            <div class="form-note">
                Lupa password? Hubungi administrator sistem Anda.
            </div>

        </div>
    </div>

</div>

<script>
function togglePw() {
    const pw  = document.getElementById('password');
    const btn = document.getElementById('pwToggle');
    const show = pw.type === 'password';
    pw.type    = show ? 'text' : 'password';
    btn.innerHTML = show ? '<i data-lucide="eye-off"></i>' : '<i data-lucide="eye"></i>';
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
// Boot Lucide on load
if (typeof lucide !== 'undefined') lucide.createIcons();

async function doLogin(e) {
    e.preventDefault();
    const btn     = document.getElementById('btnLogin');
    const alert   = document.getElementById('alertMsg');
    const alertTx = document.getElementById('alertText');

    btn.disabled = true;
    btn.classList.add('loading');
    alert.classList.remove('show');

    const fd = new FormData(document.getElementById('loginForm'));
    try {
        const res = await fetch('../api/auth/login.php', { method: 'POST', body: fd });
        const j   = await res.json();
        if (j.status === 'success') {
            if (j.data.must_change_password) {
                window.location.href = 'change_password.php';
            } else {
                window.location.href = j.redirect || '../index.php';
            }
        } else {
            alertTx.textContent = j.message;
            alert.classList.add('show');
            btn.disabled = false;
            btn.classList.remove('loading');
            document.getElementById('password').value = '';
            document.getElementById('password').focus();
        }
    } catch (_) {
        alertTx.textContent = 'Gagal menghubungi server. Periksa koneksi Anda.';
        alert.classList.add('show');
        btn.disabled = false;
        btn.classList.remove('loading');
    }
}
</script>
</body>
</html>
