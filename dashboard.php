<?php
// dashboard.php — Dashboard Mahasiswa
define('BASE_URL', '/uts_aplikasi');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Person.php';
require_once __DIR__ . '/classes/Karyawan.php';
require_once __DIR__ . '/includes/auth.php';
requireMahasiswa();

$mhs  = Mahasiswa::findById($_SESSION['user_id']);
$db   = Database::getInstance();
$conn = $db->getConnection();
$mhsId = $mhs->getId();

// Rekap absensi semua matkul
$rekap = $mhs->getRekapAbsensi();

// Hitung total kehadiran
$totalHadir = $totalIzin = $totalSakit = $totalAlpha = 0;
foreach ($rekap as $r) {
    $totalHadir += (int)$r['hadir'];
    $totalIzin  += (int)$r['izin'];
    $totalSakit += (int)$r['sakit'];
    $totalAlpha += (int)$r['alpha'];
}
$totalPertemuan = $totalHadir + $totalIzin + $totalSakit + $totalAlpha;

// Absensi terbaru
$absensiTerbaru = $conn->query(
    "SELECT a.*, p.tanggal, p.materi, mk.nama_mk
     FROM absensi a
     JOIN pertemuan p ON p.id = a.pertemuan_id
     JOIN mata_kuliah mk ON mk.id = p.mata_kuliah_id
     WHERE a.mahasiswa_id = $mhsId
     ORDER BY a.waktu_absen DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<?php showFlash(); ?>

<div class="page-header">
  <h2>Dashboard</h2>
  <p>Selamat datang, <strong><?= htmlspecialchars($mhs->getNama()) ?></strong> — <?= KELAS ?></p>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card blue">
    <div class="stat-icon blue">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= $totalHadir ?></div>
      <div class="stat-label">Total Hadir</div>
    </div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green">
      <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= $totalIzin ?></div>
      <div class="stat-label">Izin</div>
    </div>
  </div>
  <div class="stat-card yellow">
    <div class="stat-icon yellow">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= $totalSakit ?></div>
      <div class="stat-label">Sakit</div>
    </div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon red">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= $totalAlpha ?></div>
      <div class="stat-label">Alpha</div>
    </div>
  </div>
</div>

<div class="grid-2">
  <!-- Rekap per Matkul -->
  <div class="card">
    <div class="card-header">
      <h3>Rekap Per Mata Kuliah</h3>
      <a href="<?= BASE_URL ?>/absensi.php" class="btn btn-outline btn-sm">Lihat Detail</a>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if (empty($rekap)): ?>
      <div style="padding:24px;text-align:center;color:var(--muted);">Belum ada data absensi.</div>
      <?php else: ?>
      <?php foreach ($rekap as $r):
        $total = (int)$r['total_pertemuan'];
        $hadir = (int)$r['hadir'];
        $pct   = $total > 0 ? round(($hadir / $total) * 100) : 0;
        $barColor = $pct >= 75 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
      ?>
      <div style="padding:12px 16px;border-bottom:1px solid var(--border-lt);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--text);"><?= htmlspecialchars($r['nama_mk']) ?></div>
            <div style="font-size:11px;color:var(--muted);"><?= $hadir ?>/<?= $total ?> pertemuan · H:<?= $r['hadir'] ?> I:<?= $r['izin'] ?> S:<?= $r['sakit'] ?> A:<?= $r['alpha'] ?></div>
          </div>
          <span style="font-size:14px;font-weight:800;color:<?= $barColor ?>;"><?= $pct ?>%</span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>;"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Absensi Terbaru -->
  <div class="card">
    <div class="card-header">
      <h3>Absensi Terbaru</h3>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if (empty($absensiTerbaru)): ?>
      <div style="padding:24px;text-align:center;color:var(--muted);">Belum ada rekaman absensi.</div>
      <?php else: ?>
      <?php
        $statusStyle = [
          'hadir'  => ['bg'=>'#d1fae5','color'=>'#065f46','label'=>'Hadir'],
          'izin'   => ['bg'=>'#dbeafe','color'=>'#1e40af','label'=>'Izin'],
          'sakit'  => ['bg'=>'#fef9c3','color'=>'#92400e','label'=>'Sakit'],
          'alpha'  => ['bg'=>'#fee2e2','color'=>'#991b1b','label'=>'Alpha'],
        ];
        foreach ($absensiTerbaru as $a):
          $s = $statusStyle[$a['status']] ?? ['bg'=>'#f1f5f9','color'=>'#64748b','label'=>ucfirst($a['status'])];
      ?>
      <div style="padding:12px 16px;border-bottom:1px solid var(--border-lt);display:flex;justify-content:space-between;align-items:center;">
        <div>
          <div style="font-size:13px;font-weight:600;color:var(--text);"><?= htmlspecialchars($a['nama_mk']) ?></div>
          <div style="font-size:11px;color:var(--muted);"><?= date('d M Y', strtotime($a['tanggal'])) ?><?= $a['materi'] ? ' · ' . htmlspecialchars(mb_substr($a['materi'],0,30)) : '' ?></div>
        </div>
        <span style="padding:3px 10px;border-radius:12px;font-size:12px;font-weight:700;background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>;"><?= $s['label'] ?></span>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
