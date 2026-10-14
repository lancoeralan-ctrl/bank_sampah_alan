<?php
/**
 * reset_admin.php
 * -----------------------------------------------------
 * Jalankan file ini SEKALI lewat browser:
 *   http://localhost/bank_sampah_alan/reset_admin.php
 *
 * File ini akan membuat ULANG password admin dengan hash
 * yang dihitung langsung oleh PHP di server Anda saat ini
 * juga (jadi dijamin cocok, tidak ada tebak-tebakan hash).
 *
 * SETELAH BERHASIL LOGIN, HAPUS FILE INI.
 * -----------------------------------------------------
 */

require_once __DIR__ . '/config/database.php';

$username_admin = 'admin';
$password_baru  = 'admin123'; // silakan ganti string ini kalau mau password lain

$hash = password_hash($password_baru, PASSWORD_DEFAULT);

// Cek dulu apakah akun admin dengan username ini sudah ada
$cek = $pdo->prepare("SELECT id_user FROM user WHERE username = ?");
$cek->execute([$username_admin]);
$ada = $cek->fetch();

if ($ada) {
    // Sudah ada -> update saja passwordnya
    $stmt = $pdo->prepare("UPDATE user SET password = ? WHERE username = ?");
    $stmt->execute([$hash, $username_admin]);
    echo "<h3>Password admin berhasil di-reset.</h3>";
} else {
    // Belum ada -> buat baru
    $stmt = $pdo->prepare("INSERT INTO user (nama, username, password, role, keterangan, saldo) VALUES (?, ?, ?, 'admin', NULL, 0)");
    $stmt->execute(['Administrator', $username_admin, $hash]);
    echo "<h3>Akun admin baru berhasil dibuat.</h3>";
}

echo "<p>Username: <b>$username_admin</b><br>Password: <b>$password_baru</b></p>";
echo "<p style='color:red;font-weight:bold;'>PENTING: Hapus file reset_admin.php ini sekarang, lalu login dan ganti password lewat aplikasi.</p>";
echo "<p><a href='/bank_sampah_alan/login.php'>Ke halaman login &rarr;</a></p>";
