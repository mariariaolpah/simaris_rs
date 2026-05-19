<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// ================= PAGINATION & FILTER ================= //
$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// SEARCH
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : "";

// FILTER KATEGORI
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : 'medis';

$whereConditions = [];

if ($search != '') {
    $whereConditions[] = "(kerusakan.nama_aset LIKE '%$search%' 
        OR kerusakan.status LIKE '%$search%' 
        OR kerusakan.keterangan LIKE '%$search%'
        OR aset.lokasi LIKE '%$search%')";
}

if ($kategori_filter == 'medis') {
    $whereConditions[] = "aset.kategori_aset = 'Medis'";
} elseif ($kategori_filter == 'non-medis') {
    $whereConditions[] = "aset.kategori_aset = 'Non-Medis'";
}

$whereClause = "";
if (count($whereConditions) > 0) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

// ================= TOTAL DATA ================= //
$count_query = mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM kerusakan 
    LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset
    $whereClause
");

$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_page = ceil($total_data / $limit);

// ================= QUERY DATA ================= //
$query = mysqli_query($koneksi, "
    SELECT 
        kerusakan.*,
        kerusakan.tanggal AS tanggal_lapor,
        aset.kategori_aset,
        aset.lokasi 
    FROM kerusakan 
    LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset
    $whereClause
    ORDER BY kerusakan.id DESC
    LIMIT $offset, $limit
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Kerusakan Aset | SIMARIS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        body {
            background-color: #f4f6f9;
            color: #333;
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

        .nav-tabs {
            border-bottom: 2px solid #e2e8f0;
            margin-top: 15px;
            padding: 0 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            padding: 12px 20px;
            color: #64748b;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active-medis {
            color: #dc2626 !important;
            border-bottom: 3px solid #dc2626;
            background: #fffafb;
        }

        .nav-tabs .nav-link.active-nonmedis {
            color: #0284c7 !important;
            border-bottom: 3px solid #0284c7;
            background: #f0f9ff;
        }

        .nav-tabs .nav-link.active-semua {
            color: #16a34a !important;
            border-bottom: 3px solid #16a34a;
            background: #f0fdf4;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table th {
            background: #f8fafc !important;
            white-space: nowrap;
            padding: 14px !important;
        }

        table td {
            white-space: nowrap;
            padding: 12px !important;
            vertical-align: middle !important;
        }

        .badge-medis {
            background: #fee2e2;
            color: #dc2626;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .badge-nonmedis {
            background: #e0f2fe;
            color: #0284c7;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .kolom-keterangan {
            white-space: normal !important;
            min-width: 250px;
            text-align: left;
        }
    </style>
</head>

<body>

    <div id="wrapper">

        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">

            <div class="dashboard-header">
                <h4 class="fw-bold m-0">
                    <i class="bi bi-exclamation-octagon"></i> DATA KERUSAKAN ASET
                </h4>

                <div>
                    <i class="bi bi-person-circle-fill"></i>
                    <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>
                </div>
            </div>

            <div class="content">

                <div class="card">

                    <div class="card-header">

                        <div>
                            <i class="bi bi-table"></i>
                            Daftar Pelaporan Kerusakan
                        </div>

                        <div class="d-flex gap-2">

                            <form method="GET" class="d-flex gap-1">

                                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_filter) ?>">

                                <input type="text"
                                    name="search"
                                    class="form-control form-control-sm"
                                    placeholder="Cari aset..."
                                    value="<?= htmlspecialchars($search) ?>">

                                <button class="btn btn-light btn-sm">
                                    <i class="bi bi-search"></i>
                                </button>

                            </form>

                            <a href="kerusakan_tambah.php" class="btn btn-light btn-sm">
                                <i class="bi bi-plus-lg"></i> Tambah
                            </a>

                        </div>

                    </div>

                    <ul class="nav nav-tabs">

                        <li class="nav-item">
                            <a class="nav-link <?= ($kategori_filter == 'medis') ? 'active-medis' : '' ?>"
                                href="?kategori=medis">
                                🏥 Aset Medis
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?= ($kategori_filter == 'non-medis') ? 'active-nonmedis' : '' ?>"
                                href="?kategori=non-medis">
                                🪑 Aset Non-Medis
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?= ($kategori_filter == 'semua') ? 'active-semua' : '' ?>"
                                href="?kategori=semua">
                                📋 Semua Aset
                            </a>
                        </li>

                    </ul>

                    <div class="card-body p-0 pt-2">

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover text-center align-middle">

                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Aset</th>
                                        <th>Lokasi Ruangan</th>
                                        <th>Kategori</th>
                                        <th>Tanggal Lapor</th>
                                        <th>Rincian Kerusakan</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php if (mysqli_num_rows($query) == 0): ?>

                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                Tidak ada data kerusakan.
                                            </td>
                                        </tr>

                                    <?php else: ?>

                                        <?php $no = $offset + 1; ?>

                                        <?php while ($row = mysqli_fetch_assoc($query)): ?>

                                            <tr>

                                                <td><?= $no++ ?></td>

                                                <td class="text-start fw-bold">
                                                    <?= htmlspecialchars($row['nama_aset']) ?>
                                                </td>

                                                <td class="text-start">
                                                    <i class="bi bi-geo-alt text-danger me-1"></i>
                                                    <?= htmlspecialchars($row['lokasi'] ?? '-') ?>
                                                </td>

                                                <td>

                                                    <?php if (($row['kategori_aset'] ?? '') == 'Medis'): ?>

                                                        <span class="badge-medis">Medis</span>

                                                    <?php else: ?>

                                                        <span class="badge-nonmedis">Non-Medis</span>

                                                    <?php endif; ?>

                                                </td>

                                                <td>
                                                    <?= !empty($row['tanggal_lapor'])
                                                        ? date('d-m-Y', strtotime($row['tanggal_lapor']))
                                                        : '-' ?>
                                                </td>

                                                <td class="kolom-keterangan">
                                                    <?= htmlspecialchars($row['keterangan']) ?>
                                                </td>

                                                <td>

                                                    <?php
                                                    $status = $row['status'] ?? 'Baru';

                                                    $bg = 'bg-danger';

                                                    if ($status == 'Diproses') {
                                                        $bg = 'bg-warning text-dark';
                                                    }

                                                    if ($status == 'Selesai') {
                                                        $bg = 'bg-success';
                                                    }
                                                    ?>

                                                    <span class="badge <?= $bg ?>">
                                                        <?= htmlspecialchars($status) ?>
                                                    </span>

                                                </td>

                                                <td>

                                                    <div class="d-flex gap-1 justify-content-center">

                                                        <a href="kerusakan_edit.php?id=<?= $row['id'] ?>"
                                                            class="btn btn-warning btn-sm text-white">

                                                            <i class="bi bi-pencil-square"></i>

                                                        </a>

                                                        <a href="kerusakan_hapus.php?id=<?= $row['id'] ?>"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Hapus data?')">

                                                            <i class="bi bi-trash"></i>

                                                        </a>

                                                    </div>

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

    </div>

</body>

</html>