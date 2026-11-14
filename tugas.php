<?php
// tugas.php — Tugas Mahasiswa
define('BASE_URL', '/uts_aplikasi');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Person.php';
require_once __DIR__ . '/classes/Karyawan.php';
require_once __DIR__ . '/includes/auth.php';
requireMahasiswa();

$mhs  = Mahasiswa::findById($_SESSION['karyawan_id']);
$db   = Database::getInstance();
$conn = $db->getConnection();
$mhsId = $mhs->getId();

// ---- Proses POST: Kumpulkan Tugas ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'kumpulkan') {
    $tugasId = (int)($_POST['tugas_id'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? '');

    if (!$tugasId) {
        flashMessage('danger', 'Tugas tidak valid.');
    } else {
        // Cek deadline — jika sudah lewat, status = terlambat
        $stmtCek = $conn->prepare("SELECT deadline FROM tugas WHERE id = ? AND status = 'aktif'");
        $stmtCek->bind_param("i", $tugasId);
        $stmtCek->execute();
        $row = $stmtCek->get_result()->fetch_assoc();

        if (!$row) {
            flashMessage('danger', 'Tugas tidak ditemukan atau sudah ditutup.');
        } else {
            $status = (strtotime($row['deadline']) < time()) ? 'terlambat' : 'terkumpul';

            // INSERT or UPDATE (jika sudah ada baris 'belum')
            $stmtSave = $conn->prepare(
                "INSERT INTO pengumpulan_tugas (tugas_id, mahasiswa_id, catatan, status, waktu_kumpul)
                 VALUES (?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE catatan = VALUES(catatan), status = VALUES(status), waktu_kumpul = NOW()"
            );
            $stmtSave->bind_param("iiss", $tugasId, $mhsId, $catatan, $status);

            if ($stmtSave->execute()) {
                $msg = ($status === 'terlambat')
                    ? 'Tugas dikumpulkan (terlambat dari deadline).'
                    : 'Tugas berhasil dikumpulkan! ✅';
                flashMessage($status === 'terlambat' ? 'warning' : 'success', $msg);
            } else {
                flashMessage('danger', 'Gagal mengumpulkan tugas: ' . $stmtSave->error);
            }
        }
    }

    header("Location: tugas.php"); exit;
}

// Tugas belum dikerjakan
$stmtBelum = $conn->prepare(
    "SELECT t.*, mk.nama_mk,
            COALESCE(pt.status,'belum') AS status_kumpul,
            TIMESTAMPDIFF(HOUR, NOW(), t.deadline) AS sisa_jam
     FROM tugas t
     JOIN mata_kuliah mk ON t.mata_kuliah_id = mk.id
     JOIN enrollment e ON e.mata_kuliah_id = mk.id AND e.mahasiswa_id = ?
     LEFT JOIN pengumpulan_tugas pt ON pt.tugas_id = t.id AND pt.mahasiswa_id = ?
     WHERE t.status = 'aktif'
       AND (pt.status IS NULL OR pt.status = 'belum')
     ORDER BY t.deadline ASC"
);
$stmtBelum->bind_param("ii", $mhsId, $mhsId);
$stmtBelum->execute();
$tugasBelum = $stmtBelum->get_result()->fetch_all(MYSQLI_ASSOC);

// Tugas sudah selesai
$stmtSudah = $conn->prepare(
    "SELECT t.*, mk.nama_mk,
            pt.status AS status_kumpul,
            pt.catatan AS catatan_kumpul,
            pt.waktu_kumpul,
            pt.nilai
     FROM tugas t
     JOIN mata_kuliah mk ON t.mata_kuliah_id = mk.id
     JOIN enrollment e ON e.mata_kuliah_id = mk.id AND e.mahasiswa_id = ?
     JOIN pengumpulan_tugas pt ON pt.tugas_id = t.id AND pt.mahasiswa_id = ?
     WHERE t.status = 'aktif'
       AND pt.status IN ('terkumpul','dinilai','terlambat')
     ORDER BY pt.waktu_kumpul DESC"
);
$stmtSudah->bind_param("ii", $mhsId, $mhsId);
$stmtSudah->execute();
$tugasSudah = $stmtSudah->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Tugas';
include __DIR__ . '/includes/header.php';
?>

<?php showFlash(); ?>

<div class="page-header">
  <h2>Tugas</h2>
  <p>Daftar semua tugas dari mata kuliah yang kamu ikuti</p>
</div>

<!-- Summary Stats -->
<div class="stats-grid mb-24">
  <div class="stat-card yellow">
    <div class="stat-icon yellow">
      <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6zm7-7h-2v2h-2v-2H7v-2h2V9h2v2h2v2z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= count($tugasBelum) ?></div>
      <div class="stat-label">Belum Dikerjakan</div>
    </div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?= count($tugasSudah) ?></div>
      <div class="stat-label">Sudah Dikumpulkan</div>
    </div>
  </div>
</div>

<!-- Section: Belum Dikerjakan -->
<div class="card mb-24">
  <div class="card-header">
    <h3>⏳ Belum Dikerjakan</h3>
    <span class="badge badge-warning"><?= count($tugasBelum) ?> tugas</span>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($tugasBelum)): ?>
    <div style="padding:32px;text-align:center;color:var(--muted);">
      <div style="font-size:32px;margin-bottom:8px;">🎉</div>
      Tidak ada tugas yang perlu dikerjakan!
    </div>
    <?php else: ?>
    <?php foreach ($tugasBelum as $t):
      $sisaJam = (int)$t['sisa_jam'];
      $expired = $sisaJam < 0;
      $urgent  = !$expired && $sisaJam <= 48;
      $bgColor = $expired ? '#fff5f5' : ($urgent ? '#fffbeb' : '#ffffff');
    ?>
    <div style="padding:16px 20px;border-bottom:1px solid var(--border-lt);background:<?= $bgColor ?>;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div style="flex:1;min-width:180px;">
          <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px;"><?= htmlspecialchars($t['judul']) ?></div>
          <div style="font-size:12px;color:var(--muted);margin-bottom:6px;"><?= htmlspecialchars($t['nama_mk']) ?></div>
          <?php if (!empty($t['deskripsi'])): ?>
          <div style="font-size:12px;color:var(--text-2);line-height:1.5;"><?= htmlspecialchars(mb_substr($t['deskripsi'],0,140)) ?><?= mb_strlen($t['deskripsi'])>140?'…':'' ?></div>
          <?php endif; ?>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0;">
          <?php if ($expired): ?>
            <span class="badge badge-danger">Terlambat</span>
          <?php elseif ($urgent): ?>
            <span class="badge badge-warning">⚡ <?= $sisaJam ?>j lagi</span>
          <?php else: ?>
            <span class="badge badge-muted"><?= ceil($sisaJam/24) ?> hari lagi</span>
          <?php endif; ?>
          <div style="font-size:11px;color:var(--muted);text-align:right;">Deadline:<br><?= date('d M Y H:i', strtotime($t['deadline'])) ?></div>
          <!-- Tombol Kumpulkan -->
          <button
            onclick="openKumpulModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['judul'])) ?>', '<?= htmlspecialchars(addslashes($t['nama_mk'])) ?>')"
            class="btn btn-primary btn-sm"
            style="font-size:12px;padding:6px 14px;">
            <?= $expired ? '📤 Kumpulkan (Terlambat)' : '📤 Kumpulkan' ?>
          </button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Section: Sudah Dikumpulkan -->
