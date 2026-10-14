<?php
// header.php mengasumsikan auth.php SUDAH di-include sebelumnya oleh halaman pemanggil
$u = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>Bank Sampah</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/bank_sampah_alan/assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand navbar-app px-3">
    <a class="navbar-brand" href="/bank_sampah_alan/index.php">
        <i class="bi bi-recycle"></i> Bank<span>Sampah</span>
    </a>
    <button class="btn btn-sm btn-outline-orange d-md-none me-2" id="sidebarToggle" type="button">
        <i class="bi bi-list"></i>
    </button>
    <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-muted small d-none d-sm-inline">
            <i class="bi bi-person-circle"></i>
            <?= htmlspecialchars($u['nama']) ?>
            <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($u['role']) ?></span>
        </span>
        <a href="/bank_sampah_alan/logout.php" class="btn btn-sm btn-outline-orange">
            <i class="bi bi-box-arrow-right"></i> Keluar
        </a>
    </div>
</nav>

<div class="d-flex">
