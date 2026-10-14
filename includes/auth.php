<?php
/**
 * auth.php - "Penjaga pintu" aplikasi
 * WAJIB di-include PALING ATAS di setiap halaman (sebelum ada output HTML apapun),
 * karena session_start() harus dipanggil sebelum HTML dikirim ke browser.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Cek apakah user sudah login */
function isLoggedIn(): bool
{
    return isset($_SESSION['id_user']);
}

/** Wajib login, kalau belum -> lempar ke halaman login */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /bank_sampah_alan/login.php');
        exit;
    }
}

/**
 * Wajib login DAN role tertentu.
 * Contoh: requireRole('admin');  atau  requireRole(['siswa','guru']);
 */
function requireRole($roles): void
{
    requireLogin();
    $roles = (array) $roles;
    if (!in_array($_SESSION['role'], $roles, true)) {
        header('Location: /bank_sampah_alan/index.php');
        exit;
    }
}

/** Ambil data user yang sedang login (dari session, cepat tanpa query ulang) */
function currentUser(): array
{
    return [
        'id_user'  => $_SESSION['id_user']  ?? null,
        'nama'     => $_SESSION['nama']     ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role'     => $_SESSION['role']     ?? null,
    ];
}

/** Format angka jadi Rupiah, dipakai di banyak halaman */
function rupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}