<div class="card">
  <div class="card-header">
    <h3>✅ Sudah Dikumpulkan</h3>
    <span class="badge badge-success"><?= count($tugasSudah) ?> tugas</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Judul Tugas</th>
          <th>Mata Kuliah</th>
          <th>Dikumpulkan</th>
          <th>Status</th>
          <th>Nilai</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tugasSudah)): ?>
        <tr><td colspan="6" class="text-center text-muted" style="padding:24px;">Belum ada tugas yang dikumpulkan.</td></tr>
        <?php else: ?>
        <?php foreach ($tugasSudah as $i => $t):
          $statusMap = [
            'terkumpul' => ['badge-info',    'Terkumpul'],
            'dinilai'   => ['badge-success', 'Dinilai'],
            'terlambat' => ['badge-danger',  'Terlambat'],
          ];
          [$cls, $lbl] = $statusMap[$t['status_kumpul']] ?? ['badge-muted', $t['status_kumpul']];
        ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td style="font-weight:600;">
            <?= htmlspecialchars($t['judul']) ?>
            <?php if (!empty($t['catatan_kumpul'])): ?>
            <div style="font-size:11px;color:var(--muted);margin-top:2px;font-weight:400;"><?= htmlspecialchars(mb_substr($t['catatan_kumpul'],0,60)) ?><?= mb_strlen($t['catatan_kumpul'])>60?'…':'' ?></div>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($t['nama_mk']) ?></td>
          <td style="font-size:12px;color:var(--muted);"><?= $t['waktu_kumpul'] ? date('d M Y H:i', strtotime($t['waktu_kumpul'])) : '-' ?></td>
          <td><span class="badge <?= $cls ?>"><?= $lbl ?></span></td>
          <td style="font-weight:700;color:var(--primary);"><?= $t['nilai'] !== null ? number_format($t['nilai'], 0) : '<span style="color:var(--muted);">—</span>' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Kumpulkan Tugas -->
<div class="modal-overlay" id="modalKumpulkan">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <h3>📤 Kumpulkan Tugas</h3>
      <button class="modal-close" onclick="closeModal('modalKumpulkan')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="aksi" value="kumpulkan">
      <input type="hidden" name="tugas_id" id="kumpulTugasId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Tugas</label>
          <div id="kumpulTugasJudul" style="font-weight:600;font-size:14px;color:var(--text);padding:8px 0;"></div>
          <div id="kumpulTugasMk" style="font-size:12px;color:var(--muted);"></div>
        </div>
        <div class="form-group" style="margin-top:16px;">
          <label class="form-label" for="kumpulCatatan">Catatan / Keterangan <span style="color:var(--muted);font-weight:400;">(opsional)</span></label>
          <textarea name="catatan" id="kumpulCatatan" class="form-control"
            rows="4" placeholder="Tuliskan catatan pengumpulan tugas..."></textarea>
        </div>
        <div id="kumpulInfo" style="font-size:12px;color:var(--muted);margin-top:8px;padding:8px 12px;background:var(--surface);border-radius:6px;">
          ℹ️ Pastikan kamu telah menyelesaikan tugas sebelum mengumpulkan.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalKumpulkan')">Batal</button>
        <button type="submit" class="btn btn-primary">📤 Kumpulkan Sekarang</button>
      </div>
    </form>
  </div>
</div>

<script>
function openKumpulModal(id, judul, matkul) {
    document.getElementById('kumpulTugasId').value = id;
    document.getElementById('kumpulTugasJudul').textContent = judul;
    document.getElementById('kumpulTugasMk').textContent = matkul;
    document.getElementById('kumpulCatatan').value = '';
    openModal('modalKumpulkan');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
