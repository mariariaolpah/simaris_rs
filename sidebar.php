<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? '';
?>

<div id="sidebar-wrapper">
    <div class="sidebar-heading">SIMARIS RS BHAYANGKARA</div>
    <div class="list-group list-group-flush">

        <a href="dashboard.php" class="list-group-item">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <a href="aset.php" class="list-group-item">
            <i class="bi bi-box-seam"></i> Aset / Infrastruktur
        </a>

        <a href="kerusakan.php" class="list-group-item">
            <i class="bi bi-exclamation-triangle"></i> Kerusakan
        </a>

        <a href="perawatan.php" class="list-group-item">
            <i class="bi bi-tools"></i> Perawatan / Pemeliharaan
        </a>

        <?php if ($role === 'admin') : ?>
            <a href="user.php" class="list-group-item">
                <i class="bi bi-people"></i> Manajemen User
            </a>

            <a href="laporan.php" class="list-group-item">
                <i class="bi bi-file-earmark-text"></i> Laporan
            </a>
        <?php endif; ?>

        <a href="/simaris_rs/logout.php" class="list-group-item text-danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>