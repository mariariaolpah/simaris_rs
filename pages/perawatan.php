<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// Search
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

$sql = "SELECT * FROM perawatan";
if ($search != '') {
    $sql .= " WHERE nama_aset LIKE '%$search%' OR teknisi LIKE '%$search%' OR status LIKE '%$search%'";
}
$sql .= " ORDER BY id DESC";

$result = mysqli_query($koneksi, $sql);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Perawatan / Pemeliharaan | SIMARIS RS Bhayangkara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
            margin: 0;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        #sidebar-wrapper {
            width: 220px;
            background: linear-gradient(180deg, #2c7a7b, #1cc88a);
            color: #fff;
            display: flex;
            flex-direction: column;
        }

        .sidebar-heading {
            padding: 1.5rem 1rem;
            font-size: 1.2rem;
            font-weight: 700;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, .3);
        }

        .list-group-item {
            background: transparent;
            color: #fff;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, .1);
            cursor: pointer;
        }

        .list-group-item:hover {
            background-color: rgba(255, 255, 255, .15);
        }

        .list-group-item.active {
            background-color: rgba(255, 255, 255, .25);
            font-weight: bold;
        }

        #page-content-wrapper {
            flex: 1;
            padding: 0;
        }

        .dashboard-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #fff;
        }

        .dashboard-header h3 {
            margin: 0;
            font-weight: 700;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 1rem;
        }

        .content {
            padding: 40px 30px 50px 30px;
        }

        .card-header {
            font-weight: 600;
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        table.table-bordered {
            border: 1px solid #d1f0eb;
        }

        table.table-hover tbody tr:hover {
            background-color: #d1f0eb;
        }

        table th,
        table td {
            vertical-align: middle !important;
        }

        .btn-action {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-custom {
            background-color: #ffffff;
            color: #333;
            font-weight: 600;
            height: 38px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 0 12px;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h3>PERAWATAN / PEMELIHARAAN</h3>
                <div class="admin-info">
                    <i class="bi bi-person-circle"></i>
                    <span><?= $_SESSION['nama_pengguna']; ?> (<?= $_SESSION['level']; ?>)</span>
                </div>
            </div>

            <div class="content">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Data Perawatan / Pemeliharaan</span>
                        <div class="d-flex gap-2 align-items-center">
                            <!-- Form Pencarian -->
                            <form method="GET" class="d-flex gap-2 mb-0">
                                <input type="hidden" name="page" value="perawatan">
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari perawatan..." value="<?= htmlspecialchars($search) ?>" style="height:38px; font-size:14px;">
                                <button type="submit" class="btn btn-secondary" style="height:38px; font-size:14px;">
                                    <i class="bi bi-search"></i>
                                </button>
                            </form>

                            <!-- Tombol Tambah & Cetak -->
                            <a href="perawatan_tambah.php" class="btn btn-custom">
                                <i class="bi bi-plus-circle"></i> Tambah Perawatan
                            </a>
                            <a href="perawatan_cetak.php<?= $search ? '?search=' . urlencode($search) : '' ?>" class="btn btn-custom">
                                <i class="bi bi-printer"></i> Cetak PDF
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Aset</th>
                                    <th>Teknisi</th>
                                    <th>Tanggal Perawatan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) == 0): ?>
                                    <tr>
                                        <td colspan="6">Belum ada data perawatan.</td>
                                    </tr>
                                    <?php else: $i = 1;
                                    while ($p = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($p['nama_aset']) ?></td>
                                            <td><?= htmlspecialchars($p['teknisi']) ?></td>
                                            <td><?= htmlspecialchars($p['tanggal']) ?></td>
                                            <td>
                                                <?php if ($p['status'] == 'Selesai'): ?>
                                                    <span class="badge bg-success">Selesai</span>
                                                <?php elseif ($p['status'] == 'Sedang Proses'): ?>
                                                    <span class="badge bg-warning text-dark">Sedang Proses</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Belum Dimulai</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="btn-action">
                                                <a href="perawatan_edit.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i></a>
                                                <a href="perawatan_hapus.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data ini?')"><i class="bi bi-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>

</html>