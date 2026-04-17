<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// ================= FIX SESSION ================= //
if (($_SESSION['role'] ?? '') != 'admin') {
    die("Akses ditolak.");
}

// ================= PAGINATION (FIX UNIK) ================= //
$limit = 8;

$page = isset($_GET['page_user']) ? (int)$_GET['page_user'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

// ================= SEARCH & FILTER ================= //
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$filter_level = isset($_GET['level']) ? mysqli_real_escape_string($koneksi, $_GET['level']) : '';

$sql = "SELECT * FROM pengguna";
$where = [];

if ($search != '') {
    $where[] = "(nama_pengguna LIKE '%$search%' 
                OR username LIKE '%$search%' 
                OR level LIKE '%$search%' 
                OR role LIKE '%$search%')";
}

if ($filter_level != '') {
    $where[] = "level='$filter_level'";
}

if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql_count = "SELECT COUNT(*) as total FROM pengguna";
if ($where) {
    $sql_count .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY id_pengguna DESC LIMIT $limit OFFSET $offset";

$result = mysqli_query($koneksi, $sql);
$count = mysqli_query($koneksi, $sql_count);

$total_row = mysqli_fetch_assoc($count);
$total_data = $total_row['total'];
$total_page = ceil($total_data / $limit);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manajemen User</title>

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
                <h3>MANAJEMEN USER</h3>
                <div>
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>
                </div>
            </div>

            <div class="content">

                <div class="card">

                    <div class="card-header">

                        <span>Daftar User</span>

                        <div class="d-flex gap-2">

                            <form method="GET" class="d-flex gap-2">

                                <!-- FIX PAGE USER -->
                                <input type="hidden" name="page_user" value="1">

                                <input type="text" name="search" class="form-control form-control-sm"
                                    value="<?= htmlspecialchars($search) ?>" placeholder="Cari user...">

                                <select name="level" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Semua Level</option>
                                    <option value="admin" <?= ($filter_level == 'admin') ? 'selected' : '' ?>>Admin</option>
                                    <option value="user" <?= ($filter_level == 'user') ? 'selected' : '' ?>>User</option>
                                </select>

                                <button class="btn btn-secondary btn-sm">
                                    <i class="bi bi-search"></i>
                                </button>

                            </form>

                            <a href="add_user.php" class="btn btn-light btn-sm">
                                <i class="bi bi-plus-circle"></i> Tambah
                            </a>

                        </div>

                    </div>

                    <div class="card-body table-responsive">

                        <table class="table table-bordered table-hover text-center">

                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Username</th>
                                    <th>Level</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if (mysqli_num_rows($result) == 0): ?>
                                    <tr>
                                        <td colspan="7">Tidak ada data</td>
                                    </tr>
                                <?php else: $i = 1; ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>

                                        <tr>
                                            <td><?= (($page - 1) * $limit) + $i++ ?></td>
                                            <td><?= htmlspecialchars($row['nama_pengguna']) ?></td>
                                            <td><?= htmlspecialchars($row['username']) ?></td>
                                            <td><?= htmlspecialchars($row['level']) ?></td>
                                            <td><?= htmlspecialchars($row['role']) ?></td>
                                            <td><?= htmlspecialchars($row['status'] ?? 'aktif') ?></td>

                                            <td class="btn-action">
                                                <a href="edit_user.php?id=<?= $row['id_pengguna'] ?>" class="btn btn-warning btn-sm">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>

                                                <a href="hapus_user.php?id=<?= $row['id_pengguna'] ?>" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>

                                        </tr>

                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </tbody>
                        </table>

                        <!-- PAGINATION FIX -->
                        <div class="d-flex justify-content-end mt-3">

                            <nav>
                                <ul class="pagination pagination-sm">

                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                            href="?page_user=<?= $page - 1 ?>&search=<?= $search ?>&level=<?= $filter_level ?>">
                                            Prev
                                        </a>
                                    </li>

                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <?= $page ?> / <?= $total_page ?>
                                        </span>
                                    </li>

                                    <li class="page-item <?= ($page >= $total_page) ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                            href="?page_user=<?= $page + 1 ?>&search=<?= $search ?>&level=<?= $filter_level ?>">
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

</body>

</html>