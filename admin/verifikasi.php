<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('admin');

$page_title = 'Verifikasi Setoran';
$active = 'verifikasi';
$pesan = '';
$pesan_tipe = 'success';

// --- Proses aksi terima / tolak ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pengajuan = (int) ($_POST['id_pengajuan'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM pengajuan_setor WHERE id_pengajuan = ? AND status = 'pending'");
    $stmt->execute([$id_pengajuan]);
    $ajuan = $stmt->fetch();

    if (!$ajuan) {
        $pesan = 'Pengajuan tidak ditemukan atau sudah diproses sebelumnya.';
        $pesan_tipe = 'danger';
    } elseif ($aksi === 'terima') {
        try {
            $pdo->beginTransaction();

            $sampah = $pdo->prepare("SELECT harga_per_kg FROM sampah WHERE id_sampah = ? FOR UPDATE");
            $sampah->execute([$ajuan['id_sampah']]);
            $harga = $sampah->fetch()['harga_per_kg'] ?? 0;
            $total_rp = $harga * $ajuan['berat_kg'];

            $pdo->prepare("INSERT INTO transaksi (id_user, id_sampah, id_pengajuan, tipe, berat_kg, total_rp) VALUES (?, ?, ?, 'setor', ?, ?)")
                ->execute([$ajuan['id_user'], $ajuan['id_sampah'], $ajuan['id_pengajuan'], $ajuan['berat_kg'], $total_rp]);

            $pdo->prepare("UPDATE user SET saldo = saldo + ? WHERE id_user = ?")
                ->execute([$total_rp, $ajuan['id_user']]);

            $pdo->prepare("UPDATE sampah SET stok_kg = stok_kg + ? WHERE id_sampah = ?")
                ->execute([$ajuan['berat_kg'], $ajuan['id_sampah']]);

            $pdo->prepare("UPDATE pengajuan_setor SET status = 'diterima', tanggal_verifikasi = NOW() WHERE id_pengajuan = ?")
                ->execute([$id_pengajuan]);

            $pdo->commit();
            $pesan = 'Setoran diterima. Saldo warga bertambah ' . rupiah($total_rp) . '.';
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $pesan = 'Gagal memproses: ' . $e->getMessage();
            $pesan_tipe = 'danger';
        }
    } elseif ($aksi === 'tolak') {
        $catatan = trim($_POST['catatan_admin'] ?? '') ?: 'Ditolak oleh admin';
        $pdo->prepare("UPDATE pengajuan_setor SET status = 'ditolak', catatan_admin = ?, tanggal_verifikasi = NOW() WHERE id_pengajuan = ?")
            ->execute([$catatan, $id_pengajuan]);
        $pesan = 'Pengajuan telah ditolak.';
    }
}

$daftar = $pdo->query("
    SELECT p.*, u.nama AS nama_user, u.role, s.jenis_sampah, s.harga_per_kg
    FROM pengajuan_setor p
    JOIN user u ON u.id_user = p.id_user
    JOIN sampah s ON s.id_sampah = p.id_sampah
    WHERE p.status = 'pending'
    ORDER BY p.tanggal_ajuan ASC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="mb-1"><i class="bi bi-check2-square"></i> Verifikasi Setoran</h4>
<p class="text-muted mb-4">Periksa dan setujui pengajuan setoran sampah dari siswa/guru.</p>

<?php if ($pesan): ?>
    <div class="alert alert-<?= $pesan_tipe ?> py-2"><?= htmlspecialchars($pesan) ?></div>
<?php endif; ?>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Tanggal Ajuan</th>
                    <th>Pengaju</th>
                    <th>Jenis Sampah</th>
                    <th>Berat</th>
                    <th>Estimasi Nilai</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftar as $d): ?>
                <tr>
                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($d['tanggal_ajuan'])) ?></td>
                    <td><?= htmlspecialchars($d['nama_user']) ?> <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($d['role']) ?></span></td>
                    <td><?= htmlspecialchars($d['jenis_sampah']) ?></td>
                    <td><?= number_format($d['berat_kg'], 2) ?> kg</td>
                    <td><?= rupiah($d['harga_per_kg'] * $d['berat_kg']) ?></td>
                    <td class="text-end">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="id_pengajuan" value="<?= $d['id_pengajuan'] ?>">
                            <input type="hidden" name="aksi" value="terima">
                            <button class="btn btn-sm btn-orange" onclick="return confirm('Terima setoran ini dan tambahkan saldo?')">
                                <i class="bi bi-check-lg"></i> Terima
                            </button>
                        </form>
                        <button class="btn btn-sm btn-outline-orange" data-bs-toggle="modal" data-bs-target="#tolakModal<?= $d['id_pengajuan'] ?>">
                            <i class="bi bi-x-lg"></i> Tolak
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($daftar)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada pengajuan yang menunggu verifikasi. Semua sudah diproses 🎉</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($daftar as $d): ?>
<div class="modal fade" id="tolakModal<?= $d['id_pengajuan'] ?>" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content" style="background:var(--surface); border:1px solid var(--border-soft);">
      <div class="modal-header">
        <h6 class="modal-title">Tolak Pengajuan</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id_pengajuan" value="<?= $d['id_pengajuan'] ?>">
        <input type="hidden" name="aksi" value="tolak">
        <label class="form-label">Alasan penolakan</label>
        <textarea name="catatan_admin" class="form-control" rows="3" placeholder="Contoh: berat tidak sesuai fisik sampah"></textarea>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-orange btn-sm">Tolak Pengajuan</button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
