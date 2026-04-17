<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');


// ================= PAGINATION ================= //
$limit = 8;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

// SEARCH
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $search = $_GET['search']) : "";

// QUERY DATA
if ($search != '') {

    $query = mysqli_query($koneksi, "SELECT * FROM perawatan 
        WHERE nama_aset LIKE '%$search%' 
        OR teknisi LIKE '%$search%' 
        OR status LIKE '%$search%'
        ORDER BY id DESC
        LIMIT $limit OFFSET $offset
    ");

    $count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM perawatan 
        WHERE nama_aset LIKE '%$search%' 
        OR teknisi LIKE '%$search%' 
        OR status LIKE '%$search%'
    ");
} else {

    $query = mysqli_query($koneksi, "SELECT * FROM perawatan 
        ORDER BY id DESC
        LIMIT $limit OFFSET $offset
    ");

    $count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM perawatan");
}

$total_row = mysqli_fetch_assoc($count);
$total_data = $total_row['total'];
$total_page = ceil($total_data / $limit);

// DATA ARRAY
$perawatan_list = [];
while ($row = mysqli_fetch_assoc($query)) {
    $perawatan_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Perawatan | SIMARIS RS Bhayangkara</title>
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

        #page-content-wrapper {
            flex: 1;
            width: 100%;
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

        table th,
        table td {
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
                <h3>PERAWATAN / PEMELIHARAAN</h3>
                <div>
                    <i class="bi bi-person-circle"></i>
                    <?= $_SESSION['nama_pengguna']; ?>
                </div>
            </div>

            <div class="content">

                <div class="card">

                    <div class="card-header">

                        <span>Data Perawatan</span>

                        <div class="d-flex gap-2">

                            <form method="GET" class="d-flex gap-2">
                                <input type="text" name="search" class="form-control form-control-sm"
                                    value="<?= htmlspecialchars($search) ?>"
                                    placeholder="Cari...">
                                <button class="btn btn-secondary btn-sm">
                                    <i class="bi bi-search"></i>
                                </button>
                            </form>

                            <a href="perawatan_tambah.php" class="btn btn-light btn-sm">
                                + Tambah
                            </a>

                            <a href="perawatan_cetak.php<?= $search ? '?search=' . urlencode($search) : '' ?>"
                                class="btn btn-light btn-sm">
                                Cetak PDF
                            </a>

                        </div>

                    </div>

                    <div class="card-body table-responsive">

                        <table class="table table-bordered table-hover text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Aset</th>
                                    <th>Teknisi</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if (empty($perawatan_list)): ?>
                                    <tr>
                                        <td colspan="6">Tidak ada data</td>
                                    </tr>
                                <?php else: ?>

                                    <?php foreach ($perawatan_list as $i => $p): ?>
                                        <tr>
                                            <td><?= (($page - 1) * $limit) + $i + 1 ?></td>
                                            <td><?= htmlspecialchars($p['nama_aset']) ?></td>
                                            <td><?= htmlspecialchars($p['teknisi']) ?></td>
                                            <td><?= htmlspecialchars($p['tanggal']) ?></td>
                                            <td>
                                                <?php if ($p['status'] == 'Selesai'): ?>
                                                    <span class="badge bg-success">Selesai</span>
                                                <?php elseif ($p['status'] == 'Sedang Proses'): ?>
                                                    <span class="badge bg-warning text-dark">Proses</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Belum</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="btn-action">
                                                <a href="perawatan_edit.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="perawatan_hapus.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>
                        </table>

                        <!-- PAGINATION kanan bawah -->
                        <div class="d-flex justify-content-end mt-3">
                            <nav>
                                <ul class="pagination pagination-sm mb-0">

                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                            Prev
                                        </a>
                                    </li>

                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <?= $page ?> / <?= $total_page ?>
                                        </span>
                                    </li>

                                    <li class="page-item <?= ($page >= $total_page) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                                            Next
                                        </a>
                                    </li>

                                </ul>
                            </nav>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>

</html>