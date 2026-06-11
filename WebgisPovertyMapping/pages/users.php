<?php
require_once '../config.php';
require_once '../auth/helper.php';
if (!is_logged_in() || !has_role('administrator')) {
    header('Location: ../auth/login.php'); exit;
}
require_password_changed('../auth/change_password.php');
require_once '../koneksi.php';

$ibadah_list = [];
$res = $conn->query("SELECT id, nama, jenis FROM rumah_ibadah WHERE deleted_at IS NULL ORDER BY nama");
while ($row = $res->fetch_assoc()) $ibadah_list[] = $row;

$page_title    = 'Pengguna & Akun';
$page_subtitle = 'Kelola akun pengguna sistem';
$active_nav    = 'users';
include '../includes/page-start.php';
?>

<style>
.users-content {
  --c-bg:#f5f0eb; --c-surface:#fafaf9; --c-surface2:#ede8e2;
  --c-border:#ddd8d2; --c-text:#201515; --c-muted:#7a7067;
  --c-accent:#0d7490; --c-accent-h:#0a5f7a; --c-danger:#ef4444;
  --c-info:#3b82f6; --c-warn:#f59e0b;
  --font-body:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
  color: var(--c-text);
}
.users-content .section-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.users-content .section-title   { font-size:15px; font-weight:600; letter-spacing:-0.3px; color:var(--c-text); }
.users-content .btn { padding:8px 16px; border-radius:8px; font-family:var(--font-body); font-size:13px; font-weight:600; cursor:pointer; border:none; transition:background .15s; }
.users-content .btn-primary  { background:#0d7490; color:#fff; }
.users-content .btn-primary:hover { background:#0a5f7a; }
.users-content .btn-sm   { padding:5px 10px; font-size:12px; border-radius:6px; }
.users-content .btn-danger    { background:#fef2f2; color:#ef4444; border:1px solid rgba(239,68,68,.3); }
.users-content .btn-danger:hover { background:#fee2e2; }
.users-content .btn-secondary { background:#ede8e2; color:#3d3530; border:1px solid #ddd8d2; }
.users-content .btn-secondary:hover { background:#ddd8d2; }
.users-content table { width:100%; border-collapse:collapse; background:var(--c-surface); border:1px solid var(--c-border); border-radius:10px; overflow:hidden; }
.users-content th, .users-content td { padding:12px 14px; text-align:left; border-bottom:1px solid var(--c-border); font-size:13px; color:var(--c-text); }
.users-content th { font-size:11px; font-weight:500; color:var(--c-muted); text-transform:uppercase; letter-spacing:.5px; background:var(--c-surface2); }
.users-content tr:last-child td { border-bottom:none; }
.users-content tr:hover td { background:#f5f0eb; }
.users-content .badge { display:inline-block; padding:3px 10px; border-radius:9999px; font-size:11px; font-weight:500; }
.users-content .badge-admin { background:#f0fdf4; color:#16a34a; }
.users-content .badge-op    { background:#eff6ff; color:#2563eb; }
.users-content .badge-view  { background:#ede8e2; color:#7a7067; }
.users-content .badge-on    { background:#f0fdf4; color:#16a34a; }
.users-content .badge-off   { background:#fef2f2; color:#ef4444; }
.users-content .modal-overlay { position:fixed; inset:0; background:rgba(32,21,21,.45); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; padding:20px; z-index:9999; opacity:0; pointer-events:none; transition:opacity .2s; }
.users-content .modal-overlay.show { opacity:1; pointer-events:auto; }
.users-content .modal { width:min(440px,100%); background:#fafaf9; border:1px solid #ddd8d2; border-radius:12px; padding:24px; box-shadow:0 4px 16px rgba(32,21,21,.1); }
.users-content .modal h3 { font-size:15px; font-weight:600; letter-spacing:-0.3px; margin-bottom:18px; color:var(--c-text); }
.users-content .form-group { margin-bottom:14px; }
.users-content .form-group label { display:block; font-size:13px; font-weight:500; color:#3d3530; margin-bottom:5px; }
.users-content .form-group input,
.users-content .form-group select { width:100%; padding:9px 12px; background:#fafaf9; border:1px solid #ddd8d2; border-radius:8px; color:#201515; font-family:var(--font-body); font-size:13px; outline:none; transition:border-color .15s; }
.users-content .form-group input:focus,
.users-content .form-group select:focus { border-color:#0d7490; }
.users-content .modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:18px; }
.users-content .msg-box { margin-top:10px; font-size:12px; min-height:16px; }
.users-content .msg-box.error   { color:#ef4444; }
.users-content .msg-box.success { color:#16a34a; }
.users-content .temp-pw-box { background:#ede8e2; border:1px solid #ddd8d2; border-radius:8px; padding:12px 16px; text-align:center; font-size:20px; font-weight:600; color:#201515; letter-spacing:4px; margin:12px 0; }
</style>

<div class="users-content">

  <div class="section-header">
    <div class="section-title">Daftar Akun Pengguna</div>
    <button class="btn btn-primary" onclick="openModal('create')">+ Akun Baru</button>
  </div>

  <table id="userTable">
    <thead>
      <tr>
        <th>Username</th><th>Nama</th><th>Role</th>
        <th>Rumah Ibadah</th><th>Status</th><th>Dibuat</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody id="userTbody">
      <tr><td colspan="7" style="text-align:center;color:var(--c-muted);">Memuat...</td></tr>
    </tbody>
  </table>

  <!-- Modal Create/Edit -->
  <div class="modal-overlay" id="modalForm">
    <div class="modal">
      <h3 id="modalTitle">Akun Baru</h3>
      <input type="hidden" id="editId">
      <div class="form-group">
        <label>Username</label>
        <input type="text" id="fUsername" placeholder="username" autocomplete="off">
      </div>
      <div class="form-group" id="fgPassword">
        <label>Password Awal</label>
        <input type="text" id="fPassword" placeholder="Min 8 karakter, huruf+angka" autocomplete="off">
      </div>
      <div class="form-group">
        <label>Nama Lengkap</label>
        <input type="text" id="fNama" placeholder="Nama lengkap">
      </div>
      <div class="form-group">
        <label>Role</label>
        <select id="fRole" onchange="toggleIbadahField()">
          <option value="administrator">Administrator</option>
          <option value="operator">Operator</option>
          <option value="viewer" disabled>Viewer (legacy)</option>
        </select>
      </div>
      <div class="form-group" id="fgIbadah" style="display:none;">
        <label>Rumah Ibadah</label>
        <select id="fIbadah">
          <option value="">-- Pilih Rumah Ibadah --</option>
          <?php foreach ($ibadah_list as $ib): ?>
          <option value="<?= $ib['id'] ?>"><?= htmlspecialchars("[{$ib['jenis']}] {$ib['nama']}") ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="msg-box" id="modalMsg"></div>
      <div class="modal-actions">
        <button class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button class="btn btn-primary" id="btnModalSave" onclick="saveUser()">Simpan</button>
      </div>
    </div>
  </div>

  <!-- Modal Reset Password -->
  <div class="modal-overlay" id="modalReset">
    <div class="modal">
      <h3>Reset Password</h3>
      <p style="font-size:13px;color:var(--c-muted);line-height:1.6;">
        Password sementara untuk akun ini. Sampaikan ke pengguna secara langsung —
        mereka akan diminta mengganti saat login pertama.
      </p>
      <div class="temp-pw-box" id="tempPwDisplay">—</div>
      <div class="modal-actions">
        <button class="btn btn-primary" onclick="document.getElementById('modalReset').classList.remove('show')">Tutup</button>
      </div>
    </div>
  </div>

</div><!-- .users-content -->

<script>
const SELF_ID = <?= get_user_id() ?>;

async function loadUsers() {
  const res = await fetch('../api/users/ambil.php');
  const j   = await res.json();
  if (j.status !== 'success') return;

  const tbody = document.getElementById('userTbody');
  if (!j.data.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#8b949e;">Belum ada akun.</td></tr>';
    return;
  }
  tbody.innerHTML = j.data.map(u => {
    const roleBadge = u.role === 'administrator'
      ? `<span class="badge badge-admin">Admin</span>`
      : u.role === 'operator'
      ? `<span class="badge badge-op">Operator</span>`
      : `<span class="badge badge-view">Viewer</span>`;
    const statusBadge = u.is_active == 1
      ? `<span class="badge badge-on">Aktif</span>`
      : `<span class="badge badge-off">Nonaktif</span>`;
    const ibadah = u.nama_ibadah ? escapeHTML(u.nama_ibadah) : '<span style="color:#8b949e">—</span>';
    const isSelf = u.id == SELF_ID;
    const toggleBtn = isSelf ? '' :
      `<button class="btn btn-sm ${u.is_active==1 ? 'btn-danger' : 'btn-secondary'}"
               onclick="toggleActive(${u.id})">
         ${u.is_active==1 ? 'Nonaktifkan' : 'Aktifkan'}
       </button>`;
    return `<tr>
      <td style="font-weight:500">${escapeHTML(u.username)}</td>
      <td>${escapeHTML(u.nama_lengkap)}</td>
      <td>${roleBadge}</td>
      <td>${ibadah}</td>
      <td>${statusBadge}</td>
      <td style="color:#8b949e;font-size:12px">${u.created_at.slice(0,10)}</td>
      <td style="display:flex;gap:6px;flex-wrap:wrap">
        <button class="btn btn-sm btn-secondary" onclick='openModal("edit", ${JSON.stringify(u)})'>Edit</button>
        <button class="btn btn-sm btn-secondary" onclick="resetPw(${u.id})">Reset PW</button>
        ${toggleBtn}
      </td>
    </tr>`;
  }).join('');
}

function escapeHTML(s) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(s ?? ''));
  return d.innerHTML;
}

function toggleIbadahField() {
  const role = document.getElementById('fRole').value;
  document.getElementById('fgIbadah').style.display = role === 'operator' ? '' : 'none';
}

function openModal(mode, user = null) {
  const isEdit = mode === 'edit';
  document.getElementById('modalTitle').textContent    = isEdit ? 'Edit Akun' : 'Akun Baru';
  document.getElementById('editId').value              = isEdit ? user.id : '';
  document.getElementById('fUsername').value           = isEdit ? user.username : '';
  document.getElementById('fUsername').disabled        = isEdit;
  document.getElementById('fNama').value               = isEdit ? user.nama_lengkap : '';
  document.getElementById('fRole').value               = isEdit ? user.role : 'operator';
  document.getElementById('fIbadah').value             = isEdit ? (user.ibadah_id ?? '') : '';
  document.getElementById('fgPassword').style.display = isEdit ? 'none' : '';
  document.getElementById('modalMsg').textContent      = '';
  toggleIbadahField();
  document.getElementById('modalForm').classList.add('show');
}

function closeModal() {
  document.getElementById('modalForm').classList.remove('show');
}

async function saveUser() {
  const id     = document.getElementById('editId').value;
  const isEdit = !!id;
  const msg    = document.getElementById('modalMsg');
  const btn    = document.getElementById('btnModalSave');
  btn.disabled = true; btn.textContent = '⏳';
  msg.className = 'msg-box'; msg.textContent = '';

  const fd = new FormData();
  if (isEdit) {
    fd.append('id',           id);
    fd.append('nama_lengkap', document.getElementById('fNama').value.trim());
    fd.append('role',         document.getElementById('fRole').value);
    fd.append('ibadah_id',    document.getElementById('fIbadah').value);
  } else {
    fd.append('username',     document.getElementById('fUsername').value.trim());
    fd.append('password',     document.getElementById('fPassword').value);
    fd.append('nama_lengkap', document.getElementById('fNama').value.trim());
    fd.append('role',         document.getElementById('fRole').value);
    fd.append('ibadah_id',    document.getElementById('fIbadah').value);
  }

  const url = isEdit ? '../api/users/update.php' : '../api/users/simpan.php';
  const res = await fetch(url, { method: 'POST', body: appendCsrf(fd) });
  const j   = await res.json();
  btn.disabled = false; btn.textContent = 'Simpan';

  if (j.status === 'success') {
    closeModal();
    loadUsers();
  } else {
    msg.className   = 'msg-box error';
    msg.textContent = j.message;
  }
}

function showConfirm({ title, message, confirmLabel = 'Ya, Lanjutkan', cancelLabel = 'Batal', danger = false }) {
    return new Promise(resolve => {
        const el = document.createElement('div');
        el.className = 'confirm-overlay';
        el.innerHTML = `<div class="confirm-box">
            <div class="confirm-title">${title}</div>
            <p class="confirm-msg">${message}</p>
            <div class="confirm-actions">
                <button class="btn-secondary" id="_sc_cancel">${cancelLabel}</button>
                <button class="${danger ? 'btn-danger' : 'btn-primary'}" id="_sc_ok">${confirmLabel}</button>
            </div></div>`;
        document.body.appendChild(el);
        const done = val => { el.remove(); resolve(val); };
        el.querySelector('#_sc_ok').onclick     = () => done(true);
        el.querySelector('#_sc_cancel').onclick = () => done(false);
        el.addEventListener('click', e => { if (e.target === el) done(false); });
    });
}

async function toggleActive(id) {
  const user = [...document.querySelectorAll('#userTbody tr')].find(tr =>
      tr.querySelector(`[onclick*="toggleActive(${id})"]`)
  );
  const isActive = user?.querySelector('.badge-on') !== null;
  const ok = await showConfirm({
      title: isActive ? 'Nonaktifkan Akun' : 'Aktifkan Akun',
      message: isActive
          ? 'Akun ini akan dinonaktifkan dan tidak bisa login.'
          : 'Akun ini akan diaktifkan kembali.',
      confirmLabel: isActive ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan',
      danger: isActive
  });
  if (!ok) return;

  const fd = new FormData(); fd.append('id', id);
  const res = await fetch('../api/users/toggle_active.php', { method:'POST', body: appendCsrf(fd) });
  const j   = await res.json();
  if (j.status === 'success') loadUsers();
  else alert(j.message);
}

async function resetPw(id) {
  const ok = await showConfirm({
      title: 'Reset Password',
      message: 'Password akun ini akan direset. Password sementara akan ditampilkan setelah proses selesai.',
      confirmLabel: 'Ya, Reset'
  });
  if (!ok) return;
  const fd = new FormData(); fd.append('id', id);
  const res = await fetch('../api/users/reset_password.php', { method:'POST', body: appendCsrf(fd) });
  const j   = await res.json();
  if (j.status === 'success') {
    document.getElementById('tempPwDisplay').textContent = j.temp_password;
    document.getElementById('modalReset').classList.add('show');
  } else {
    alert(j.message);
  }
}

loadUsers();
</script>

<?php include '../includes/page-end.php'; ?>
