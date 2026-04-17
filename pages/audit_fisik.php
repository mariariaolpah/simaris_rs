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
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : "";

// QUERY DATA
if ($search != '') {
    $query = mysqli_query($koneksi, "SELECT audit_fisik.*, aset.nama_aset FROM audit_fisik 
        JOIN aset ON audit_fisik.id_aset = aset.id_aset
        WHERE aset.nama_aset LIKE '%$search%' 
        OR audit_fisik.auditor LIKE '%$search%' 
        OR audit_fisik.keterangan LIKE '%$search%'
        ORDER BY id_audit DESC
        LIMIT $limit OFFSET $offset
    ");

    $count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM audit_fisik 
        JOIN aset ON audit_fisik.id_aset = aset.id_aset
        WHERE aset.nama_aset LIKE '%$search%' 
        OR audit_fisik.auditor LIKE '%$search%' 
        OR audit_fisik.keterangan LIKE '%$search%'
    ");
} else {
    $query = mysqli_query($koneksi, "SELECT audit_fisik.*, aset.nama_aset FROM audit_fisik 
        JOIN aset ON audit_fisik.id_aset = aset.id_aset
        ORDER BY id_audit DESC
        LIMIT $limit OFFSET $offset
    ");

    $count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM audit_fisik");
}

$total_row = mysqli_fetch_assoc($count);
$total_data = $total_row['total'];
$total_page = ceil($total_data / $limit);

// DATA ARRAY
$audit_list = [];
while ($row = mysqli_fetch_assoc($query)) {
    $audit_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Audit Fisik | SIMARIS RS Bhayangkara</title>
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
            padding: 40px 30px;
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
                <h3>AUDIT FISIK ASET</h3>
                <div>
                    <i class="bi bi-person-circle"></i>
                    <?= $_SESSION['nama_pengguna']; ?>
                </div>
            </div>

            <div class="content">

                <div class="card">

                    <div class="card-header">

                        <span>Riwayat Audit Fisik</span>

                        <div class="d-flex gap-2">

                            <form method="GET" class="d-flex gap-2">
                                <input type="text" name="search" class="form-control form-control-sm"
                                    value="<?= htmlspecialchars($search) ?>"
                                    placeholder="Cari...">
                                <button class="btn btn-secondary btn-sm">
                                    <i class="bi bi-search"></i>
                                </button>
                            </form>

                            <a href="audit_tambah.php" class="btn btn-light btn-sm">
                                + Tambah
                            </a>

                            <a href="audit_cetak.php" class="btn btn-light btn-sm">
                                Cetak
                            </a>

                        </div>

                    </div>

                    <div class="card-body table-responsive">

                        <table class="table table-bordered table-hover text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Aset</th>
                                    <th>Tanggal</th>
                                    <th>Kondisi</th>
                                    <th>Auditor</th>
                                    <th>Keterangan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($audit_list)): ?>
                                    <tr>
                                        <td colspan="7">Tidak ada data</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($audit_list as $i => $row): ?>
                                        <tr>
                                            <td><?= (($page - 1) * $limit) + $i + 1 ?></td>
                                            <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($row['tanggal_audit'])) ?></td>
                                            <td><?= $row['kondisi_fisik'] ?></td>
                                            <td><?= htmlspecialchars($row['auditor']) ?></td>
                                            <td><?= htmlspecialchars($row['keterangan']) ?></td>

                                            <td class="btn-action">
                                                <a href="audit_edit.php?id=<?= $row['id_audit'] ?>" class="btn btn-warning btn-sm">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="audit_hapus.php?id=<?= $row['id_audit'] ?>" class="btn btn-danger btn-sm">
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