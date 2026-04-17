<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}
include(__DIR__ . '/../config/koneksi.php');

/* ================= PAGINATION ================= */
$batas = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $batas;

/* ================= TOTAL DATA ================= */
$totalData = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total FROM peminjaman"
))['total'];

$totalPage = ceil($totalData / $batas);

/* ================= QUERY DATA ================= */
$query = mysqli_query($koneksi, "
    SELECT peminjaman.*, aset.nama_aset 
    FROM peminjaman 
    JOIN aset ON peminjaman.id_aset = aset.id_aset 
    ORDER BY peminjaman.id_pinjam DESC
    LIMIT $offset, $batas
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Peminjaman Alat | SIMARIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
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
            padding: 40px 30px;
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* ===== PAGINATION POSISI KANAN ===== */
        .pagination-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">

            <div class="dashboard-header">
                <h3 class="fw-bold">PEMINJAMAN ALAT</h3>
                <div class="admin-info">
                    <i class="bi bi-person-circle"></i>
                    <span class="fw-bold"><?= $_SESSION['nama_pengguna']; ?></span>
                </div>
            </div>

            <div class="content">
                <div class="card shadow-sm">

                    <div class="card-header py-3">
                        <span>Daftar Transaksi Peminjaman</span>
                        <div class="d-flex gap-2">
                            <a href="peminjaman_tambah.php" class="btn btn-light btn-sm fw-bold">+ Tambah Pinjam</a>
                            <a href="peminjaman_cetak.php" class="btn btn-light btn-sm fw-bold">Cetak Laporan</a>
                        </div>
                    </div>

                    <div class="card-body">

                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Peminjam</th>
                                    <th>Nama Alat</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Tgl Kembali</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php $no = $offset + 1; ?>
                                <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama_peminjam']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                        <td><?= $row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '-' ?></td>
                                        <td>
                                            <span class="badge <?= $row['status_pinjam'] == 'Dipinjam' ? 'bg-warning' : 'bg-success' ?>">
                                                <?= $row['status_pinjam'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 justify-content-center">
                                                <?php if ($row['status_pinjam'] == 'Dipinjam'): ?>
                                                    <a href="peminjaman_kembali.php?id=<?= $row['id_pinjam'] ?>" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-arrow-return-left"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="peminjaman_edit.php?id=<?= $row['id_pinjam'] ?>" class="btn btn-warning btn-sm text-white">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="peminjaman_hapus.php?id=<?= $row['id_pinjam'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>

                        </table>

                        <!-- ================= PAGINATION ================= -->
                        <div class="pagination-wrapper">
                            <nav>
                                <ul class="pagination pagination-sm mb-0">

                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                                    </li>

                                    <?php for ($i = 1; $i <= $totalPage; $i++): ?>
                                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?= ($page >= $totalPage) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
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