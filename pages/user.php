<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// Hanya admin
if (($_SESSION['role'] ?? '') != 'admin') {
    die("Akses ditolak.");
}

// Search dan filter level
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$filter_level = isset($_GET['level']) ? mysqli_real_escape_string($koneksi, $_GET['level']) : '';

$sql = "SELECT * FROM pengguna";
$where = [];
if ($search != '') {
    $where[] = "(nama_pengguna LIKE '%$search%' OR username LIKE '%$search%' OR level LIKE '%$search%' OR role LIKE '%$search%')";
}
if ($filter_level != '') {
    $where[] = "level='$filter_level'";
}
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY id_pengguna DESC";

$result = mysqli_query($koneksi, $sql);
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
            flex-wrap: wrap;
        }

        .table-bordered {
            border: 1px solid #d1f0eb;
        }

        .table-hover tbody tr:hover {
            background-color: #d1f0eb;
        }

        table th,
        table td {
            vertical-align: middle !important;
        }

        .btn-action {
            display: flex;
            gap: 5px;
            justify-content: center;
            flex-wrap: wrap;
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

        form.d-flex input.form-control-sm,
        form.d-flex select.form-select-sm {
            height: 38px;
            font-size: 14px;
        }

        form.d-flex {
            align-items: center;
        }

        @media (max-width: 575px) {
            form.d-flex {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include_once(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h3>MANAJEMEN USER</h3>
                <div class="admin-info">
                    <i class="bi bi-person-circle"></i>
                    <span><?= htmlspecialchars($_SESSION['nama_pengguna']); ?> (<?= htmlspecialchars($_SESSION['level']); ?>)</span>
                </div>
            </div>

            <div class="content">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <span>Daftar User</span>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <form method="GET" class="d-flex gap-2 mb-0">
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari user..." value="<?= htmlspecialchars($search) ?>">
                                <select name="level" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Semua Level</option>
                                    <option value="admin" <?= ($filter_level == 'admin') ? 'selected' : '' ?>>Admin</option>
                                    <option value="user" <?= ($filter_level == 'user') ? 'selected' : '' ?>>User</option>
                                </select>
                                <button type="submit" class="btn btn-secondary btn-sm"><i class="bi bi-search"></i></button>
                            </form>

                            <a href="add_user.php" class="btn btn-custom">
                                <i class="bi bi-plus-circle"></i> Tambah User
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <table class="table table-bordered table-hover text-center align-middle">
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
                                        <td colspan="7">Belum ada user.</td>
                                    </tr>
                                    <?php else: $i = 1;
                                    while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($row['nama_pengguna']); ?></td>
                                            <td><?= htmlspecialchars($row['username']); ?></td>
                                            <td><?= htmlspecialchars($row['level']); ?></td>
                                            <td><?= ucfirst(htmlspecialchars($row['role'])); ?></td>
                                            <td><?= htmlspecialchars($row['status'] ?? 'aktif'); ?></td>
                                            <td class="btn-action">
                                                <a href="edit_user.php?id=<?= $row['id_pengguna']; ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i></a>
                                                <a href="hapus_user.php?id=<?= $row['id_pengguna']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus user ini?')"><i class="bi bi-trash"></i></a>
                                                <a href="toggle_status.php?id=<?= $row['id_pengguna']; ?>" class="btn btn-secondary btn-sm">
                                                    <?= ($row['status'] ?? 'aktif') == 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                </a>
                                            </td>
                                        </tr>
                                <?php endwhile;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>