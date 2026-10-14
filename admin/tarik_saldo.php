<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('admin');

$page_title = 'Persetujuan Tarik Saldo';
$active = 'tarik_saldo';
$pesan = '';
$pesan_tipe = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tarik = (int) ($_POST['id_tarik'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';

    $stmt = $pdo->prepare("SELECT t.*, u.saldo FROM tarik_saldo t JOIN user u ON u.id_user = t.id_user WHERE t.id_tarik = ? AND t.status = 'pending'");
    $stmt->execute([$id_tarik]);
    $ajuan = $stmt->fetch();

    if (!$ajuan) {
        $pesan = 'Permintaan tidak ditemukan atau sudah diproses.';
        $pesan_tipe = 'danger';
    } elseif ($aksi === 'setuju') {
        if ($ajuan['saldo'] < $ajuan['nominal']) {
            $pesan = 'Saldo warga tidak mencukupi untuk penarikan ini.';
            $pesan_tipe = 'danger';
        } else {
            try {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE user SET saldo = saldo - ? WHERE id_user = ?")
                    ->execute([$ajuan['nominal'], $ajuan['id_user']]);
                $pdo->prepare("UPDATE tarik_saldo SET status = 'disetujui' WHERE id_tarik = ?")
                    ->execute([$id_tarik]);
                $pdo->commit();
                $pesan = 'Penarikan disetujui. Saldo warga telah dipotong ' . rupiah($ajuan['nominal']) . '.';
            } catch (\Throwable $e) {
                $pdo->rollBack();
                $pesan = 'Gagal memproses: ' . $e->getMessage();
                $pesan_tipe = 'danger';
            }
        }
    } elseif ($aksi === 'tolak') {
        $pdo->prepare("UPDATE tarik_saldo SET status = 'ditolak' WHERE id_tarik = ?")->execute([$id_tarik]);
        $pesan = 'Permintaan penarikan telah ditolak.';
    }
}

$pending = $pdo->query("
    SELECT t.*, u.nama, u.saldo
    FROM tarik_saldo t JOIN user u ON u.id_user = t.id_user
    WHERE t.status = 'pending' ORDER BY t.tanggal ASC
")->fetchAll();

$riwayat = $pdo->query("
    SELECT t.*, u.nama
    FROM tarik_saldo t JOIN user u ON u.id_user = t.id_user
    WHERE t.status != 'pending' ORDER BY t.tanggal DESC LIMIT 20
")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="mb-1"><i class="bi bi-cash-coin"></i> Persetujuan Tarik Saldo</h4>
<p class="text-muted mb-4">Setujui atau tolak permintaan pencairan saldo dari siswa/guru.</p>

<?php if ($pesan): ?>
    <div class="alert alert-<?= $pesan_tipe ?> py-2"><?= htmlspecialchars($pesan) ?></div>
<?php endif; ?>

<div class="card p-3 mb-4">
    <h6 class="mb-3">Menunggu Persetujuan</h6>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Tanggal</th><th>Nama</th><th>Saldo Saat Ini</th><th>Nominal Diminta</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($pending as $p): ?>
                <tr>
                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($p['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($p['nama']) ?></td>
                    <td><?= rupiah($p['saldo']) ?></td>
                    <td><?= rupiah($p['nominal']) ?></td>
                    <td class="text-end">
                        <form method="POST" class="d-inline" onsubmit="return confirm('Setujui penarikan ini?')">
                            <input type="hidden" name="id_tarik" value="<?= $p['id_tarik'] ?>">
                            <input type="hidden" name="aksi" value="setuju">
                            <button class="btn btn-sm btn-orange"><i class="bi bi-check-lg"></i> Setujui</button>
                        </form>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Tolak penarikan ini?')">
                            <input type="hidden" name="id_tarik" value="<?= $p['id_tarik'] ?>">
                            <input type="hidden" name="aksi" value="tolak">
                            <button class="btn btn-sm btn-outline-orange"><i class="bi bi-x-lg"></i> Tolak</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pending)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada permintaan yang menunggu.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card p-3">
    <h6 class="mb-3">Riwayat Diproses</h6>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Tanggal</th><th>Nama</th><th>Nominal</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($riwayat as $r): ?>
                <tr>
                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($r['nama']) ?></td>
                    <td><?= rupiah($r['nominal']) ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($riwayat)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada riwayat.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
