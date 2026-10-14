<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['siswa', 'guru']);

$page_title = 'Riwayat Setoran';
$active = 'riwayat';
$u = currentUser();

$stmt = $pdo->prepare("
    SELECT p.*, s.jenis_sampah, s.harga_per_kg
    FROM pengajuan_setor p JOIN sampah s ON s.id_sampah = p.id_sampah
    WHERE p.id_user = ? ORDER BY p.tanggal_ajuan DESC
");
$stmt->execute([$u['id_user']]);
$riwayat = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="mb-1"><i class="bi bi-clock-history"></i> Riwayat Setoran</h4>
<p class="text-muted mb-4">Semua pengajuan setoran sampah yang pernah Anda kirim.</p>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr><th>Tanggal Ajuan</th><th>Jenis Sampah</th><th>Berat</th><th>Estimasi Nilai</th><th>Status</th><th>Catatan Admin</th></tr>
            </thead>
            <tbody>
                <?php foreach ($riwayat as $r): ?>
                <tr>
                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($r['tanggal_ajuan'])) ?></td>
                    <td><?= htmlspecialchars($r['jenis_sampah']) ?></td>
                    <td><?= number_format($r['berat_kg'], 2) ?> kg</td>
                    <td><?= rupiah($r['harga_per_kg'] * $r['berat_kg']) ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td class="text-muted small"><?= htmlspecialchars($r['catatan_admin'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($riwayat)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat setoran.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
