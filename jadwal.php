<?php
// jadwal.php — Jadwal Kuliah Mahasiswa
define('BASE_URL', '/uts_aplikasi');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Person.php';
require_once __DIR__ . '/classes/Karyawan.php';
require_once __DIR__ . '/includes/auth.php';
requireMahasiswa();

$mhs   = Mahasiswa::findById($_SESSION['karyawan_id']);
$db    = Database::getInstance();
$conn  = $db->getConnection();
$mhsId = $mhs->getId();

$stmt = $conn->prepare(
    "SELECT j.*, mk.nama_mk, mk.kode_mk, d.nama AS nama_dosen
     FROM jadwal j
     JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
     LEFT JOIN dosen d ON mk.dosen_id = d.id
     JOIN enrollment e ON e.mata_kuliah_id = mk.id AND e.mahasiswa_id = ?
     ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_mulai"
);
$stmt->bind_param("i", $mhsId);
$stmt->execute();
$allJadwal = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group by hari
$hariList  = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$byHari    = [];
foreach ($hariList as $h) $byHari[$h] = [];
foreach ($allJadwal as $j) {
    $byHari[$j['hari']][] = $j;
}

// Today's Indonesian day name
$hariIndo  = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$hariIni   = $hariIndo[date('l')] ?? '';

// Card color palette per day
$dayColors = [
    'Senin'  => ['#1565c0','#e3f0ff','#1565c0'],
    'Selasa' => ['#0d6b52','#e6f7f2','#0d6b52'],
    'Rabu'   => ['#7c3aed','#f0eaff','#7c3aed'],
    'Kamis'  => ['#b45309','#fff7e6','#b45309'],
    'Jumat'  => ['#be123c','#fff0f3','#be123c'],
    'Sabtu'  => ['#0e7490','#e0f7fa','#0e7490'],
];

$pageTitle = 'Jadwal Kuliah';
include __DIR__ . '/includes/header.php';
?>

<?php showFlash(); ?>

<div class="page-header">
  <h2>Jadwal Kuliah</h2>
  <p>Jadwal kuliah mingguan — <?= SEMESTER_AKTIF ?></p>
</div>

<?php if (empty($allJadwal)): ?>
<div class="card">
  <div class="card-body text-center text-muted" style="padding:40px;">
    <div style="font-size:36px;margin-bottom:12px;">📅</div>
    Belum ada jadwal kuliah yang tersedia.
  </div>
</div>
<?php else: ?>

<?php foreach ($hariList as $hari):
  $items = $byHari[$hari];
  if (empty($items)) continue;
  [$accent, $lightBg, $border] = $dayColors[$hari] ?? ['#1565c0','#e3f0ff','#1565c0'];
  $isToday = ($hari === $hariIni);
?>
<div class="card mb-24" style="<?= $isToday ? "border:2px solid {$accent};" : '' ?>">
  <div class="card-header" style="background:<?= $lightBg ?>;border-bottom:1px solid <?= $border ?>22;">
    <h3 style="color:<?= $accent ?>;">
      <?= $isToday ? '📍 ' : '' ?><?= $hari ?>
      <?php if ($isToday): ?><span style="font-size:11px;font-weight:600;background:<?= $accent ?>;color:white;padding:2px 10px;border-radius:20px;margin-left:8px;vertical-align:middle;">Hari Ini</span><?php endif; ?>
    </h3>
    <span style="font-size:12px;color:var(--muted);"><?= count($items) ?> kelas</span>
  </div>
  <div class="card-body" style="padding:16px;">
    <div style="display:flex;flex-direction:column;gap:12px;">
    <?php foreach ($items as $j): ?>
    <div style="display:flex;gap:16px;align-items:flex-start;background:<?= $lightBg ?>;border:1px solid <?= $border ?>33;border-radius:10px;padding:14px 18px;">
      <!-- Time block -->
      <div style="flex-shrink:0;text-align:center;min-width:68px;">
        <div style="font-size:15px;font-weight:800;color:<?= $accent ?>;"><?= $j['jam_mulai'] ? substr($j['jam_mulai'],0,5) : '-' ?></div>
        <div style="font-size:10px;color:var(--muted);margin:2px 0;">s/d</div>
        <div style="font-size:13px;font-weight:600;color:var(--muted);"><?= $j['jam_selesai'] ? substr($j['jam_selesai'],0,5) : '-' ?></div>
      </div>
      <!-- Divider -->
      <div style="width:3px;background:<?= $accent ?>;border-radius:2px;flex-shrink:0;min-height:60px;opacity:.4;"></div>
      <!-- Info -->
      <div style="flex:1;">
        <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:3px;"><?= htmlspecialchars($j['nama_mk']) ?></div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:6px;"><?= htmlspecialchars($j['kode_mk']) ?></div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <span style="font-size:12px;color:var(--text-2);">
            <svg width="12" height="12" viewBox="0 0 24 24" style="fill:var(--muted);vertical-align:-1px;margin-right:3px;"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            <?= htmlspecialchars($j['nama_dosen'] ?? 'Dosen TBA') ?>
          </span>
          <?php if (!empty($j['ruangan'])): ?>
          <span style="font-size:12px;color:var(--text-2);">
            <svg width="12" height="12" viewBox="0 0 24 24" style="fill:var(--muted);vertical-align:-1px;margin-right:3px;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/></svg>
            <?= htmlspecialchars($j['ruangan']) ?>
          </span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
