<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('admin');

$page_title = 'Kelola Pengguna';
$active = 'kelola_user';
$pesan = '';
$pesan_tipe = 'success';
$edit_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $role = $_POST['role'] ?? 'siswa';
        $keterangan = trim($_POST['keterangan'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($nama === '' || $username === '' || $password === '') {
            $pesan = 'Nama, username, dan password wajib diisi.';
            $pesan_tipe = 'danger';
        } else {
            $cek = $pdo->prepare("SELECT id_user FROM user WHERE username = ?");
            $cek->execute([$username]);
            if ($cek->fetch()) {
                $pesan = 'Username sudah dipakai, pilih yang lain.';
                $pesan_tipe = 'danger';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO user (nama, username, password, role, keterangan, saldo) VALUES (?, ?, ?, ?, ?, 0)")
                    ->execute([$nama, $username, $hash, $role, $keterangan ?: null]);
                $pesan = "Akun \"$nama\" berhasil dibuat.";
            }
        }
    } elseif ($aksi === 'update') {
        $id = (int) ($_POST['id_user'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $role = $_POST['role'] ?? 'siswa';
        $keterangan = trim($_POST['keterangan'] ?? '');
        $pdo->prepare("UPDATE user SET nama = ?, role = ?, keterangan = ? WHERE id_user = ? AND role != 'admin'")
            ->execute([$nama, $role, $keterangan ?: null, $id]);
        $pesan = 'Data pengguna berhasil diperbarui.';
    } elseif ($aksi === 'reset_password') {
        $id = (int) ($_POST['id_user'] ?? 0);
        $hash = password_hash('siswa123', PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE user SET password = ? WHERE id_user = ? AND role != 'admin'")->execute([$hash, $id]);
        $pesan = 'Password direset ke default: siswa123. Sampaikan ke pengguna untuk menggantinya.';
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id_user'] ?? 0);
        try {
            $pdo->prepare("DELETE FROM user WHERE id_user = ? AND role != 'admin'")->execute([$id]);
            $pesan = 'Akun berhasil dihapus.';
        } catch (\PDOException $e) {
            $pesan = 'Tidak bisa dihapus, kemungkinan masih ada data terkait.';
            $pesan_tipe = 'danger';
        }
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = ? AND role != 'admin'");
    $stmt->execute([(int) $_GET['edit']]);
    $edit_data = $stmt->fetch();
}

$daftar = $pdo->query("SELECT * FROM user WHERE role IN ('siswa','guru') ORDER BY nama")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<h4 class="mb-1"><i class="bi bi-people"></i> Kelola Pengguna</h4>
<p class="text-muted mb-4">Kelola akun siswa dan guru yang terdaftar sebagai nasabah bank sampah.</p>

<?php if ($pesan): ?>
    <div class="alert alert-<?= $pesan_tipe ?> py-2"><?= htmlspecialchars($pesan) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card p-3">
            <h6 class="mb-3"><?= $edit_data ? 'Edit Pengguna' : 'Tambah Pengguna Baru' ?></h6>
            <form method="POST">
                <input type="hidden" name="aksi" value="<?= $edit_data ? 'update' : 'tambah' ?>">
                <?php if ($edit_data): ?><input type="hidden" name="id_user" value="<?= $edit_data['id_user'] ?>"><?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($edit_data['nama'] ?? '') ?>">
                </div>

                <?php if (!$edit_data): ?>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password Awal</label>
                    <input type="text" name="password" class="form-control" required placeholder="Contoh: siswa123">
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Peran</label>
                    <select name="role" class="form-select">
                        <option value="siswa" <?= ($edit_data['role'] ?? '') === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                        <option value="guru" <?= ($edit_data['role'] ?? '') === 'guru' ? 'selected' : '' ?>>Guru</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kelas / NIP (opsional)</label>
                    <input type="text" name="keterangan" class="form-control" value="<?= htmlspecialchars($edit_data['keterangan'] ?? '') ?>">
                </div>

                <button type="submit" class="btn btn-orange w-100"><i class="bi bi-save"></i> <?= $edit_data ? 'Simpan Perubahan' : 'Tambah Pengguna' ?></button>
                <?php if ($edit_data): ?>
                    <a href="/bank_sampah_alan/admin/kelola_user.php" class="btn btn-outline-orange w-100 mt-2">Batal</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card p-3">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Nama</th><th>Username</th><th>Peran</th><th>Keterangan</th><th>Saldo</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($daftar as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td class="text-uppercase"><?= htmlspecialchars($row['role']) ?></td>
                            <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                            <td><?= rupiah($row['saldo']) ?></td>
                            <td class="text-end">
                                <a href="?edit=<?= $row['id_user'] ?>" class="btn btn-sm btn-outline-orange"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Reset password ke siswa123?')">
                                    <input type="hidden" name="aksi" value="reset_password">
                                    <input type="hidden" name="id_user" value="<?= $row['id_user'] ?>">
                                    <button class="btn btn-sm btn-outline-orange"><i class="bi bi-key"></i></button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus akun ini? Semua riwayatnya juga akan terhapus.')">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id_user" value="<?= $row['id_user'] ?>">
                                    <button class="btn btn-sm btn-outline-orange"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($daftar)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada siswa/guru terdaftar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
