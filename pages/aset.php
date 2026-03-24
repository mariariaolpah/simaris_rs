<?php
session_start();

// === FIX SESSION LEVEL BIAR GA ERROR ===
if (!isset($_SESSION['level']) && isset($_SESSION['role'])) {
    $_SESSION['level'] = $_SESSION['role'];
}
if (!isset($_SESSION['level'])) {
    $_SESSION['level'] = 'user';
}
// =======================================

if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// Ambil data aset dari database
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
if ($search != '') {
    $query = mysqli_query($koneksi, "SELECT * FROM aset 
        WHERE nama_aset LIKE '%$search%' 
        OR jenis LIKE '%$search%' 
        OR lokasi LIKE '%$search%' 
        OR kondisi LIKE '%$search%' 
        ORDER BY id_aset DESC");
} else {
    $query = mysqli_query($koneksi, "SELECT * FROM aset ORDER BY id_aset DESC");
}

$aset_list = [];
while ($row = mysqli_fetch_assoc($query)) {
    $aset_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Aset / Infrastruktur | SIMARIS RS Bhayangkara</title>
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
        }

        .dashboard-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        table.table-hover tbody tr:hover {
            background-color: #d1f0eb;
        }

        table td,
        table th {
            vertical-align: middle !important;
        }

        .btn-action {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h3 class="fw-bold">ASET / INFRASTRUKTUR</h3>

                <div class="admin-info" style="display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-person-circle"></i>
                    <span class="fw-bold"><?= $_SESSION['nama_pengguna']; ?> (<?= $_SESSION['level']; ?>)</span>
                </div>

            </div>

            <div class="content">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Data Aset</span>
                        <div class="d-flex gap-2 align-items-center">

                            <form method="GET" class="d-flex gap-2 mb-0">
                                <input type="text" name="search" class="form-control form-control-sm"
                                    placeholder="Cari aset..."
                                    value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                                    style="height:38px; font-size:14px;">
                                <button type="submit" class="btn btn-secondary" style="height:38px; font-size:14px;">
                                    <i class="bi bi-search"></i>
                                </button>
                            </form>

                            <a href="aset_tambah.php" class="btn btn-light fw-bold" style="height:38px; font-size:14px;">
                                <i class="bi bi-plus-circle"></i> Tambah Aset
                            </a>
                            <a href="aset_cetak.php<?= isset($_GET['search']) ? '?search=' . urlencode($_GET['search']) : '' ?>" class="btn btn-light fw-bold" style="height:38px; font-size:14px;">
                                <i class="bi bi-printer"></i> Cetak PDF
                            </a>
                        </div>
                    </div>

                    <div class="card-body" style="overflow-x: auto;">
                        <table class="table table-bordered table-hover text-center align-middle" style="white-space: nowrap;">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Aset</th>
                                    <th>Jenis</th>
                                    <th>Tipe Aset</th>
                                    <th>Lokasi</th>
                                    <th>Kondisi</th>
                                    <th>Asal-Usul</th>
                                    <th>Harga (Rp)</th>
                                    <th>Tanggal Masuk</th>
                                    <th>Dokumen</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($aset_list)): ?>
                                    <tr>
                                        <td colspan="11">Belum ada data aset.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($aset_list as $i => $a): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= htmlspecialchars($a['nama_aset']) ?></td>
                                            <td><?= htmlspecialchars($a['jenis']) ?></td>
                                            <td><?= htmlspecialchars($a['tipe_aset']) ?></td>
                                            <td><?= htmlspecialchars($a['lokasi']) ?></td>
                                            <td><?= htmlspecialchars($a['kondisi']) ?></td>
                                            <td><?= htmlspecialchars($a['asal_usul']) ?></td>
                                            <td>Rp <?= number_format($a['harga'], 0, ',', '.') ?></td>
                                            <td><?= htmlspecialchars($a['tanggal_masuk']) ?></td>

                                            <td>
                                                <?php if (!empty($a['dokumen'])): ?>
                                                    <a href="../assets/dokumen/<?= htmlspecialchars($a['dokumen']) ?>" target="_blank" class="btn btn-info btn-sm text-white">
                                                        <i class="bi bi-file-earmark-text"></i> Lihat
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="btn-action">
                                                <a href="aset_edit.php?id=<?= $a['id_aset'] ?>" class="btn btn-warning btn-sm">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="aset_hapus.php?id=<?= $a['id_aset'] ?>" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Yakin ingin menghapus data ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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