<div id="sidebar-wrapper">
    <div class="sidebar-heading">
        SIMARIS RS BHAYANGKARA
    </div>

    <div class="list-group list-group-flush">

        <a href="dashboard_user.php" class="list-group-item">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>

        <a href="user_data_aset.php" class="list-group-item">
            <i class="bi bi-box me-2"></i> Data Aset
        </a>

        <a href="user_ajukan_peminjaman.php" class="list-group-item">
            <i class="bi bi-cart-plus me-2"></i> Ajukan Peminjaman
        </a>
        <a href="user_data_kerusakan.php" class="list-group-item">
            <i class="bi bi-file-earmark-text me-2"></i> Laporan Kerusakan
        </a>

        <a href="user_tambah_kerusakan.php" class="list-group-item">
            <i class="bi bi-pencil-square me-2"></i> Buat Laporan Kerusakan
        </a>

        <a href="../logout.php" class="list-group-item text-danger">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>

    </div>
</div>

<style>
    #sidebar-wrapper {
        width: 230px;
        background: linear-gradient(180deg, #2c7a7b, #1cc88a);
        border-right: 1px solid rgba(255, 255, 255, 0.3);
        min-height: 100vh;
        position: fixed;
        padding-top: 5px;
    }

    #sidebar-wrapper .sidebar-heading {
        text-align: center;
        font-weight: bold;
        padding: 1rem 0;
        color: #fff;
        border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        font-size: 1.1rem;
    }

    #sidebar-wrapper .list-group-item {
        color: #fff;
        background: transparent;
        border: none;
        padding: 14px 20px;
        font-size: 15px;
        font-weight: 500;
    }

    #sidebar-wrapper .list-group-item:hover {
        background: rgba(0, 0, 0, 0.20);
        color: #fff;
    }

    #sidebar-wrapper .list-group-item.text-danger:hover {
        color: #ff4d4d !important;
    }
</style>