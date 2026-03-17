<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Perbaikan: Ambil dari 'level' atau 'role' agar lebih aman
$role = $_SESSION['level'] ?? $_SESSION['role'] ?? '';
?>

<style>
    #sidebar-wrapper {
        width: 230px;
        background: linear-gradient(180deg, #2c7a7b, #1cc88a);
        color: #fff;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .sidebar-heading {
        padding: 1.5rem 1rem;
        font-size: 1.1rem;
        font-weight: 700;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.3);
    }

    .list-group-item {
        background: transparent !important;
        color: #fff !important;
        border: none;
        padding: 12px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }

    .list-group-item:hover {
        background-color: rgba(255, 255, 255, 0.15) !important;
    }
</style>

<div id="sidebar-wrapper">
    <div class="sidebar-heading">SIMARIS RS BHAYANGKARA</div>
    <div class="list-group list-group-flush">
        <a href="dashboard.php" class="list-group-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="aset.php" class="list-group-item"><i class="bi bi-box-seam"></i> Aset / Infrastruktur</a>
        <a href="peminjaman.php" class="list-group-item"><i class="bi bi-arrow-left-right"></i> Peminjaman Alat</a>
        <a href="audit_fisik.php" class="list-group-item"><i class="bi bi-clipboard-check"></i> Audit Fisik</a>
        <a href="kerusakan.php" class="list-group-item"><i class="bi bi-exclamation-triangle"></i> Kerusakan</a>
        <a href="perawatan.php" class="list-group-item"><i class="bi bi-tools"></i> Perawatan / Pemeliharaan</a>

        <?php if ($role === 'admin') : ?>
            <a href="user.php" class="list-group-item"><i class="bi bi-people"></i> Manajemen User</a>
            <a href="laporan.php" class="list-group-item"><i class="bi bi-file-earmark-text"></i> Laporan</a>
        <?php endif; ?>

        <a href="../logout.php" class="list-group-item text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</div>