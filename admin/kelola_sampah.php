<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('admin');

$page_title = 'Kelola Jenis Sampah';
$active = 'kelola_sampah';
$pesan = '';
$pesan_tipe = 'success';
$edit_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $jenis = trim($_POST['jenis_sampah'] ?? '');
        $harga = (float) ($_POST['harga_per_kg'] ?? 0);
        if ($jenis === '' || $harga <= 0) {
            $pesan = 'Nama jenis sampah dan harga wajib diisi dengan benar.';
            $pesan_tipe = 'danger';
        } else {
            $pdo->prepare("INSERT INTO sampah (jenis_sampah, harga_per_kg, stok_kg) VALUES (?, ?, 0)")
                ->execute([$jenis, $harga]);
            $pesan = "Jenis sampah \"$jenis\" berhasil ditambahkan.";
        }
    } elseif ($aksi === 'update') {
        $id = (int) ($_POST['id_sampah'] ?? 0);
        $jenis = trim($_POST['jenis_sampah'] ?? '');
        $harga = (float) ($_POST['harga_per_kg'] ?? 0);
        $pdo->prepare("UPDATE sampah SET jenis_sampah = ?, harga_per_kg = ? WHERE id_sampah = ?")
            ->execute([$jenis, $harga, $id]);
        $pesan = 'Data jenis sampah berhasil diperbarui.';
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id_sampah'] ?? 0);
        try {
            $pdo->prepare("DELETE FROM sampah WHERE id_sampah = ?")->execute([$id]);
            $pesan = 'Jenis sampah berhasil dihapus.';
        } catch (\PDOException $e) {
            $pesan = 'Tidak bisa dihapus karena jenis sampah ini sudah punya riwayat transaksi.';
            $pesan_tipe = 'danger';
        }
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM sampah WHERE id_sampah = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $edit_data = $stmt->fetch();
}

$daftar = $pdo->query("SELECT * FROM sampah ORDER BY jenis_sampah")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="mb-1"><i class="bi bi-trash3"></i> Kelola Jenis Sampah</h4>
<p class="text-muted mb-4">Atur jenis sampah yang diterima beserta harga beli per kilogram.</p>

<?php if ($pesan): ?>
    <div class="alert alert-<?= $pesan_tipe ?> py-2"><?= htmlspecialchars($pesan) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card p-3">
            <h6 class="mb-3"><?= $edit_data ? 'Edit Jenis Sampah' : 'Tambah Jenis Sampah' ?></h6>
            <form method="POST">
                <input type="hidden" name="aksi" value="<?= $edit_data ? 'update' : 'tambah' ?>">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id_sampah" value="<?= $edit_data['id_sampah'] ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Nama Jenis Sampah</label>
                    <input type="text" name="jenis_sampah" class="form-control" required
                           value="<?= htmlspecialchars($edit_data['jenis_sampah'] ?? '') ?>" placeholder="Contoh: Botol Plastik">
                </div>
                <div class="mb-3">
                    <label class="form-label">Harga per Kg (Rp)</label>
                    <input type="number" step="0.01" min="0" name="harga_per_kg" class="form-control" required
                           value="<?= htmlspecialchars($edit_data['harga_per_kg'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-orange w-100">
                    <i class="bi bi-save"></i> <?= $edit_data ? 'Simpan Perubahan' : 'Tambah' ?>
                </button>
                <?php if ($edit_data): ?>
                    <a href="/bank_sampah_alan/admin/kelola_sampah.php" class="btn btn-outline-orange w-100 mt-2">Batal</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card p-3">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr><th>Jenis Sampah</th><th>Harga/kg</th><th>Stok Saat Ini</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['jenis_sampah']) ?></td>
                            <td><?= rupiah($s['harga_per_kg']) ?></td>
                            <td><?= number_format($s['stok_kg'], 2) ?> kg</td>
                            <td class="text-end">
                                <a href="?edit=<?= $s['id_sampah'] ?>" class="btn btn-sm btn-outline-orange"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus jenis sampah ini?')">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id_sampah" value="<?= $s['id_sampah'] ?>">
                                    <button class="btn btn-sm btn-outline-orange"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($daftar)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada jenis sampah terdaftar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
