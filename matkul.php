<?php
// matkul.php — Daftar Mata Kuliah (Mahasiswa)
define('BASE_URL', '/uts_aplikasi');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Person.php';
require_once __DIR__ . '/classes/Karyawan.php';
require_once __DIR__ . '/includes/auth.php';
requireMahasiswa();

$db   = Database::getInstance();
$conn = $db->getConnection();

$matkul = $conn->query(
    "SELECT mk.*, 
     (SELECT COUNT(*) FROM pertemuan p WHERE p.mata_kuliah_id = mk.id) AS total_pertemuan
     FROM mata_kuliah mk ORDER BY mk.nama_mk"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Mata Kuliah';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <h2>Mata Kuliah</h2>
  <p>8 mata kuliah aktif — <?= KELAS ?></p>
</div>

<div class="stats-grid mb-24">
  <div class="stat-card blue">
    <div class="stat-icon blue">
      <svg viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18V17h2v-4.82L9 13.36V17c0 2.21 2.69 4 6 4s6-1.79 6-4v-3.64l3-1.18V9L12 3z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= count($matkul) ?></div>
      <div class="stat-label">Mata Kuliah</div>
    </div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green">
      <svg viewBox="0 0 24 24"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= array_sum(array_column($matkul, 'sks')) ?></div>
      <div class="stat-label">Total SKS</div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>Daftar Mata Kuliah</h3>
    <span class="badge badge-primary"><?= count($matkul) ?> matkul</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Kode</th>
          <th>Nama Mata Kuliah</th>
          <th>SKS</th>
          <th>Dosen Pengampu</th>
          <th style="text-align:center;">Pertemuan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($matkul as $i => $mk): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><span class="badge badge-primary" style="font-size:11px;"><?= htmlspecialchars($mk['kode_mk']) ?></span></td>
          <td style="font-weight:600;"><?= htmlspecialchars($mk['nama_mk']) ?></td>
          <td style="text-align:center;font-weight:700;color:var(--primary);"><?= $mk['sks'] ?></td>
          <td style="font-size:13px;color:var(--muted);"><?= htmlspecialchars($mk['dosen_pengampu']) ?></td>
          <td style="text-align:center;"><?= (int)$mk['total_pertemuan'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
