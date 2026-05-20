<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Ambil dari 'level' atau 'role' sesuai session sistem
$role = $_SESSION['level'] ?? $_SESSION['role'] ?? '';

// Hitung jumlah pengajuan yang berstatus 'Menunggu Persetujuan'
if (!isset($koneksi)) {
    include(__DIR__ . '/config/koneksi.php');
}
$q_notif_sidebar = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM peminjaman WHERE status_pinjam = 'Menunggu Persetujuan'");
$data_notif_sidebar = mysqli_fetch_assoc($q_notif_sidebar);
$jumlah_pending = $data_notif_sidebar['total'] ?? 0;
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    #sidebar-wrapper {
        width: 260px;
        background: linear-gradient(180deg, #2c7a7b, #1cc88a);
        /* Gradasi warna asli tetap dipertahankan */
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

    /* Style untuk Logo Rumah Sakit Tempat Magang */
    .sidebar-logo {
        width: 95px;
        /* <-- Ubah di sini jadi 95px atau 100px */
        height: 95px;
        /* <-- Ubah di sini juga biar tetap bulat presisi */
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.5);
        background-color: #fff;
        padding: 2px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Pengelompokan Kategori Menu */
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
        /* Membuat pendorong agar tombol logout bisa otomatis ke bawah */
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
        justify-content: space-between;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.2s ease-in-out;
    }

    /* Efek hover modern bergeser sedikit ke kanan */
    .list-group-item:hover {
        background: rgba(255, 255, 255, 0.15) !important;
        color: #fff !important;
        padding-left: 20px;
    }

    .list-group-item div {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Badge Bulat Merah Cantik untuk Notifikasi */
    .badge-notif {
        background-color: #ef4444 !important;
        color: white !important;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 3px 7px !important;
        border-radius: 50rem !important;
        box-shadow: 0 2px 5px rgba(239, 68, 68, 0.4);
    }

    /* Style Khusus Tombol Logout Merah Modern di Paling Bawah */
    .logout-item {
        margin-top: auto !important;
        /* Mendorong tombol otomatis ke dasar sidebar */
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
        <div class="menu-category">Utama</div>
        <a href="dashboard.php" class="list-group-item">
            <div><i class="bi bi-speedometer2"></i> Dashboard</div>
        </a>

        <div class="menu-category">Manajemen Aset</div>
        <a href="aset.php" class="list-group-item">
            <div><i class="bi bi-box-seam"></i> Aset / Infrastruktur</div>
        </a>
        <a href="aset_pindah.php" class="list-group-item">
            <div><i class="bi bi-geo-alt"></i> Pelacakan Lokasi Aset</div>
        </a>

        <div class="menu-category">Aktivitas & Operasional</div>
        <a href="peminjaman.php" class="list-group-item">
            <div><i class="bi bi-arrow-left-right"></i> Peminjaman Alat</div> <?php if ($jumlah_pending > 0): ?>
                <span class="badge badge-notif"><?= $jumlah_pending ?></span>
            <?php endif; ?>
        </a>
        <a href="audit_fisik.php" class="list-group-item">
            <div><i class="bi bi-clipboard-check"></i> Audit Fisik</div>
        </a>
        <a href="kerusakan.php" class="list-group-item">
            <div><i class="bi bi-exclamation-triangle"></i> Kerusakan</div>
        </a>
        <a href="perawatan.php" class="list-group-item">
            <div><i class="bi bi-tools"></i> Perawatan / Pemeliharaan</div>
        </a>

        <?php if ($role === 'admin') : ?>
            <div class="menu-category">Pengaturan Sistem</div>
            <a href="user.php" class="list-group-item">
                <div><i class="bi bi-people"></i> Manajemen User</div>
            </a>
            <a href="laporan.php" class="list-group-item">
                <div><i class="bi bi-file-earmark-bar-graph"></i> Laporan</div>
            </a>
        <?php endif; ?>

        <a href="../logout.php" class="list-group-item logout-item" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem SIMARIS?')">
            <div><i class="bi bi-box-arrow-right"></i> Logout</div>
        </a>
    </div>
</div>