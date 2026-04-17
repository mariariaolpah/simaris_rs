<?php
session_start();

if (!isset($_SESSION['level']) && isset($_SESSION['role'])) {
    $_SESSION['level'] = $_SESSION['role'];
}
if (!isset($_SESSION['level'])) {
    $_SESSION['level'] = 'user';
}

if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// ================= FORMAT TANGGAL =================
function formatTanggal($tanggal)
{
    if (!$tanggal || $tanggal == '0000-00-00') {
        return '-';
    }
    return date('d-m-Y', strtotime($tanggal));
}

// ================= PAGINATION =================
$limit = 8;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : "";

if ($search != '') {

    $query = mysqli_query($koneksi, "SELECT * FROM aset 
        WHERE nama_aset LIKE '%$search%' 
        OR jenis LIKE '%$search%' 
        OR lokasi LIKE '%$search%' 
        OR kondisi LIKE '%$search%' 
        ORDER BY id_aset DESC
        LIMIT $limit OFFSET $offset
    ");

    $count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM aset 
        WHERE nama_aset LIKE '%$search%' 
        OR jenis LIKE '%$search%' 
        OR lokasi LIKE '%$search%' 
        OR kondisi LIKE '%$search%'
    ");
} else {

    $query = mysqli_query($koneksi, "SELECT * FROM aset 
        ORDER BY id_aset DESC
        LIMIT $limit OFFSET $offset
    ");

    $count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM aset");
}

$total_row = mysqli_fetch_assoc($count);
$total_data = $total_row['total'];
$total_page = ceil($total_data / $limit);

$aset_list = [];
while ($row = mysqli_fetch_assoc($query)) {
    $aset_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Aset / Infrastruktur</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
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
            padding: 30px;
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        table td,
        table th {
            vertical-align: middle !important;
        }

        .btn-action {
            display: flex;
            gap: 6px;
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
                <div>
                    <i class="bi bi-person-circle"></i>
                    <?= $_SESSION['nama_pengguna']; ?> (<?= $_SESSION['level']; ?>)
                </div>
            </div>

            <div class="content">

                <div class="card">

                    <div class="card-header">
                        <span>Data Aset</span>

                        <div class="d-flex gap-2">

                            <form method="GET" class="d-flex gap-2">
                                <input type="text" name="search" class="form-control form-control-sm"
                                    placeholder="Cari aset..."
                                    value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-light btn-sm">
                                    <i class="bi bi-search"></i>
                                </button>
                            </form>

                            <a href="aset_tambah.php" class="btn btn-light btn-sm">+ Tambah</a>

                            <!-- CETAK PDF SIMPLE (TANPA WARNA / ICON) -->
                            <a href="aset_cetak.php<?= $search ? '?search=' . urlencode($search) : '' ?>"
                                target="_blank"
                                class="btn btn-light btn-sm">
                                Cetak PDF
                            </a>

                        </div>
                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover text-center">

                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Aset</th>
                                        <th>Jenis</th>
                                        <th>Tipe</th>
                                        <th>Lokasi</th>
                                        <th>Kondisi</th>
                                        <th>Asal</th>
                                        <th>Harga</th>
                                        <th>Tanggal</th>
                                        <th>Dokumen</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($aset_list as $i => $a): ?>
                                        <tr>
                                            <td><?= (($page - 1) * $limit) + $i + 1 ?></td>
                                            <td><?= $a['nama_aset'] ?></td>
                                            <td><?= $a['jenis'] ?></td>
                                            <td><?= $a['tipe_aset'] ?></td>
                                            <td><?= $a['lokasi'] ?></td>
                                            <td><?= $a['kondisi'] ?></td>
                                            <td><?= $a['asal_usul'] ?></td>
                                            <td>Rp <?= number_format($a['harga'], 0, ',', '.') ?></td>

                                            <td><?= formatTanggal($a['tanggal_masuk']) ?></td>

                                            <td>
                                                <?php if ($a['dokumen']): ?>
                                                    <a href="../assets/dokumen/<?= $a['dokumen'] ?>"
                                                        class="btn btn-info btn-sm text-white"
                                                        target="_blank">
                                                        Lihat
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>

                                            <td class="btn-action">
                                                <a href="aset_edit.php?id=<?= $a['id_aset'] ?>" class="btn btn-warning btn-sm">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="aset_hapus.php?id=<?= $a['id_aset'] ?>" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>

                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <ul class="pagination pagination-sm">

                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                        href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                        Prev
                                    </a>
                                </li>

                                <li class="page-item disabled">
                                    <span class="page-link"><?= $page ?> / <?= $total_page ?></span>
                                </li>

                                <li class="page-item <?= ($page >= $total_page) ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                        href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                        Next
                                    </a>
                                </li>

                            </ul>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>

</html>