-- =========================================================
-- Bank Sampah - Skema Database FINAL (hasil update bersama)
-- Perubahan dari file asli:
--   1. Tabel `user`.role diubah -> enum('admin','siswa','guru')
--   2. Tabel `user` ditambah kolom `keterangan` (kelas/NIP, opsional)
--   3. Tabel baru `pengajuan_setor` (siswa/guru ajukan setoran)
--   4. Tabel baru `setor_pengepul` (admin jual stok ke pengepul)
-- =========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Tabel `sampah` (tetap sama seperti asli)
-- --------------------------------------------------------
CREATE TABLE `sampah` (
  `id_sampah` int(11) NOT NULL AUTO_INCREMENT,
  `jenis_sampah` varchar(50) NOT NULL,
  `harga_per_kg` decimal(10,2) NOT NULL,
  `stok_kg` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id_sampah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabel `user` (role diperbarui: admin, siswa, guru)
-- --------------------------------------------------------
CREATE TABLE `user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','siswa','guru') NOT NULL DEFAULT 'siswa',
  `keterangan` varchar(100) DEFAULT NULL COMMENT 'Kelas untuk siswa, NIP untuk guru',
  `saldo` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabel BARU `pengajuan_setor`
-- Siswa/guru mengajukan setoran, menunggu verifikasi admin
-- --------------------------------------------------------
CREATE TABLE `pengajuan_setor` (
  `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_sampah` int(11) NOT NULL,
  `berat_kg` decimal(10,2) NOT NULL,
  `status` enum('pending','diterima','ditolak') NOT NULL DEFAULT 'pending',
  `catatan_admin` varchar(255) DEFAULT NULL,
  `tanggal_ajuan` datetime NOT NULL DEFAULT current_timestamp(),
  `tanggal_verifikasi` datetime DEFAULT NULL,
  PRIMARY KEY (`id_pengajuan`),
  KEY `id_user` (`id_user`),
  KEY `id_sampah` (`id_sampah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabel `transaksi` (tetap sama - catatan FINAL yang sudah disetujui)
-- --------------------------------------------------------
CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_sampah` int(11) NOT NULL,
  `id_pengajuan` int(11) DEFAULT NULL COMMENT 'Referensi ke pengajuan_setor jika ada',
  `tipe` enum('setor','ambil') NOT NULL DEFAULT 'setor',
  `berat_kg` decimal(10,2) NOT NULL,
  `total_rp` decimal(10,2) NOT NULL,
  `tanggal` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_transaksi`),
  KEY `id_user` (`id_user`),
  KEY `id_sampah` (`id_sampah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabel BARU `setor_pengepul`
-- Admin menjual stok sampah terkumpul ke pengepul
-- --------------------------------------------------------
CREATE TABLE `setor_pengepul` (
  `id_setor` int(11) NOT NULL AUTO_INCREMENT,
  `id_sampah` int(11) NOT NULL,
  `berat_kg` decimal(10,2) NOT NULL,
  `total_rp` decimal(12,2) NOT NULL,
  `nama_pengepul` varchar(100) NOT NULL,
  `tanggal` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_setor`),
  KEY `id_sampah` (`id_sampah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabel `tarik_saldo` (tetap sama seperti asli)
-- --------------------------------------------------------
CREATE TABLE `tarik_saldo` (
  `id_tarik` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `nominal` decimal(12,2) NOT NULL,
  `status` enum('pending','disetujui','ditolak') NOT NULL DEFAULT 'pending',
  `tanggal` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_tarik`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- FOREIGN KEYS
-- --------------------------------------------------------
ALTER TABLE `pengajuan_setor`
  ADD CONSTRAINT `pengajuan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pengajuan_ibfk_2` FOREIGN KEY (`id_sampah`) REFERENCES `sampah` (`id_sampah`);

ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`id_sampah`) REFERENCES `sampah` (`id_sampah`),
  ADD CONSTRAINT `transaksi_ibfk_3` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan_setor` (`id_pengajuan`) ON DELETE SET NULL;

ALTER TABLE `setor_pengepul`
  ADD CONSTRAINT `setor_pengepul_ibfk_1` FOREIGN KEY (`id_sampah`) REFERENCES `sampah` (`id_sampah`);

ALTER TABLE `tarik_saldo`
  ADD CONSTRAINT `tarik_saldo_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

-- --------------------------------------------------------
-- Data awal: 1 akun admin default (username: admin, password: admin123)
-- Password sudah di-hash dengan password_hash() PHP (bcrypt)
-- --------------------------------------------------------
INSERT INTO `user` (`nama`, `username`, `password`, `role`, `keterangan`, `saldo`) VALUES
('Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 0.00);
-- Catatan: hash di atas = "admin123". WAJIB diganti setelah login pertama.

-- Contoh jenis sampah awal (boleh dihapus/disesuaikan lewat halaman admin nanti)
INSERT INTO `sampah` (`jenis_sampah`, `harga_per_kg`, `stok_kg`) VALUES
('Botol Plastik', 3000.00, 0.00),
('Kardus', 2000.00, 0.00),
('Kertas', 1500.00, 0.00),
('Kaleng', 4000.00, 0.00);

COMMIT;
