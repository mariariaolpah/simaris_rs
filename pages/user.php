<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// ================= FIX SESSION CHECK ================= //
if (($_SESSION['role'] ?? '') != 'admin') {
    die("Akses ditolak. Halaman ini hanya untuk Administrator.");
}

// ================= PAGINATION ================= //
$limit = 8;
$page = isset($_GET['page_user']) ? (int)$_GET['page_user'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

// ================= SEARCH & FILTER ================= //
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$filter_level = isset($_GET['level']) ? mysqli_real_escape_string($koneksi, $_GET['level']) : '';

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

$where_clause = "";
if (count($where) > 0) {
    $where_clause = " WHERE " . implode(" AND ", $where);
}

// QUERY DATA SINKRON DENGAN PAGINATION
$query = mysqli_query($koneksi, "SELECT * FROM pengguna $where_clause ORDER BY id_pengguna DESC LIMIT $limit OFFSET $offset");
$count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pengguna $where_clause");

$total_row = mysqli_fetch_assoc($count);
$total_data = $total_row['total'];
$total_page = ceil($total_data / $limit);

$user_list = [];
while ($row = mysqli_fetch_assoc($query)) {
    $user_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manajemen User | SIMARIS RS Bhayangkara</title>
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

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            font-weight: 600;
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
            padding: 15px 20px;
        }

        table th {
            background-color: #f1f5f9 !important;
            color: #334155;
            font-weight: 600;
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

        .form-control,
        .form-select {
            border-radius: 8px;
        }
    </style>
</head>

<body>

    <div id="wrapper">

        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">

            <div class="dashboard-header">
                <h3>MANAJEMEN PENGGUNA / USER</h3>
                <div>
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars($_SESSION['nama_pengguna']); ?> (Admin)
                </div>
            </div>

            <div class="content">

                <div class="card">

                    <div class="card-header">
                        <span><i class="bi bi-people-fill"></i> Data Akun Pengguna Sistem</span>
                        <a href="add_user.php" class="btn btn-light btn-sm fw-bold text-success">
                            <i class="bi bi-person-plus-fill"></i> + Tambah User
                        </a>
                    </div>

                    <div class="card-body">

                        <form method="GET" class="row g-2 mb-4 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-secondary">Cari Pengguna</label>
                                <input type="text" name="search" class="form-control form-control-sm"
                                    value="<?= htmlspecialchars($search) ?>"
                                    placeholder="Cari nama atau username...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-secondary">Filter Hak Akses (Level)</label>
                                <select name="level" class="form-select form-select-sm">
                                    <option value="">-- Semua Level --</option>
                                    <option value="admin" <?= $filter_level == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="user" <?= $filter_level == 'user' ? 'selected' : '' ?>>User / Pegawai</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-1">
                                <button type="submit" class="btn btn-secondary btn-sm flex-fill">
                                    <i class="bi bi-search"></i> Cari
                                </button>
                                <?php if ($search != '' || $filter_level != ''): ?>
                                    <a href="user.php" class="btn btn-outline-danger btn-sm">Reset</a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-center align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 8%;">#</th>
                                        <th>Nama Pengguna</th>
                                        <th>Username</th>
                                        <th>Level Akses</th>
                                        <th>Role Operasional</th>
                                        <th style="width: 15%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($user_list)): ?>
                                        <tr>
                                            <td colspan="6" class="text-muted py-4">Tidak ada data pengguna ditemukan.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($user_list as $index => $u): ?>
                                            <tr>
                                                <td><?= (($page - 1) * $limit) + $index + 1 ?></td>
                                                <td class="text-start fw-bold text-dark"><?= htmlspecialchars($u['nama_pengguna']) ?></td>
                                                <td><code class="text-secondary"><?= htmlspecialchars($u['username']) ?></code></td>
                                                <td>
                                                    <?php if ($u['level'] == 'admin'): ?>
                                                        <span class="badge bg-success px-3 py-2"><i class="bi bi-shield-lock"></i> Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary px-3 py-2"><i class="bi bi-person"></i> User</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="text-capitalize text-muted"><?= htmlspecialchars($u['role'] ?? 'Pegawai'); ?></span></td>
                                                <td class="btn-action">
                                                    <a href="edit_user.php?id=<?= $u['id_pengguna'] ?>" class="btn btn-warning btn-sm" title="Edit Akun">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="hapus_user.php?id=<?= $u['id_pengguna'] ?>" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Apakah Anda yakin ingin menghapus akun <?= htmlspecialchars($u['nama_pengguna']) ?>?')" title="Hapus Akun">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page_user=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&level=<?= urlencode($filter_level) ?>">
                                            Prev
                                        </a>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link text-dark fw-bold">
                                            <?= $page ?> / <?= $total_page ?>
                                        </span>
                                    </li>
                                    <li class="page-item <?= ($page >= $total_page) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page_user=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&level=<?= urlencode($filter_level) ?>">
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