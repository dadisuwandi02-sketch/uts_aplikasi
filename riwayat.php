<?php
// riwayat.php — Riwayat Absensi Karyawan
define('BASE_URL', '/uts_aplikasi');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Person.php';
require_once __DIR__ . '/classes/Karyawan.php';
require_once __DIR__ . '/classes/Absensi.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$karyawan   = Karyawan::findById($_SESSION['karyawan_id']);
$absensiObj = new Absensi();

$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

$riwayat = $karyawan->getRiwayatAbsensi($bulan, $tahun);
$rekap   = $absensiObj->getRekapBulanan($karyawan->getId(), $bulan, $tahun);

$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$pageTitle = 'Riwayat Absensi';
include __DIR__ . '/includes/header.php';
?>

<form method="GET" class="d-flex align-center gap-12 mb-24" style="flex-wrap:wrap;">
  <div class="form-group mb-0">
    <select name="bulan" class="form-control" style="width:160px;">
      <?php for ($i=1; $i<=12; $i++): ?>
      <option value="<?= $i ?>" <?= $i == $bulan ? 'selected' : '' ?>><?= $namaBulan[$i] ?></option>
      <?php endfor; ?>
    </select>
  </div>
  <div class="form-group mb-0">
    <select name="tahun" class="form-control" style="width:110px;">
      <?php for ($y = date('Y'); $y >= date('Y')-3; $y--): ?>
      <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
  </div>
  <button type="submit" class="btn btn-primary">Filter</button>
</form>

<!-- Rekap Bulanan -->
<div class="stats-grid mb-24">
  <?php foreach ([
    ['hadir', 'Hadir', 'green'],
    ['izin',  'Izin',  'yellow'],
    ['sakit', 'Sakit', 'cyan'],
    ['alpha', 'Alpha', 'red'],
  ] as [$key, $label, $color]): ?>
  <div class="stat-card">
    <div class="stat-icon <?= $color ?>">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= $rekap[$key] ?? 0 ?></div>
      <div class="stat-label"><?= $label ?> — <?= $namaBulan[(int)$bulan] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Tabel Riwayat -->
<div class="card">
  <div class="card-header">
    <h3>Detail Absensi — <?= $namaBulan[(int)$bulan] ?> <?= $tahun ?></h3>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Tanggal</th>
          <th>Hari</th>
          <th>Jam Masuk</th>
          <th>Jam Keluar</th>
          <th>Durasi</th>
          <th>Status</th>
          <th>Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($riwayat)): ?>
        <tr><td colspan="8" class="text-center text-muted" style="padding:24px;">Tidak ada data absensi.</td></tr>
        <?php else: ?>
        <?php foreach ($riwayat as $i => $r): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
          <td><?= date('l', strtotime($r['tanggal'])) ?></td>
          <td><?= $r['jam_masuk'] ?? '-' ?></td>
          <td><?= $r['jam_keluar'] ?? '-' ?></td>
          <td><?= $absensiObj->hitungDurasi($r['jam_masuk'], $r['jam_keluar']) ?></td>
          <td>
            <?php
            $badges = ['hadir'=>'success','izin'=>'warning','sakit'=>'info','alpha'=>'danger'];
            $b = $badges[$r['status']] ?? 'muted';
            echo "<span class='badge badge-{$b}'>{$r['status']}</span>";
            ?>
          </td>
          <td><?= htmlspecialchars($r['keterangan'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
