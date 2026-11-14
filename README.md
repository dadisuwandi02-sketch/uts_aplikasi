# Sistem Absensi Karyawan — OOP PHP UTS

## Struktur Aplikasi

```
uts_aplikasi/
├── index.php               # Halaman Login
├── logout.php              # Proses Logout
├── dashboard.php           # Dashboard Karyawan
├── absensi.php             # Absensi Masuk/Keluar
├── riwayat.php             # Riwayat Absensi
├── izin.php                # Pengajuan Izin
├── profil.php              # Profil Karyawan
│
├── admin/
│   ├── dashboard.php       # Dashboard Admin
│   ├── karyawan.php        # Kelola Data Karyawan
│   ├── absensi.php         # Kelola Absensi
│   ├── izin.php            # Kelola Izin
│   └── laporan.php         # Laporan Bulanan
│
├── classes/
│   ├── Person.php          # KELAS INDUK (abstract)
│   ├── Karyawan.php        # KELAS ANAK (extends Person)
│   └── Absensi.php         # Kelas Absensi
│
├── config/
│   └── database.php        # Koneksi Database (Singleton)
│
├── includes/
│   ├── auth.php            # Helper Autentikasi
│   ├── header.php          # Template Header + Sidebar
│   └── footer.php          # Template Footer + JS
│
├── assets/
│   └── css/style.css       # Desain CSS
│
└── database/
    └── absensi.sql         # Schema & Seed Database
```

## Konsep OOP yang Diterapkan

### 1. Kelas Induk — `Person` (abstract)
**Atribut (5+):** `$id`, `$nama`, `$email`, `$no_telepon`, `$alamat`, `$password`, `$created_at`  
**Method (4+):** `setFromArray()`, `hashPassword()`, `verifyPassword()`, `getInfoDasar()`, `validate()`

### 2. Kelas Anak — `Karyawan extends Person` (Inheritance)
**Atribut tambahan (5+):** `$nip`, `$jabatan`, `$departemen_id`, `$tanggal_masuk`, `$foto`, `$role`, `$status`  
**Method (4+):** `simpan()`, `tampilkan()`, `login()`, `getAbsensiHariIni()`, `getRiwayatAbsensi()`, `updatePassword()`

### 3. Enkapsulasi
- Semua atribut menggunakan `private` / `protected`
- Akses data hanya melalui getter/setter publik
- Contoh: `getNama()`, `setNama()`, `getNip()`, `setJabatan()`, dsb.

### 4. Instansiasi Objek
```php
$karyawan = new Karyawan();          // instansiasi kelas anak
$karyawan->login($email, $pw);       // panggil method
$absensiObj = new Absensi();         // instansiasi kelas absensi
$absensiObj->absenMasuk($id);        // catat absensi
```

### 5. Inheritance
```
Person (abstract)
    └── Karyawan
```
`Karyawan` mewarisi semua atribut dan method dari `Person`, kemudian meng-override `setFromArray()`, `validate()`, dan mengimplementasikan abstract method `simpan()` & `tampilkan()`.

## Cara Instalasi

1. Copy folder ke `C:/xampp/htdocs/uts_aplikasi`
2. Buka phpMyAdmin, import file `database/absensi.sql`
3. Akses di browser: `http://localhost/uts_aplikasi`

## Akun Default

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@absensi.com | password |
| Karyawan | budi@absensi.com | karyawan123 |
| Karyawan | siti@absensi.com | karyawan123 |
