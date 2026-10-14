<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('admin');

$page_title = 'Dashboard Admin';
$active = 'dashboard';

// --- Ambil statistik ringkas ---
$totalPending   = $pdo->query("SELECT COUNT(*) c FROM pengajuan_setor WHERE status='pending'")->fetch()['c'];
$totalWarga     = $pdo->query("SELECT COUNT(*) c FROM user WHERE role IN ('siswa','guru')")->fetch()['c'];
$totalSaldo     = $pdo->query("SELECT COALESCE(SUM(saldo),0) s FROM user WHERE role IN ('siswa','guru')")->fetch()['s'];
$totalStokKg    = $pdo->query("SELECT COALESCE(SUM(stok_kg),0) s FROM sampah")->fetch()['s'];
$totalTarikMinta= $pdo->query("SELECT COUNT(*) c FROM tarik_saldo WHERE status='pending'")->fetch()['c'];

$stokPerJenis = $pdo->query("SELECT jenis_sampah, stok_kg, harga_per_kg FROM sampah ORDER BY jenis_sampah")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="mb-1">Halo, Admin 👋</h4>
<p class="text-muted mb-4">Ringkasan aktivitas bank sampah hari ini.</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card card-stat p-3">
            <div class="label">Pengajuan Pending</div>
            <div class="value"><?= $totalPending ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat p-3">
            <div class="label">Total Siswa/Guru</div>
            <div class="value"><?= $totalWarga ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat p-3">
            <div class="label">Total Saldo Beredar</div>
            <div class="value" style="font-size:1.3rem"><?= rupiah($totalSaldo) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat p-3">
            <div class="label">Permintaan Tarik Saldo</div>
            <div class="value"><?= $totalTarikMinta ?></div>
        </div>
    </div>
</div>

<div class="card p-3">
    <h6 class="mb-3"><i class="bi bi-box-seam"></i> Stok Sampah Saat Ini (siap disetor ke pengepul)</h6>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr><th>Jenis Sampah</th><th>Harga/kg</th><th>Stok (kg)</th></tr>
            </thead>
            <tbody>
                <?php foreach ($stokPerJenis as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['jenis_sampah']) ?></td>
                    <td><?= rupiah($s['harga_per_kg']) ?></td>
                    <td><?= number_format($s['stok_kg'], 2) ?> kg</td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($stokPerJenis)): ?>
                <tr><td colspan="3" class="text-muted text-center py-3">Belum ada data jenis sampah.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
