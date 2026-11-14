<?php
// profil.php — Halaman Profil Saya (semua role)
define('BASE_URL', '/uts_aplikasi');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Person.php';
require_once __DIR__ . '/classes/Karyawan.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$db   = Database::getInstance();
$conn = $db->getConnection();
$role = $_SESSION['role'] ?? 'mahasiswa';
$uid  = (int)$_SESSION['karyawan_id'];

// Instansiasi objek berdasarkan role (polimorfisme)
if (isMahasiswa()) {
    $person = Mahasiswa::findById($uid);
} else {
    // Dosen / Admin — ambil dari tabel dosen
    $stmt = $conn->prepare("SELECT * FROM dosen WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $dosenData = $stmt->get_result()->fetch_assoc();
    $person = $dosenData; // array, handled below
}

$error   = '';
$success = '';

// ---- Proses POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'update_profil') {
        if (isMahasiswa() && $person instanceof Mahasiswa) {
            // Enkapsulasi: gunakan setter
            $person->setNama($_POST['nama'] ?? '');
            $person->setNoTelepon($_POST['no_telepon'] ?? '');
            $person->setAlamat($_POST['alamat'] ?? '');

            $errors = $person->validate();
            if (!empty($errors)) {
                $error = implode('<br>', $errors);
            } else {
                $hasil = $person->simpan();
                if ($hasil['success']) {
                    $_SESSION['nama'] = $person->getNama();
                    flashMessage('success', 'Profil berhasil diperbarui.');
                    header("Location: profil.php"); exit;
                } else {
                    $error = implode('<br>', $hasil['errors']);
                }
            }
        } else {
            // Dosen / Admin: direct SQL UPDATE
            $nama      = htmlspecialchars(trim($_POST['nama'] ?? ''));
            $no_telp   = htmlspecialchars(trim($_POST['no_telepon'] ?? ''));
            $alamat    = htmlspecialchars(trim($_POST['alamat'] ?? ''));
            if (empty($nama)) {
                $error = 'Nama tidak boleh kosong.';
            } else {
                $stmt = $conn->prepare("UPDATE dosen SET nama=?, no_telepon=?, alamat=? WHERE id=?");
                $stmt->bind_param("sssi", $nama, $no_telp, $alamat, $uid);
                if ($stmt->execute()) {
                    $_SESSION['nama'] = $nama;
                    // Refresh data
                    $stmt2 = $conn->prepare("SELECT * FROM dosen WHERE id=? LIMIT 1");
                    $stmt2->bind_param("i", $uid);
                    $stmt2->execute();
                    $dosenData = $stmt2->get_result()->fetch_assoc();
                    $person    = $dosenData;
                    flashMessage('success', 'Profil berhasil diperbarui.');
                    header("Location: profil.php"); exit;
                } else {
                    $error = 'Gagal memperbarui profil.';
                }
            }
        }

    } elseif ($aksi === 'ganti_password') {
        $pw_lama       = $_POST['pw_lama'] ?? '';
        $pw_baru       = $_POST['pw_baru'] ?? '';
        $pw_konfirmasi = $_POST['pw_konfirmasi'] ?? '';

        if ($pw_baru !== $pw_konfirmasi) {
            $error = 'Konfirmasi password baru tidak cocok.';
        } elseif (strlen($pw_baru) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } else {
            if (isMahasiswa() && $person instanceof Mahasiswa) {
                if (!$person->verifyPassword($pw_lama)) {
                    $error = 'Password lama tidak sesuai.';
                } else {
                    if ($person->updatePassword($pw_baru)) {
                        flashMessage('success', 'Password berhasil diubah.');
                        header("Location: profil.php"); exit;
                    } else {
                        $error = 'Gagal mengubah password.';
                    }
                }
            } else {
                // Dosen / Admin
                $hashed = $dosenData['password'] ?? '';
                if (!password_verify($pw_lama, $hashed)) {
                    $error = 'Password lama tidak sesuai.';
                } else {
                    $newHash = password_hash($pw_baru, PASSWORD_BCRYPT);
                    $stmt    = $conn->prepare("UPDATE dosen SET password=? WHERE id=?");
                    $stmt->bind_param("si", $newHash, $uid);
                    if ($stmt->execute()) {
                        flashMessage('success', 'Password berhasil diubah.');
                        header("Location: profil.php"); exit;
                    } else {
                        $error = 'Gagal mengubah password.';
                    }
                }
            }
        }
    }
}

