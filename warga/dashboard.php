<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['siswa', 'guru']);

$page_title = 'Dashboard';
$active = 'dashboard';
$u = currentUser();

// Saldo terkini (ambil ulang dari DB, jangan andalkan session supaya selalu akurat)
$stmt = $pdo->prepare("SELECT saldo FROM user WHERE id_user = ?");
$stmt->execute([$u['id_user']]);
$saldo = $stmt->fetch()['saldo'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM pengajuan_setor WHERE id_user = ? AND status = 'pending'");
$stmt->execute([$u['id_user']]);
$pendingSaya = $stmt->fetch()['c'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(berat_kg),0) t FROM transaksi WHERE id_user = ? AND tipe='setor'");
$stmt->execute([$u['id_user']]);
$totalBeratSetor = $stmt->fetch()['t'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="mb-1">Halo, <?= htmlspecialchars($u['nama']) ?> 👋</h4>
<p class="text-muted mb-4">Terus kumpulkan sampah, terus dapat saldo.</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4">
        <div class="card card-stat p-3">
            <div class="label">Saldo Saya</div>
            <div class="value"><?= rupiah($saldo) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card card-stat p-3">
            <div class="label">Total Sampah Disetor</div>
            <div class="value"><?= number_format($totalBeratSetor, 2) ?> kg</div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card card-stat p-3">
            <div class="label">Pengajuan Menunggu</div>
            <div class="value"><?= $pendingSaya ?></div>
        </div>
    </div>
</div>

<div class="card p-4 text-center">
    <p class="mb-3 text-muted">Sudah kumpulkan sampah hari ini?</p>
    <a href="/bank_sampah_alan/warga/ajukan_setoran.php" class="btn btn-orange px-4 py-2 mx-auto" style="max-width:280px">
        <i class="bi bi-recycle"></i> Ajukan Setoran Sekarang
    </a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
