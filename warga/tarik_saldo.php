<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['siswa', 'guru']);

$page_title = 'Tarik Saldo';
$active = 'tarik_saldo';
$u = currentUser();
$pesan = '';
$pesan_tipe = 'success';

$stmt = $pdo->prepare("SELECT saldo FROM user WHERE id_user = ?");
$stmt->execute([$u['id_user']]);
$saldo = $stmt->fetch()['saldo'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominal = (float) ($_POST['nominal'] ?? 0);

    $cekPending = $pdo->prepare("SELECT COUNT(*) c FROM tarik_saldo WHERE id_user = ? AND status = 'pending'");
    $cekPending->execute([$u['id_user']]);

    if ($nominal <= 0) {
        $pesan = 'Nominal penarikan tidak valid.';
        $pesan_tipe = 'danger';
    } elseif ($nominal > $saldo) {
        $pesan = 'Nominal melebihi saldo Anda saat ini (' . rupiah($saldo) . ').';
        $pesan_tipe = 'danger';
    } elseif ($cekPending->fetch()['c'] > 0) {
        $pesan = 'Anda masih punya permintaan penarikan yang belum diproses admin.';
        $pesan_tipe = 'danger';
    } else {
        $pdo->prepare("INSERT INTO tarik_saldo (id_user, nominal) VALUES (?, ?)")->execute([$u['id_user'], $nominal]);
        $pesan = 'Permintaan penarikan sebesar ' . rupiah($nominal) . ' telah dikirim, menunggu persetujuan admin.';
    }
}

$stmt = $pdo->prepare("SELECT * FROM tarik_saldo WHERE id_user = ? ORDER BY tanggal DESC");
$stmt->execute([$u['id_user']]);
$riwayat = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="mb-1"><i class="bi bi-wallet2"></i> Tarik Saldo</h4>
<p class="text-muted mb-4">Ajukan pencairan saldo hasil setoran sampah Anda.</p>

<?php if ($pesan): ?>
    <div class="alert alert-<?= $pesan_tipe ?> py-2"><?= htmlspecialchars($pesan) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card card-stat p-3 mb-3">
            <div class="label">Saldo Anda Saat Ini</div>
            <div class="value"><?= rupiah($saldo) ?></div>
        </div>
        <div class="card p-3">
            <h6 class="mb-3">Ajukan Penarikan</h6>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nominal (Rp)</label>
                    <input type="number" step="1" min="1" max="<?= $saldo ?>" name="nominal" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-orange w-100"><i class="bi bi-send"></i> Ajukan Penarikan</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card p-3">
            <h6 class="mb-3">Riwayat Penarikan</h6>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Tanggal</th><th>Nominal</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($riwayat as $r): ?>
                        <tr>
                            <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?></td>
                            <td><?= rupiah($r['nominal']) ?></td>
                            <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($riwayat)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">Belum ada riwayat penarikan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
