<?php
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /bank_sampah_alan/login.php');
    exit;
}

$role = $_SESSION['role'];

if ($role === 'admin') {
    header('Location: /bank_sampah_alan/admin/dashboard.php');
} else {
    // siswa & guru pakai dashboard yang sama
    header('Location: /bank_sampah_alan/warga/dashboard.php');
}
exit;
