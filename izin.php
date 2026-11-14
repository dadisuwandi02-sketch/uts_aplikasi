<?php
// izin.php — Pengajuan Izin Karyawan
define('BASE_URL', '/uts_aplikasi');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Person.php';
require_once __DIR__ . '/classes/Karyawan.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$karyawan = Karyawan::findById($_SESSION['karyawan_id']);
$db = Database::getInstance();
$conn = $db->getConnection();
$error = '';
$success = '';

// Proses pengajuan izin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis     = $_POST['jenis_izin'] ?? '';
    $tgl_mulai = $_POST['tanggal_mulai'] ?? '';
    $tgl_selesai = $_POST['tanggal_selesai'] ?? '';
    $alasan    = trim($_POST['alasan'] ?? '');

    if (empty($jenis) || empty($tgl_mulai) || empty($tgl_selesai) || empty($alasan)) {
        $error = 'Semua field wajib diisi.';
    } else {
        $stmt = $conn->prepare("INSERT INTO izin (karyawan_id, tanggal_mulai, tanggal_selesai, jenis_izin, alasan) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $karyawan->getId(), $tgl_mulai, $tgl_selesai, $jenis, $alasan);
        if ($stmt->execute()) {
            flashMessage('success', 'Pengajuan izin berhasil dikirim, menunggu persetujuan.');
            header("Location: izin.php");
            exit;
        } else {
            $error = 'Gagal menyimpan pengajuan izin.';
        }
    }
}

// Ambil riwayat izin karyawan ini
$stmt = $conn->prepare("SELECT * FROM izin WHERE karyawan_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $karyawan->getId());
$stmt->execute();
$riwayatIzin = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Pengajuan Izin';
include __DIR__ . '/includes/header.php';
?>

<?php showFlash(); ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="grid-2">
  <!-- Form Pengajuan -->
  <div class="card">
    <div class="card-header"><h3>Ajukan Izin / Sakit</h3></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label>Jenis Izin</label>
          <select name="jenis_izin" class="form-control" required>
            <option value="">-- Pilih Jenis Izin --</option>
            <option value="sakit">Sakit</option>
            <option value="cuti">Cuti Tahunan</option>
            <option value="keperluan_keluarga">Keperluan Keluarga</option>
            <option value="lainnya">Lainnya</option>
          </select>
        </div>
        <div class="form-group">
          <label>Tanggal Mulai</label>
          <input type="date" name="tanggal_mulai" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label>Tanggal Selesai</label>
          <input type="date" name="tanggal_selesai" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label>Alasan / Keterangan</label>
          <textarea name="alasan" class="form-control" rows="4" placeholder="Jelaskan alasan Anda..." required></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Kirim Pengajuan</button>
      </form>
    </div>
  </div>

  <!-- Riwayat Izin -->
  <div class="card">
    <div class="card-header"><h3>Riwayat Pengajuan</h3></div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Jenis</th><th>Tanggal</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (empty($riwayatIzin)): ?>
          <tr><td colspan="3" class="text-center text-muted" style="padding:20px;">Belum ada pengajuan.</td></tr>
          <?php else: ?>
          <?php foreach ($riwayatIzin as $iz): ?>
          <tr>
            <td><?= ucfirst(str_replace('_', ' ', $iz['jenis_izin'])) ?></td>
            <td style="font-size:12px;">
              <?= date('d/m/y', strtotime($iz['tanggal_mulai'])) ?> –
              <?= date('d/m/y', strtotime($iz['tanggal_selesai'])) ?>
            </td>
            <td>
              <?php
              $badges = ['pending'=>'warning','disetujui'=>'success','ditolak'=>'danger'];
              $b = $badges[$iz['status_persetujuan']] ?? 'muted';
              echo "<span class='badge badge-{$b}'>{$iz['status_persetujuan']}</span>";
              ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
