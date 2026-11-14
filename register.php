<?php
// register.php — Pendaftaran Mahasiswa Baru
define('BASE_URL', '/uts_aplikasi');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Person.php';
require_once __DIR__ . '/classes/Karyawan.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header("Location: " . BASE_URL . "/dashboard.php"); exit; }

$db   = Database::getInstance();
$conn = $db->getConnection();
$prodiList = $conn->query("SELECT * FROM prodi ORDER BY nama_prodi")->fetch_all(MYSQLI_ASSOC);

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nim'        => trim($_POST['nim'] ?? ''),
        'nama'       => trim($_POST['nama'] ?? ''),
        'email'      => trim($_POST['email'] ?? ''),
        'password'   => $_POST['password'] ?? '',
        'no_telepon' => trim($_POST['no_telepon'] ?? ''),
        'prodi_id'   => (int)($_POST['prodi_id'] ?? 0),
        'semester'   => (int)($_POST['semester'] ?? 1),
        'angkatan'   => (int)($_POST['angkatan'] ?? date('Y')),
    ];
    if (strlen($data['nim']) < 5)         $error = 'NIM minimal 5 karakter.';
    elseif (empty($data['nama']))          $error = 'Nama lengkap wajib diisi.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $error = 'Format email tidak valid.';
    elseif (strlen($data['password']) < 6) $error = 'Password minimal 6 karakter.';
    elseif ($_POST['password'] !== $_POST['konfirmasi_password']) $error = 'Konfirmasi password tidak cocok.';
    elseif ($data['prodi_id'] === 0)       $error = 'Pilih program studi.';
    else {
        $hasil = Mahasiswa::register($data);
        if ($hasil['success']) {
            $success = 'Pendaftaran berhasil! Silakan login dengan NIM dan password Anda.';
        } else {
            $error = implode(' ', $hasil['errors']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Mahasiswa — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body style="background:#0f172a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;">
<div style="width:100%;max-width:600px;">
  <!-- Header -->
  <div style="text-align:center;margin-bottom:32px;">
    <div style="width:64px;height:64px;background:linear-gradient(135deg,#1565c0,#0d47a1);border-radius:20px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;box-shadow:0 8px 24px rgba(21,101,192,.4);">
      <span style="font-size:22px;font-weight:900;color:white;">UM</span>
    </div>
    <h1 style="color:white;font-size:22px;font-weight:800;letter-spacing:-.5px;">Pendaftaran Mahasiswa</h1>
    <p style="color:rgba(255,255,255,.4);font-size:14px;margin-top:6px;"><?= APP_FULLNAME ?></p>
  </div>

  <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:32px;backdrop-filter:blur(20px);">
    <?php if ($success): ?>
    <div style="background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);border-radius:12px;padding:16px;color:#6ee7b7;margin-bottom:24px;text-align:center;">
      ✅ <?= htmlspecialchars($success) ?>
      <br><a href="<?= BASE_URL ?>/index.php" style="color:#34d399;font-weight:600;margin-top:8px;display:inline-block;">→ Klik untuk Login</a>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:12px;padding:14px;color:#fca5a5;margin-bottom:20px;">
      ⚠️ <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="grid-2">
        <div class="form-group">
          <label>NIM <span style="color:#ef4444;">*</span></label>
          <input type="text" name="nim" class="form-control" placeholder="2024001001" value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Nama Lengkap <span style="color:#ef4444;">*</span></label>
          <input type="text" name="nama" class="form-control" placeholder="Nama sesuai KTP" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Email Aktif <span style="color:#ef4444;">*</span></label>
        <input type="email" name="email" class="form-control" placeholder="nama@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Program Studi <span style="color:#ef4444;">*</span></label>
          <select name="prodi_id" class="form-control" required>
            <option value="">-- Pilih Prodi --</option>
            <?php foreach ($prodiList as $p): ?>
            <option value="<?= $p['id'] ?>" <?= ($_POST['prodi_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['nama_prodi']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Semester</label>
          <select name="semester" class="form-control">
            <?php for ($s=1; $s<=8; $s++): ?>
            <option value="<?= $s ?>" <?= ($_POST['semester'] ?? 1) == $s ? 'selected' : '' ?>>Semester <?= $s ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Angkatan</label>
          <select name="angkatan" class="form-control">
            <?php for ($y=date('Y'); $y>=date('Y')-6; $y--): ?>
            <option value="<?= $y ?>" <?= ($_POST['angkatan'] ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group">
          <label>No. Telepon</label>
          <input type="text" name="no_telepon" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['no_telepon'] ?? '') ?>">
        </div>
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Password <span style="color:#ef4444;">*</span></label>
          <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
        </div>
        <div class="form-group">
          <label>Konfirmasi Password <span style="color:#ef4444;">*</span></label>
          <input type="password" name="konfirmasi_password" class="form-control" placeholder="Ulangi password" required>
        </div>
      </div>
      <button type="submit" class="btn btn-block btn-lg" style="background:linear-gradient(135deg,#1565c0,#0d47a1);color:white;box-shadow:0 4px 14px rgba(21,101,192,.4);font-size:15px;margin-top:8px;">
        🎓 Daftar Sekarang
      </button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:13px;color:rgba(255,255,255,.3);">
      Sudah punya akun? <a href="<?= BASE_URL ?>/index.php" style="color:rgba(255,255,255,.6);font-weight:600;">Login di sini</a>
    </p>
  </div>
</div>
</body>
</html>
