<?php
require_once __DIR__ . '/config/database.php';
$conn = Database::getInstance()->getConnection();

// Membuat hash baru yang dijamin 100% valid untuk password 'admin123'
$passwordBaru = password_hash('admin123', PASSWORD_BCRYPT);

$stmt = $conn->prepare("UPDATE dosen SET password = ? WHERE username IN ('admin', 'dosen')");
$stmt->bind_param("s", $passwordBaru);

if ($stmt->execute()) {
    echo "<h3>Berhasil! Password untuk username 'admin' dan 'dosen' sudah direset menjadi: <b>admin123</b></h3>";
    echo "<a href='index.php'>Klik di sini untuk kembali ke Login</a>";
} else {
    echo "Gagal: " . $conn->error;
}