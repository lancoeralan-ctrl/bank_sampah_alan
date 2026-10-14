<?php
/**
 * Koneksi Database - Bank Sampah
 * Menggunakan PDO supaya bisa pakai prepared statement (aman dari SQL Injection)
 */

$host    = 'localhost';
$db      = 'bank_sampah_alan';
$dbuser  = 'root';   // default XAMPP
$dbpass  = '';       // default XAMPP kosong
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // biar error kelihatan jelas saat development
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (\PDOException $e) {
    // Di production, jangan tampilkan detail error ke user. Untuk belajar, ini masih aman.
    die('Koneksi database gagal: ' . $e->getMessage());
}
