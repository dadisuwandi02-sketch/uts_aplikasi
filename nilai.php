<?php
// nilai.php — Nilai & Transkrip Mahasiswa
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
    "SELECT mk.nama_mk, mk.kode_mk, mk.sks,
            e.nilai_uts, e.nilai_uas, e.nilai_tugas, e.nilai_akhir, e.grade
     FROM enrollment e
     JOIN mata_kuliah mk ON e.mata_kuliah_id = mk.id
     WHERE e.mahasiswa_id = ?
     ORDER BY mk.nama_mk"
);
$stmt->bind_param("i", $mhsId);
$stmt->execute();
$nilaiList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Hitung IPK (rata-rata nilai_akhir yang tidak null, skala 0-100 → konversi ke 4.0)
$nilaiArr  = array_filter(array_column($nilaiList, 'nilai_akhir'), fn($v) => $v !== null && $v !== '');
$ipk       = count($nilaiArr) > 0 ? round(array_sum($nilaiArr) / count($nilaiArr), 2) : null;

// Total SKS yang memiliki nilai akhir
$sksSelesai = 0;
foreach ($nilaiList as $n) {
    if ($n['nilai_akhir'] !== null && $n['nilai_akhir'] !== '') {
        $sksSelesai += (int)$n['sks'];
    }
}
$totalSks = array_sum(array_column($nilaiList, 'sks'));

// Grade color helper
function gradeColor($grade) {
    return match ($grade) {
        'A'  => '#10b981',
        'B+','B' => '#3b82f6',
        'C+','C' => '#f59e0b',
        'D'  => '#f97316',
        'E','F' => '#ef4444',
        default => '#94a3b8',
    };
}

$pageTitle = 'Nilai & Transkrip';
include __DIR__ . '/includes/header.php';
?>

<?php showFlash(); ?>

<div class="page-header">
  <h2>Nilai &amp; Transkrip</h2>
  <p>Hasil studi kamu untuk semester <?= SEMESTER_AKTIF ?></p>
</div>

<!-- Summary Stats -->
<div class="stats-grid mb-24">
  <div class="stat-card blue">
    <div class="stat-icon blue">
      <svg viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18V17h2v-4.82L9 13.36V17c0 2.21 2.69 4 6 4s6-1.79 6-4v-3.64l3-1.18V9L12 3zm0 2.24l7.16 3.93L12 12.85 4.84 9.17 12 5.24z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= count($nilaiList) ?></div>
      <div class="stat-label">Mata Kuliah</div>
    </div>
  </div>
  <div class="stat-card cyan">
    <div class="stat-icon cyan">
      <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= $totalSks ?></div>
      <div class="stat-label">Total SKS</div>
    </div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green">
      <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= $sksSelesai ?></div>
      <div class="stat-label">SKS Bernilai</div>
    </div>
  </div>
  <div class="stat-card yellow">
    <div class="stat-icon yellow">
      <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4l5 2.18V11c0 3.42-2.3 6.62-5 7.93-2.7-1.31-5-4.5-5-7.93V7.18L12 5z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= $ipk !== null ? number_format($ipk, 1) : '-' ?></div>
      <div class="stat-label">Rata-rata Nilai</div>
    </div>
  </div>
</div>

<!-- Tabel Nilai -->
<div class="card">
  <div class="card-header">
    <h3>Transkrip Nilai</h3>
    <?php if ($ipk !== null): ?>
    <span class="badge badge-primary">Rata-rata: <?= number_format($ipk, 2) ?></span>
    <?php endif; ?>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:44px;">#</th>
          <th>Kode MK</th>
          <th>Nama Mata Kuliah</th>
          <th style="text-align:center;">SKS</th>
          <th style="text-align:center;">Nilai Tugas</th>
          <th style="text-align:center;">Nilai UTS</th>
          <th style="text-align:center;">Nilai UAS</th>
          <th style="text-align:center;">Nilai Akhir</th>
          <th style="text-align:center;">Grade</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($nilaiList)): ?>
        <tr><td colspan="9" class="text-center text-muted" style="padding:28px;">Belum ada data nilai.</td></tr>
        <?php else: ?>
        <?php foreach ($nilaiList as $i => $n): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($n['kode_mk']) ?></td>
          <td style="font-weight:600;"><?= htmlspecialchars($n['nama_mk']) ?></td>
          <td style="text-align:center;"><?= (int)$n['sks'] ?></td>
          <td style="text-align:center;"><?= $n['nilai_tugas'] !== null ? number_format((float)$n['nilai_tugas'], 1) : '-' ?></td>
          <td style="text-align:center;"><?= $n['nilai_uts'] !== null ? number_format((float)$n['nilai_uts'], 1) : '-' ?></td>
          <td style="text-align:center;"><?= $n['nilai_uas'] !== null ? number_format((float)$n['nilai_uas'], 1) : '-' ?></td>
          <td style="text-align:center;font-weight:700;">
            <?php if ($n['nilai_akhir'] !== null): ?>
            <?php $na = (float)$n['nilai_akhir']; $naColor = $na >= 75 ? '#10b981' : ($na >= 50 ? '#f59e0b' : '#ef4444'); ?>
            <span style="color:<?= $naColor ?>;"><?= number_format($na, 1) ?></span>
            <?php else: ?>
            <span style="color:var(--muted);">-</span>
            <?php endif; ?>
          </td>
          <td style="text-align:center;">
            <?php if (!empty($n['grade'])): ?>
            <span style="font-size:15px;font-weight:800;color:<?= gradeColor($n['grade']) ?>;"><?= htmlspecialchars($n['grade']) ?></span>
            <?php else: ?>
            <span style="color:var(--muted);">-</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($nilaiList)): ?>
      <tfoot>
        <tr style="background:#f8fafc;font-weight:700;">
          <td colspan="3" style="padding:12px 16px;font-size:13px;">Total / Rata-rata</td>
          <td style="text-align:center;"><?= $totalSks ?> SKS</td>
          <td colspan="3"></td>
          <td style="text-align:center;color:<?= $ipk !== null ? ($ipk >= 75 ? '#10b981' : ($ipk >= 50 ? '#f59e0b' : '#ef4444')) : 'var(--muted)' ?>;">
            <?= $ipk !== null ? number_format($ipk, 2) : '-' ?>
          </td>
          <td></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
