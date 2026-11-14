<?php
// index.php — Halaman Login
define('BASE_URL', '/uts_aplikasi');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Person.php';
require_once __DIR__ . '/classes/Karyawan.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . (isDosen() ? "/admin/dashboard.php" : "/dashboard.php"));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginAs  = $_POST['login_as'] ?? 'mahasiswa';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username/NIM dan password wajib diisi.';
    } elseif ($loginAs === 'mahasiswa') {
        $mhs = new Mahasiswa();
        if ($mhs->login($username, $password)) {
            // Hapus semua session lama sebelum set yang baru
            session_unset();
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$mhs->getId();
            $_SESSION['nama']    = $mhs->getNama();
            $_SESSION['nim']     = $mhs->getNim();
            $_SESSION['kelas']   = $mhs->getKelas();
            $_SESSION['angkatan']= $mhs->getAngkatan();
            $_SESSION['role']    = 'mahasiswa';
            header("Location: " . BASE_URL . "/dashboard.php"); exit;
        } else {
            $error = 'NIM atau password salah, atau akun tidak aktif.';
        }
    } else {
        // Login admin/dosen
        $db   = Database::getInstance();
        $stmt = $db->getConnection()->prepare(
            "SELECT * FROM dosen WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && password_verify($password, $row['password'])) {
            // Hapus semua session lama sebelum set yang baru
            session_unset();
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$row['id'];
            $_SESSION['nama']    = $row['nama'];
            $_SESSION['role']    = $row['role'];
            header("Location: " . BASE_URL . "/admin/dashboard.php"); exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — AbsensiTI</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-left">
    <div class="login-left-brand">
      <div class="logo-icon">
        <svg viewBox="0 0 24 24" style="width:22px;height:22px;"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm-2 14l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
      </div>
      <div>
        <h1>AbsensiTI</h1>
        <span>Sistem Absensi Mahasiswa</span>
      </div>
    </div>
    <div class="login-headline">
      <h2>Absensi <span>TI Kelas C</span>Angkatan 2025</h2>
      <p>Platform pencatatan kehadiran mahasiswa dan pengelolaan data absensi untuk 8 mata kuliah semester aktif.</p>
    </div>
    <div class="login-features">
      <div class="login-feature">
        <div class="feat-icon" style="background:linear-gradient(135deg,#1565c0,#0d47a1);">
          <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
        </div>
        <div class="login-feature-text">
          <strong>8 Mata Kuliah</strong>
          <span>PBO, RPL, Basis Data, Struktur Data, dan lainnya</span>
        </div>
      </div>
      <div class="login-feature">
        <div class="feat-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
          <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
        </div>
        <div class="login-feature-text">
          <strong>Rekap Absensi</strong>
          <span>Pantau kehadiran, izin, sakit, dan alpha</span>
        </div>
      </div>
      <div class="login-feature">
        <div class="feat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
          <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </div>
        <div class="login-feature-text">
          <strong>Kelola Mahasiswa</strong>
          <span>Tambah, edit, dan hapus data mahasiswa</span>
        </div>
      </div>
    </div>
  </div>

  <div class="login-right">
    <div class="login-card">
      <div class="login-card-header">
        <h3>Masuk ke Sistem</h3>
        <p>Gunakan NIM (mahasiswa) atau username admin</p>
      </div>

      <?php if ($error): ?>
      <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#fca5a5;padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:20px;">
        ⚠️ <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST">
        <!-- Tab pilih peran -->
        <div style="display:flex;gap:8px;margin-bottom:20px;background:rgba(255,255,255,.05);border-radius:8px;padding:4px;">
          <label style="flex:1;display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:6px;cursor:pointer;font-size:13px;color:rgba(255,255,255,.5);transition:all .2s;" id="tabMhs">
            <input type="radio" name="login_as" value="mahasiswa" checked onchange="switchTab('mhs')" style="display:none;">
            <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor;flex-shrink:0;"><path d="M12 3L1 9l4 2.18V17h2v-4.82L9 13.36V17c0 2.21 2.69 4 6 4s6-1.79 6-4v-3.64l3-1.18V9L12 3z"/></svg>
            Mahasiswa
          </label>
          <label style="flex:1;display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:6px;cursor:pointer;font-size:13px;color:rgba(255,255,255,.5);transition:all .2s;" id="tabAdmin">
            <input type="radio" name="login_as" value="admin" onchange="switchTab('admin')" style="display:none;">
            <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor;flex-shrink:0;"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
            Admin/Dosen
          </label>
        </div>

        <div class="form-group">
          <label id="usernameLabel">NIM MAHASISWA</label>
          <input type="text" name="username" class="form-control" id="usernameInput"
            placeholder="Masukkan NIM" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            required autocomplete="username">
        </div>
        <div class="form-group">
          <label>PASSWORD</label>
          <input type="password" name="password" class="form-control"
            placeholder="Masukkan password" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">
          Masuk →
        </button>
      </form>

      <div style="margin-top:24px;padding-top:20px;border-top:1px solid rgba(255,255,255,.07);">
        <div style="font-size:11px;color:rgba(255,255,255,.3);margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;">Akun Demo</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;">
          <button onclick="fillDemo('admin','password')" class="btn btn-ghost btn-sm" style="font-size:11px;color:rgba(255,255,255,.4);border:1px solid rgba(255,255,255,.1);">Admin: admin / password</button>
          <button onclick="fillDemo('2025001','password','mhs')" class="btn btn-ghost btn-sm" style="font-size:11px;color:rgba(255,255,255,.4);border:1px solid rgba(255,255,255,.1);">Mahasiswa: 2025001 / password</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var tabMhs   = document.getElementById('tabMhs');
var tabAdmin = document.getElementById('tabAdmin');
var activeStyle = 'background:rgba(99,102,241,.2);color:white;border:1px solid rgba(99,102,241,.3);';
var inactiveStyle = '';
tabMhs.style.cssText = activeStyle;

function switchTab(tab) {
    var label = document.getElementById('usernameLabel');
    var input = document.getElementById('usernameInput');
    if (tab === 'admin') {
        tabAdmin.style.cssText = activeStyle;
        tabMhs.style.cssText   = inactiveStyle;
        label.textContent = 'USERNAME ADMIN';
        input.placeholder = 'Masukkan username';
    } else {
        tabMhs.style.cssText   = activeStyle;
        tabAdmin.style.cssText = inactiveStyle;
        label.textContent = 'NIM MAHASISWA';
        input.placeholder = 'Masukkan NIM';
    }
}
function fillDemo(user, pass, type) {
    document.getElementById('usernameInput').value = user;
    document.querySelector('input[name=password]').value = pass;
    if (type === 'mhs') {
        document.querySelector('input[value=mahasiswa]').checked = true;
        switchTab('mhs');
    } else {
        document.querySelector('input[value=admin]').checked = true;
        switchTab('admin');
    }
}
</script>
</body>
</html>
