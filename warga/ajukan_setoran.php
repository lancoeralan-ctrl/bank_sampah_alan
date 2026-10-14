<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['siswa', 'guru']);

$page_title = 'Ajukan Setoran';
$active = 'ajukan_setoran';
$u = currentUser();
$pesan = '';
$pesan_tipe = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_sampah = (int) ($_POST['id_sampah'] ?? 0);
    $berat_kg = (float) ($_POST['berat_kg'] ?? 0);

    if ($id_sampah <= 0 || $berat_kg <= 0) {
        $pesan = 'Pilih jenis sampah dan isi berat dengan benar.';
        $pesan_tipe = 'danger';
    } else {
        $pdo->prepare("INSERT INTO pengajuan_setor (id_user, id_sampah, berat_kg) VALUES (?, ?, ?)")
            ->execute([$u['id_user'], $id_sampah, $berat_kg]);
        $pesan = 'Pengajuan setoran berhasil dikirim. Menunggu verifikasi admin.';
    }
}

$jenisSampah = $pdo->query("SELECT * FROM sampah ORDER BY jenis_sampah")->fetchAll();

$stmt = $pdo->prepare("
    SELECT p.*, s.jenis_sampah, s.harga_per_kg
    FROM pengajuan_setor p JOIN sampah s ON s.id_sampah = p.id_sampah
    WHERE p.id_user = ? ORDER BY p.tanggal_ajuan DESC LIMIT 5
");
$stmt->execute([$u['id_user']]);
$terbaru = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="mb-1"><i class="bi bi-recycle"></i> Ajukan Setoran Sampah</h4>
<p class="text-muted mb-4">Pilih jenis sampah yang Anda kumpulkan dan masukkan beratnya. Admin akan memverifikasi sebelum saldo ditambahkan.</p>

<?php if ($pesan): ?>
    <div class="alert alert-<?= $pesan_tipe ?> py-2"><?= htmlspecialchars($pesan) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card p-3">
            <h6 class="mb-3">Form Pengajuan</h6>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Jenis Sampah</label>
                    <select name="id_sampah" id="id_sampah" class="form-select" required onchange="tampilkanEstimasi()">
                        <option value="">-- pilih --</option>
                        <?php foreach ($jenisSampah as $s): ?>
                            <option value="<?= $s['id_sampah'] ?>" data-harga="<?= $s['harga_per_kg'] ?>">
                                <?= htmlspecialchars($s['jenis_sampah']) ?> (<?= rupiah($s['harga_per_kg']) ?>/kg)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Berat (kg)</label>
                    <input type="number" step="0.01" min="0.01" name="berat_kg" id="berat_kg" class="form-control" required oninput="tampilkanEstimasi()">
                </div>
                <div class="mb-3 small text-muted">
                    Estimasi nilai: <span id="estimasi" class="text-white fw-bold">Rp 0</span>
                </div>
                <button type="submit" class="btn btn-orange w-100"><i class="bi bi-send"></i> Kirim Pengajuan</button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card p-3">
            <h6 class="mb-3">Pengajuan Terakhir Anda</h6>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Tanggal</th><th>Jenis</th><th>Berat</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($terbaru as $t): ?>
                        <tr>
                            <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($t['tanggal_ajuan'])) ?></td>
                            <td><?= htmlspecialchars($t['jenis_sampah']) ?></td>
                            <td><?= number_format($t['berat_kg'], 2) ?> kg</td>
                            <td><span class="badge badge-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($terbaru)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada pengajuan. Yuk mulai setor sampah pertama Anda!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="/bank_sampah_alan/warga/riwayat.php" class="small">Lihat semua riwayat &rarr;</a>
        </div>
    </div>
</div>

<script>
function tampilkanEstimasi() {
    const select = document.getElementById('id_sampah');
    const berat = parseFloat(document.getElementById('berat_kg').value) || 0;
    const harga = parseFloat(select.options[select.selectedIndex]?.dataset.harga) || 0;
    const total = berat * harga;
    document.getElementById('estimasi').textContent =
        'Rp ' + total.toLocaleString('id-ID', {maximumFractionDigits: 0});
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
