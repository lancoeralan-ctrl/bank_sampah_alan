<?php
// sidebar.php mengasumsikan auth.php sudah di-include.
// Set variabel $active di halaman pemanggil untuk menandai menu yang sedang aktif.
$u = currentUser();
$active = $active ?? '';

function navlink($href, $icon, $label, $key, $active) {
    $cls = $active === $key ? 'active' : '';
    echo "<a class=\"$cls\" href=\"$href\"><i class=\"bi $icon\"></i> $label</a>";
}
?>
<div class="sidebar" style="width: 240px; flex-shrink:0;">
    <?php if ($u['role'] === 'admin'): ?>
        <?php navlink('/bank_sampah_alan/admin/dashboard.php', 'bi-speedometer2', 'Dashboard', 'dashboard', $active); ?>
        <?php navlink('/bank_sampah_alan/admin/verifikasi.php', 'bi-check2-square', 'Verifikasi Setoran', 'verifikasi', $active); ?>
        <?php navlink('/bank_sampah_alan/admin/kelola_sampah.php', 'bi-trash3', 'Kelola Jenis Sampah', 'kelola_sampah', $active); ?>
        <?php navlink('/bank_sampah_alan/admin/setor_pengepul.php', 'bi-truck', 'Setor ke Pengepul', 'setor_pengepul', $active); ?>
        <?php navlink('/bank_sampah_alan/admin/tarik_saldo.php', 'bi-cash-coin', 'Persetujuan Tarik Saldo', 'tarik_saldo', $active); ?>
        <?php navlink('/bank_sampah_alan/admin/kelola_user.php', 'bi-people', 'Kelola Pengguna', 'kelola_user', $active); ?>
    <?php else: ?>
        <?php navlink('/bank_sampah_alan/warga/dashboard.php', 'bi-speedometer2', 'Dashboard', 'dashboard', $active); ?>
        <?php navlink('/bank_sampah_alan/warga/ajukan_setoran.php', 'bi-recycle', 'Ajukan Setoran Sampah', 'ajukan_setoran', $active); ?>
        <?php navlink('/bank_sampah_alan/warga/riwayat.php', 'bi-clock-history', 'Riwayat Setoran', 'riwayat', $active); ?>
        <?php navlink('/bank_sampah_alan/warga/tarik_saldo.php', 'bi-wallet2', 'Tarik Saldo', 'tarik_saldo', $active); ?>
    <?php endif; ?>
</div>
<div class="flex-grow-1 p-4">