// Helper untuk ambil nilai (bisa Mahasiswa object atau dosen array)
$nama      = ($person instanceof Mahasiswa) ? $person->getNama()       : ($person['nama']       ?? '');
$email     = ($person instanceof Mahasiswa) ? $person->getEmail()      : ($person['email']      ?? '');
$no_telp   = ($person instanceof Mahasiswa) ? $person->getNoTelepon()  : ($person['no_telepon'] ?? '');
$alamat    = ($person instanceof Mahasiswa) ? $person->getAlamat()     : ($person['alamat']     ?? '');
$initials  = strtoupper(substr($nama, 0, 1));

$pageTitle = 'Profil Saya';
include __DIR__ . '/includes/header.php';
?>

<?php showFlash(); ?>
<?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<div class="grid-2">
  <!-- Kiri: Avatar + Form Update Profil -->
  <div class="card">
    <div class="card-header"><h3>Data Diri</h3></div>
    <div class="card-body">
      <div class="text-center" style="margin-bottom:20px;">
        <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#1565c0,#00897b);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:#fff;margin:0 auto 10px;">
          <?= $initials ?>
        </div>
        <div class="fw-bold" style="font-size:16px;"><?= htmlspecialchars($nama) ?></div>
        <div style="font-size:12px;color:var(--muted);"><?= ucfirst($role) ?></div>
      </div>

      <form method="POST">
        <input type="hidden" name="aksi" value="update_profil">
        <div class="form-group">
          <label>Nama Lengkap</label>
          <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($nama) ?>" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" class="form-control" value="<?= htmlspecialchars($email) ?>" disabled style="background:#f8fafc;">
        </div>
        <div class="form-group">
          <label>No. Telepon</label>
          <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($no_telp ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Alamat</label>
          <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($alamat ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </form>
    </div>
  </div>

  <!-- Kanan: Ganti Password + Info Kartu -->
  <div>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><h3>Ganti Password</h3></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="aksi" value="ganti_password">
          <div class="form-group">
            <label>Password Lama</label>
            <input type="password" name="pw_lama" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Password Baru</label>
            <input type="password" name="pw_baru" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Konfirmasi Password Baru</label>
            <input type="password" name="pw_konfirmasi" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-warning">Ubah Password</button>
        </form>
      </div>
    </div>

    <!-- Info Card -->
    <div class="card">
      <div class="card-header"><h3>Informasi Akun</h3></div>
      <div class="card-body">
        <table style="width:100%;font-size:13px;">
          <?php if (isMahasiswa() && $person instanceof Mahasiswa): ?>
          <tr><td style="padding:8px 0;color:var(--muted);width:45%;">NIM</td>
              <td class="fw-bold"><?= htmlspecialchars($person->getNim() ?? '-') ?></td></tr>
          <tr><td style="padding:8px 0;color:var(--muted);">Program Studi</td>
              <td class="fw-bold"><?= htmlspecialchars($person->getNamaProdi() ?? '-') ?></td></tr>
          <tr><td style="padding:8px 0;color:var(--muted);">Semester</td>
              <td class="fw-bold"><?= $person->getSemester() ?? '-' ?></td></tr>
          <tr><td style="padding:8px 0;color:var(--muted);">Angkatan</td>
              <td class="fw-bold"><?= $person->getAngkatan() ?? '-' ?></td></tr>
          <tr><td style="padding:8px 0;color:var(--muted);">Status</td>
              <td><span class="badge <?= $person->getStatus()==='aktif' ? 'badge-success' : 'badge-muted' ?>"><?= ucfirst($person->getStatus() ?? '-') ?></span></td></tr>
          <?php else: ?>
          <tr><td style="padding:8px 0;color:var(--muted);width:45%;">NID / NIP</td>
              <td class="fw-bold"><?= htmlspecialchars($person['nid'] ?? $person['nip'] ?? '-') ?></td></tr>
          <tr><td style="padding:8px 0;color:var(--muted);">Jabatan</td>
              <td class="fw-bold"><?= htmlspecialchars($person['jabatan'] ?? '-') ?></td></tr>
          <tr><td style="padding:8px 0;color:var(--muted);">Mata Kuliah</td>
              <td class="fw-bold"><?= htmlspecialchars($person['mata_kuliah'] ?? '-') ?></td></tr>
          <tr><td style="padding:8px 0;color:var(--muted);">Status</td>
              <td><span class="badge <?= ($person['status'] ?? '') === 'aktif' ? 'badge-success' : 'badge-muted' ?>"><?= ucfirst($person['status'] ?? '-') ?></span></td></tr>
          <?php endif; ?>
          <tr><td style="padding:8px 0;color:var(--muted);">Role</td>
              <td><span class="badge badge-info"><?= ucfirst($role) ?></span></td></tr>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
