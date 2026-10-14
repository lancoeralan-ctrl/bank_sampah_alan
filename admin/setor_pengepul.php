<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('admin');

$page_title = 'Setor ke Pengepul';
$active = 'setor_pengepul';
$pesan = '';
$pesan_tipe = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_sampah = (int) ($_POST['id_sampah'] ?? 0);
    $berat_kg = (float) ($_POST['berat_kg'] ?? 0);
    $harga_jual = (float) ($_POST['harga_jual_per_kg'] ?? 0);
    $nama_pengepul = trim($_POST['nama_pengepul'] ?? '');

    $cek = $pdo->prepare("SELECT * FROM sampah WHERE id_sampah = ?");
    $cek->execute([$id_sampah]);
    $sampah = $cek->fetch();

    if (!$sampah) {
        $pesan = 'Jenis sampah tidak ditemukan.';
        $pesan_tipe = 'danger';
    } elseif ($berat_kg <= 0 || $nama_pengepul === '' || $harga_jual <= 0) {
        $pesan = 'Lengkapi semua data dengan benar.';
        $pesan_tipe = 'danger';
    } elseif ($berat_kg > $sampah['stok_kg']) {
        $pesan = 'Berat melebihi stok yang tersedia (' . number_format($sampah['stok_kg'], 2) . ' kg).';
        $pesan_tipe = 'danger';
    } else {
        try {
            $pdo->beginTransaction();

            // Kunci baris & cek ulang stok di dalam transaksi, jaga-jaga ada input lain yang masuk duluan
            $lock = $pdo->prepare("SELECT stok_kg FROM sampah WHERE id_sampah = ? FOR UPDATE");
            $lock->execute([$id_sampah]);
            $stokTerkini = $lock->fetch()['stok_kg'] ?? 0;

            if ($berat_kg > $stokTerkini) {
                $pdo->rollBack();
                $pesan = 'Stok berubah sebelum disimpan. Sisa stok saat ini: ' . number_format($stokTerkini, 2) . ' kg.';
                $pesan_tipe = 'danger';
            } else {
                $total_rp = $berat_kg * $harga_jual;

                $pdo->prepare("INSERT INTO setor_pengepul (id_sampah, berat_kg, total_rp, nama_pengepul) VALUES (?, ?, ?, ?)")
                    ->execute([$id_sampah, $berat_kg, $total_rp, $nama_pengepul]);

                $pdo->prepare("UPDATE sampah SET stok_kg = stok_kg - ? WHERE id_sampah = ?")
                    ->execute([$berat_kg, $id_sampah]);

                $pdo->commit();
                $pesan = "Berhasil dicatat: $berat_kg kg {$sampah['jenis_sampah']} terjual ke $nama_pengepul senilai " . rupiah($total_rp) . '.';
            }
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $pesan = 'Gagal menyimpan: ' . $e->getMessage();
            $pesan_tipe = 'danger';
        }
    }
}

$jenisSampah = $pdo->query("SELECT * FROM sampah WHERE stok_kg > 0 ORDER BY jenis_sampah")->fetchAll();
$riwayat = $pdo->query("
    SELECT sp.*, s.jenis_sampah
    FROM setor_pengepul sp
    JOIN sampah s ON s.id_sampah = sp.id_sampah
    ORDER BY sp.tanggal DESC LIMIT 30
")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="mb-1"><i class="bi bi-truck"></i> Setor ke Pengepul</h4>
<p class="text-muted mb-4">Catat penjualan stok sampah yang terkumpul kepada pengepul.</p>

<?php if ($pesan): ?>
    <div class="alert alert-<?= $pesan_tipe ?> py-2"><?= htmlspecialchars($pesan) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card p-3">
            <h6 class="mb-3">Form Penjualan</h6>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Jenis Sampah</label>
                    <select name="id_sampah" class="form-select" required>
                        <option value="">-- pilih --</option>
                        <?php foreach ($jenisSampah as $s): ?>
                            <option value="<?= $s['id_sampah'] ?>">
                                <?= htmlspecialchars($s['jenis_sampah']) ?> (stok: <?= number_format($s['stok_kg'], 2) ?> kg)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Berat Terjual (kg)</label>
                    <input type="number" step="0.01" min="0.01" name="berat_kg" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Harga Jual per Kg (Rp)</label>
                    <input type="number" step="0.01" min="0" name="harga_jual_per_kg" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Pengepul</label>
                    <input type="text" name="nama_pengepul" class="form-control" required placeholder="Contoh: UD Jaya Daur Ulang">
                </div>
                <button type="submit" class="btn btn-orange w-100"><i class="bi bi-check2-circle"></i> Catat Penjualan</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card p-3">
            <h6 class="mb-3">Riwayat Penjualan ke Pengepul</h6>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr><th>Tanggal</th><th>Jenis</th><th>Berat</th><th>Pengepul</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayat as $r): ?>
                        <tr>
                            <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($r['jenis_sampah']) ?></td>
                            <td><?= number_format($r['berat_kg'], 2) ?> kg</td>
                            <td><?= htmlspecialchars($r['nama_pengepul']) ?></td>
                            <td><?= rupiah($r['total_rp']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($riwayat)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat penjualan ke pengepul.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
