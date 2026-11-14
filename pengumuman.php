<?php
// pengumuman.php — Pengumuman Mahasiswa
define('BASE_URL', '/uts_aplikasi');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Person.php';
require_once __DIR__ . '/classes/Karyawan.php';
require_once __DIR__ . '/includes/auth.php';
requireMahasiswa();

$mhs  = Mahasiswa::findById($_SESSION['karyawan_id']);
$db   = Database::getInstance();
$conn = $db->getConnection();

$pengumumanList = $conn->query(
    "SELECT p.*, d.nama AS nama_dosen, mk.nama_mk
     FROM pengumuman p
     LEFT JOIN dosen d ON p.dosen_id = d.id
     LEFT JOIN mata_kuliah mk ON p.mata_kuliah_id = mk.id
     ORDER BY p.penting DESC, p.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$countPenting = count(array_filter($pengumumanList, fn($p) => $p['penting'] == 1));

$pageTitle = 'Pengumuman';
include __DIR__ . '/includes/header.php';
?>

<?php showFlash(); ?>

<div class="page-header">
  <h2>Pengumuman</h2>
  <p>Informasi dan pemberitahuan penting dari dosen &amp; akademik</p>
</div>

<!-- Summary -->
<div class="stats-grid mb-24">
  <div class="stat-card blue">
    <div class="stat-icon blue">
      <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= count($pengumumanList) ?></div>
      <div class="stat-label">Total Pengumuman</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= $countPenting ?></div>
      <div class="stat-label">Pengumuman Penting</div>
    </div>
  </div>
</div>

<?php if (empty($pengumumanList)): ?>
<div class="card">
  <div class="card-body text-center text-muted" style="padding:40px;">
    <div style="font-size:36px;margin-bottom:12px;">📭</div>
    Belum ada pengumuman saat ini.
  </div>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:16px;">
<?php foreach ($pengumumanList as $p):
  $penting  = (bool)$p['penting'];
  $bgCard   = $penting ? '#fffbeb' : '#ffffff';
  $border   = $penting ? '#fcd34d' : 'var(--border)';
  $tanggal  = date('d M Y, H:i', strtotime($p['created_at']));
  $pengirim = $p['nama_dosen'] ?? 'UMMI';
?>
<div class="card" style="background:<?= $bgCard ?>;border:1px solid <?= $border ?>;">
  <div class="card-header" style="border-bottom:1px solid <?= $border ?>;">
    <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
      <?php if ($penting): ?>
      <span class="badge badge-danger" style="flex-shrink:0;">🔔 Penting</span>
      <?php else: ?>
      <span class="badge badge-muted" style="flex-shrink:0;">📌 Info</span>
      <?php endif; ?>
      <h3 style="font-size:15px;font-weight:700;color:var(--text);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
        <?= htmlspecialchars($p['judul']) ?>
      </h3>
    </div>
    <div style="font-size:11px;color:var(--muted);flex-shrink:0;text-align:right;">
      <?= $tanggal ?>
    </div>
  </div>
  <div class="card-body">
    <!-- Meta -->
    <div style="display:flex;gap:16px;margin-bottom:14px;flex-wrap:wrap;">
      <span style="font-size:12px;color:var(--muted);">
        <svg width="12" height="12" viewBox="0 0 24 24" style="fill:var(--muted);vertical-align:-1px;margin-right:3px;"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        <?= htmlspecialchars($pengirim) ?>
      </span>
      <?php if (!empty($p['nama_mk'])): ?>
      <span style="font-size:12px;color:var(--muted);">
        <svg width="12" height="12" viewBox="0 0 24 24" style="fill:var(--muted);vertical-align:-1px;margin-right:3px;"><path d="M12 3L1 9l4 2.18V17h2v-4.82L9 13.36V17c0 2.21 2.69 4 6 4s6-1.79 6-4v-3.64l3-1.18V9L12 3z"/></svg>
        <?= htmlspecialchars($p['nama_mk']) ?>
      </span>
      <?php endif; ?>
    </div>
    <!-- Body text -->
    <div style="font-size:14px;color:var(--text-2);line-height:1.75;white-space:pre-line;">
      <?= htmlspecialchars($p['isi'] ?? '') ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div><!-- end list -->
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
