<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

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
                OR role LIKE '%$search%'
                OR nip LIKE '%$search%'
                OR jabatan LIKE '%$search%'
                OR ruangan LIKE '%$search%')";
}

if ($filter_level != '') {
    $where[] = "level='$filter_level'";
}

$where_clause = "";
if (count($where) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $where);
}

// ================= TOTAL DATA ================= //
$count_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pengguna $where_clause");
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_page = ceil($total_data / $limit);

// ================= QUERY DATA ================= //
$query = mysqli_query($koneksi, "SELECT * FROM pengguna $where_clause ORDER BY id_pengguna DESC LIMIT $offset, $limit");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manajemen Data Pegawai | SIMARIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Poppins', sans-serif;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        #page-content-wrapper {
            flex: 1;
            max-width: 100%;
            overflow-x: hidden;
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

        .card {
            border: none;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        table th {
            background: #f8fafc !important;
            white-space: nowrap;
        }

        table td {
            vertical-align: middle !important;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h4 class="fw-bold m-0"><i class="bi bi-people-fill"></i> MANAJEMEN DATA PEGAWAI</h4>
                <div>
                    <i class="bi bi-person-circle-fill"></i> Admin
                </div>
            </div>

            <div class="content">
                <div class="card">
                    <div class="card-header">
                        <div><i class="bi bi-table"></i> Master Data Pegawai & Akun Rumah Sakit</div>
                        <div class="d-flex gap-2">
                            <form method="GET" class="d-flex gap-1">
                                <select name="level" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">Semua Level</option>
                                    <option value="admin" <?= $filter_level == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="pegawai" <?= $filter_level == 'pegawai' ? 'selected' : '' ?>>Pegawai</option>
                                    <option value="teknisi" <?= $filter_level == 'teknisi' ? 'selected' : '' ?>>Teknisi</option>
                                </select>

                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari NIP/Nama/Ruangan..." value="<?= htmlspecialchars($search) ?>" style="min-width: 200px;">
                                <button type="submit" class="btn btn-light btn-sm"><i class="bi bi-search"></i></button>
                            </form>
                            <a href="add_user.php" class="btn btn-light btn-sm text-nowrap"><i class="bi bi-plus-lg"></i> Tambah Pegawai</a>
                        </div>
                    </div>

                    <div class="card-body p-0 table-responsive">
                        <table class="table table-bordered table-hover text-center m-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIP / NIK</th>
                                    <th>Nama Pegawai</th>
                                    <th>Username</th>
                                    <th>Jabatan</th>
                                    <th>Ruangan / Unit Kerja</th>
                                    <th>Level</th>
                                    <th>Role</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($query) == 0): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">Data pegawai tidak ditemukan.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = $offset + 1; ?>
                                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td class="fw-bold text-secondary"><?= htmlspecialchars($row['nip'] ?? '-'); ?></td>
                                            <td class="text-start fw-bold text-dark"><?= htmlspecialchars($row['nama_pengguna']); ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['username']); ?></span></td>
                                            <td><?= htmlspecialchars($row['jabatan'] ?? '-'); ?></td>
                                            <td class="text-start"><i class="bi bi-geo-alt text-danger"></i> <?= htmlspecialchars($row['ruangan'] ?? '-'); ?></td>
                                            <td><?= htmlspecialchars($row['level']); ?></td>
                                            <td>
                                                <span class="badge <?= $row['role'] == 'admin' ? 'bg-primary' : 'bg-info text-dark' ?>">
                                                    <?= htmlspecialchars($row['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <a href="edit_user.php?id=<?= $row['id_pengguna'] ?>" class="btn btn-warning btn-sm text-white"><i class="bi bi-pencil-square"></i></a>
                                                    <a href="hapus_user.php?id=<?= $row['id_pengguna'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data pegawai ini?')"><i class="bi bi-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <div class="d-flex justify-content-end p-3">
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page_user=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&level=<?= urlencode($filter_level) ?>">Prev</a>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link text-dark fw-bold"><?= $page ?> / <?= max(1, $total_page) ?></span>
                                    </li>
                                    <li class="page-item <?= ($page >= $total_page) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page_user=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&level=<?= urlencode($filter_level) ?>">Next</a>
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