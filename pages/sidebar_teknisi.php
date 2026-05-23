<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$nama_user = $_SESSION['nama_pengguna'] ?? '';

// Deteksi flexibel: apakah nama mengandung kata 'budi' atau 'ahmad'
$is_budi = (strpos(strtolower($nama_user), 'budi') !== false);
$is_ahmad = (strpos(strtolower($nama_user), 'ahmad') !== false);
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    #sidebar-wrapper {
        width: 260px;
        background: linear-gradient(180deg, #2c7a7b, #1cc88a);
        color: #fff;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        font-family: 'Inter', sans-serif;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05);
    }

    .sidebar-heading {
        padding: 2rem 1.5rem 1rem 1.5rem;
        font-size: 1.2rem;
        font-weight: 700;
        text-align: center;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .sidebar-logo {
        width: 95px;
        height: 95px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.5);
        background-color: #fff;
        padding: 2px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .menu-category {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: rgba(255, 255, 255, 0.6);
        padding: 1.2rem 15px 0.4rem 15px;
    }

    .list-group {
        padding: 0 10px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .list-group-item {
        background: transparent !important;
        color: rgba(255, 255, 255, 0.85) !important;
        border: none !important;
        margin: 2px 0;
        padding: 10px 14px;
        border-radius: 8px !important;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.2s ease-in-out;
    }

    .list-group-item:hover {
        background: rgba(255, 255, 255, 0.15) !important;
        color: #fff !important;
        padding-left: 20px;
    }

    .logout-item {
        margin-top: auto !important;
        margin-bottom: 15px;
        background: rgba(239, 68, 68, 0.15) !important;
        color: #ffa3a3 !important;
        border: 1px solid rgba(239, 68, 68, 0.3) !important;
        font-weight: 600;
    }

    .logout-item:hover {
        background: #ef4444 !important;
        color: #fff !important;
        padding-left: 20px;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
</style>

<div id="sidebar-wrapper">
    <div class="sidebar-heading">
        <img src="../assets/img/logo.png" alt="Logo Rumah Sakit" class="sidebar-logo">
        <span>SIMARIS RS</span>
    </div>

    <div class="list-group list-group-flush">
        <div class="menu-category">Menu Utama</div>
        <a href="dashboard_teknisi.php" class="list-group-item">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <?php if ($is_budi) : ?>
            <div class="menu-category">Manajemen Perbaikan</div>
            <a href="kerusakan.php" class="list-group-item">
                <i class="bi bi-exclamation-octagon"></i> Laporan Kerusakan
            </a>
        <?php endif; ?>

        <?php if ($is_ahmad) : ?>
            <div class="menu-category">Manajemen Pemeliharaan</div>
            <a href="perawatan.php" class="list-group-item">
                <i class="bi bi-tools"></i> Jadwal Perawatan/Pemiliharaan Dan Kalibrasi Rutin
            </a>
        <?php endif; ?>

        <a href="../logout.php" class="list-group-item logout-item" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>