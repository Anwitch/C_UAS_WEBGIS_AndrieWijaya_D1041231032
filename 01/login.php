<?php
require_once __DIR__ . '/koneksi.php';

if (is_admin()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (login_is_throttled($username)) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi beberapa menit.';
    } elseif ($username && $password) {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && password_verify($password, $row['password'])) {
            harden_successful_login();
            login_record_success($username);
            $_SESSION['admin_id']       = $row['id'];
            $_SESSION['admin_username'] = $username;
            header('Location: index.php'); exit;
        }
        login_record_failure($username);
        $error = 'Username atau password salah.';
    } else {
        $error = 'Username dan password wajib diisi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — WebGIS Pontianak</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --sb-bg:      #201515; /* ink */
    --body-bg:    #f8f4f0; /* canvas-soft */
    --card-bg:    #fffefb; /* canvas */
    --card-border:#e6e1d5; /* warm border */
    --accent:     #ff4f00; /* primary (Zapier Orange) */
    --accent-h:   #e04600; /* accent hover */
    --accent-dim: rgba(255, 79, 0, 0.08);
    --text:       #201515; /* ink */
    --text-sec:   #2f2a26; /* ink-soft */
    --text-muted: #605d52; /* body */
    --danger:     #ef4444;
    --danger-dim: rgba(239, 68, 68, 0.08);
    --danger-bdr: rgba(239, 68, 68, 0.25);
    --border:     #e6e1d5;
    --radius:     12px;
    --radius-sm:  6px;
    --shadow:     0 6px 20px rgba(32, 21, 21, 0.08);
    --font:       'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
html, body { height: 100%; font-family: var(--font); background: var(--body-bg); color: var(--text); }
.page { min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr; }
@media (max-width: 768px) { .page { grid-template-columns: 1fr; } .brand-side { display: none; } }

.brand-side {
    background: var(--sb-bg);
    display: flex; flex-direction: column; justify-content: space-between;
    padding: 48px 52px; position: relative; overflow: hidden;
}
.brand-side::before {
    content: ''; position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 60% 50% at 30% 20%, rgba(14,165,233,.18) 0%, transparent 70%),
        radial-gradient(ellipse 50% 60% at 80% 80%, rgba(56,189,248,.12) 0%, transparent 70%);
    pointer-events: none;
}
.brand-logo { display: flex; align-items: center; gap: 12px; position: relative; }
.brand-logo-icon {
    width: 44px; height: 44px; border-radius: var(--radius); background: var(--accent);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.brand-logo-icon .lucide { width: 22px; height: 22px; color: #fff; }
.brand-logo-name { font-size: 15px; font-weight: 600; color: #fff; line-height: 1.3; }
.brand-logo-sub  { font-size: 11px; color: #a89f96; }
.brand-body { position: relative; }
.brand-headline { font-size: 28px; font-weight: 600; color: #fff; line-height: 1.35; margin-bottom: 16px; }
.brand-headline span { color: #a89f96; }
.brand-desc { font-size: 14px; color: #a89f96; line-height: 1.7; max-width: 340px; }
.brand-stats { display: flex; gap: 28px; margin-top: 36px; }
.brand-stat-num   { font-size: 20px; font-weight: 600; color: #fff; letter-spacing: -0.5px; }
.brand-stat-label { font-size: 11px; color: #a89f96; margin-top: 2px; }
.brand-footer     { font-size: 11px; color: #a89f96; position: relative; }

.form-side { display: flex; align-items: center; justify-content: center; padding: 40px 32px; background: var(--body-bg); }
.form-box { width: min(420px, 100%); }
.form-header { margin-bottom: 32px; }
.form-welcome     { font-size: 24px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
.form-welcome-sub { font-size: 14px; color: var(--text-muted); line-height: 1.5; }

.login-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); padding: 28px 32px 32px; box-shadow: var(--shadow); }

.alert { display: none; align-items: flex-start; gap: 10px; background: var(--danger-dim); border: 1px solid var(--danger-bdr); border-radius: 10px; padding: 12px 14px; margin-bottom: 20px; font-size: 13px; color: var(--danger); line-height: 1.5; }
.alert.show { display: flex; }
.alert .lucide { width: 16px; height: 16px; color: var(--danger); flex-shrink: 0; margin-top: 1px; }

.form-group { margin-bottom: 18px; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-sec); margin-bottom: 7px; }
.input-wrap { position: relative; }
.form-group input {
    width: 100%; padding: 11px 14px; background: var(--body-bg);
    border: 1.5px solid var(--border); border-radius: var(--radius-sm);
    color: var(--text); font-family: var(--font); font-size: 14px; outline: none;
    transition: border-color .2s, background .2s, box-shadow .2s;
}
.form-group input:focus { border-color: var(--text); background: var(--card-bg); box-shadow: 0 0 0 3px var(--accent-dim); }
.form-group input::placeholder { color: var(--text-muted); }
.input-wrap input { padding-right: 44px; }
.pw-toggle {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; color: var(--text-muted);
    padding: 4px; border-radius: 5px; transition: color .2s;
    display: flex; align-items: center; justify-content: center;
}
.pw-toggle:hover { color: var(--text-sec); }
.pw-toggle .lucide { width: 17px; height: 17px; }

.btn-login {
    width: 100%; padding: 12px; background: var(--accent); color: #fff; border: none;
    border-radius: 9px; font-family: var(--font); font-size: 14px; font-weight: 600;
    cursor: pointer; margin-top: 6px; transition: background .2s, transform .1s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-login:hover:not(:disabled) { background: var(--accent-h); transform: translateY(-1px); }
.btn-login:disabled { opacity: .6; cursor: not-allowed; }
.btn-spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.35); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; display: none; }
.btn-login.loading .btn-spinner { display: block; }
@keyframes spin { to { transform: rotate(360deg); } }

.divider { display: flex; align-items: center; gap: 12px; margin: 22px 0; color: var(--text-muted); font-size: 12px; }
.divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }

.public-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 11px; background: transparent; color: var(--text-sec);
    border: 1.5px solid var(--border); border-radius: 9px; font-family: var(--font);
    font-size: 14px; font-weight: 600; text-decoration: none;
    transition: border-color .2s, color .2s, background .2s;
}
.public-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-dim); }
.public-btn .lucide { width: 16px; height: 16px; }
.form-note { text-align: center; margin-top: 24px; font-size: 12px; color: var(--text-muted); line-height: 1.6; }
</style>
</head>
<body>
<div class="page">

    <div class="brand-side">
        <div class="brand-logo">
            <div class="brand-logo-icon"><i data-lucide="map"></i></div>
            <div>
                <div class="brand-logo-name">WebGIS Pontianak</div>
                <div class="brand-logo-sub">Visualisasi Data Wilayah</div>
            </div>
        </div>
        <div class="brand-body">
            <div class="brand-headline">
                Pemetaan Wilayah<br>Berbasis <span>Data</span><br>ArcGIS
            </div>
            <div class="brand-desc">
                Platform visualisasi choropleth untuk menganalisis data spasial wilayah Pontianak
                melalui layer GeoJSON yang dapat dikonfigurasi.
            </div>
            <div class="brand-stats">
                <div><div class="brand-stat-num">GIS</div><div class="brand-stat-label">Berbasis Peta</div></div>
                <div><div class="brand-stat-num">ArcGIS</div><div class="brand-stat-label">Import Data</div></div>
                <div><div class="brand-stat-num">RT</div><div class="brand-stat-label">Real-time</div></div>
            </div>
        </div>
        <div class="brand-footer">&copy; <?= date('Y') ?> WebGIS Pontianak</div>
    </div>

    <div class="form-side">
        <div class="form-box">
            <div class="form-header">
                <div class="form-welcome">Selamat datang 👋</div>
                <div class="form-welcome-sub">Masuk untuk mengelola layer dan data peta.</div>
            </div>
            <div class="login-card">
                <?php if ($error): ?>
                <div class="alert show">
                    <i data-lucide="alert-triangle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>
                <form method="POST" id="loginForm">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Masukkan username" required autofocus autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <input type="password" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                            <button type="button" class="pw-toggle" onclick="togglePw()" aria-label="Tampilkan password">
                                <i data-lucide="eye" id="pwIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-login">
                        <div class="btn-spinner"></div>
                        <span>Masuk</span>
                    </button>
                </form>
                <div class="divider">atau</div>
                <a href="index.php" class="public-btn">
                    <i data-lucide="map"></i> Lihat Peta sebagai Pengunjung
                </a>
            </div>
            <div class="form-note">Lupa password? Hubungi administrator sistem.</div>
        </div>
    </div>

</div>
<script>
function togglePw() {
    const pw = document.getElementById('password');
    const ic = document.getElementById('pwIcon');
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    ic.setAttribute('data-lucide', show ? 'eye-off' : 'eye');
    lucide.createIcons();
}
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = this.querySelector('.btn-login');
    btn.disabled = true;
    btn.classList.add('loading');
});
lucide.createIcons();
</script>
</body>
</html>
