<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}
include(__DIR__ . '/../config/koneksi.php');

/* ================= PAGINATION & FILTER ================= */
$batas = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $batas;

$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : "";
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : 'medis';

$whereConditions = [];
if ($search != '') {
    $whereConditions[] = "(nama_peminjam LIKE '%$search%' OR aset.nama_aset LIKE '%$search%')";
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

/* ================= TOTAL DATA ================= */
$totalData = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total 
     FROM peminjaman 
     JOIN aset ON peminjaman.id_aset = aset.id_aset
     $whereClause"
))['total'];

$totalPage = ceil($totalData / $batas);

/* ================= QUERY DATA ================= */
$query = mysqli_query($koneksi, "
    SELECT peminjaman.*, aset.nama_aset, aset.kategori_aset, aset.lokasi 
    FROM peminjaman 
    JOIN aset ON peminjaman.id_aset = aset.id_aset 
    $whereClause
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
            background-color: #f4f6f9;
            color: #333;
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .content {
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            background: #fff;
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 15px 20px;
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* STYLE NAV TABS SAMA PERSIS DARI ASET.PHP */
        .nav-tabs {
            border-bottom: 2px solid #e2e8f0;
            margin-top: 15px;
            padding: 0 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            font-weight: 500;
            color: #64748b;
            padding: 12px 20px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link:hover {
            color: #2c7a7b;
            border-bottom: 3px solid #cbd5e1;
        }

        .nav-tabs .nav-link.active-medis {
            color: #dc2626 !important;
            font-weight: bold;
            border-bottom: 3px solid #dc2626;
            background: #fffafb;
        }

        .nav-tabs .nav-link.active-nonmedis {
            color: #0284c7 !important;
            font-weight: bold;
            border-bottom: 3px solid #0284c7;
            background: #f0f9ff;
        }

        .nav-tabs .nav-link.active-semua {
            color: #16a34a !important;
            font-weight: bold;
            border-bottom: 3px solid #16a34a;
            background: #f0fdf4;
        }

        /* STYLE TABEL SAMA PERSIS DARI ASET.PHP (MENCEGAH TEKS KETUMPUK) */
        .table-responsive {
            border-radius: 0 0 12px 12px;
            overflow-x: auto;
        }

        table {
            margin-bottom: 0 !important;
        }

        table th {
            background-color: #f8fafc !important;
            color: #4a5568 !important;
            font-weight: bold !important;
            text-transform: uppercase;
            font-size: 0.8rem;
            padding: 14px 16px !important;
            border-bottom: 2px solid #e2e8f0 !important;
            white-space: nowrap;
        }

        table td {
            vertical-align: middle !important;
            white-space: nowrap;
            /* Teks memanjang horizontal, tabel otomatis melebar rapi */
            padding: 12px 16px !important;
            font-size: 0.9rem;
            color: #4a5568;
        }

        table tbody tr:hover {
            background-color: #fafffd !important;
        }

        /* BADGE KATEGORI SAMA PERSIS DARI ASET.PHP */
        .badge-medis {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }

        .badge-nonmedis {
            background-color: #e0f2fe;
            color: #0284c7;
            border: 1px solid #7dd3fc;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>
        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h4 class="fw-bold m-0"><i class="bi bi-box-seam"></i> PEMINJAMAN ALAT</h4>
                <div class="small fw-medium">
                    <i class="bi bi-person-circle-fill"></i> <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>
                    <span class="badge bg-light text-dark ms-1" style="text-transform: uppercase;"><?= htmlspecialchars($_SESSION['level']); ?></span>
                </div>
            </div>
            <div class="content">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-table"></i> <span>Data Peminjaman</span>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="GET" class="d-flex gap-1 align-items-center">
                                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_filter) ?>">
                                <input type="text" name="search" class="form-control form-control-sm bg-light text-dark border-0" placeholder="Cari peminjam / alat..." style="border-radius: 6px; padding: 6px 12px;" value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-light btn-sm text-dark" style="border-radius: 6px;"><i class="bi bi-search"></i></button>
                            </form>
                            <a href="peminjaman_tambah.php" class="btn btn-light btn-sm" style="border-radius: 6px;"><i class="bi bi-plus-lg"></i> Tambah</a>
                            <a href="peminjaman_cetak.php?kategori=<?= $kategori_filter ?>&search=<?= urlencode($search) ?>" class="btn btn-light btn-sm" style="border-radius: 6px;"><i class="bi bi-file-earmark-pdf"></i> Cetak PDF</a>
                        </div>
                    </div>

                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?= ($kategori_filter == 'medis') ? 'active-medis' : '' ?>" href="?kategori=medis<?= $search ? '&search=' . urlencode($search) : '' ?>">
                                🏥 Aset Medis (Alkes)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($kategori_filter == 'non-medis') ? 'active-nonmedis' : '' ?>" href="?kategori=non-medis<?= $search ? '&search=' . urlencode($search) : '' ?>">
                                🪑 Aset Non-Medis
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($kategori_filter == 'semua') ? 'active-semua' : '' ?>" href="?kategori=semua<?= $search ? '&search=' . urlencode($search) : '' ?>">
                                📋 Semua Aset
                            </a>
                        </li>
                    </ul>

                    <div class="card-body p-0 pt-2">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-center align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Peminjam</th>
                                        <th>Nama Alat</th>
                                        <th>Kategori</th>
                                        <th>Lokasi Asal</th>
                                        <th>Tgl Pinjam</th>
                                        <th>Tgl Kembali</th>
                                        <th>Status</th>
                                        <th style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($query) == 0): ?>
                                        <tr>
                                            <td colspan="9" class="text-muted py-5 text-center"><i class="bi bi-inboxes text-secondary d-block mb-2" style="font-size: 2rem;"></i>Data untuk kategori ini belum tersedia atau tidak ditemukan.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = $offset + 1; ?>
                                        <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                            <tr>
                                                <td class="text-secondary"><?= $no++ ?></td>
                                                <td class="fw-bold text-dark text-start"><?= htmlspecialchars($row['nama_peminjam']) ?></td>
                                                <td class="text-start"><?= htmlspecialchars($row['nama_aset']) ?></td>

                                                <td>
                                                    <?php if (isset($row['kategori_aset']) && $row['kategori_aset'] == 'Medis'): ?>
                                                        <span class="badge-medis">Medis</span>
                                                    <?php elseif (isset($row['kategori_aset']) && $row['kategori_aset'] == 'Non-Medis'): ?>
                                                        <span class="badge-nonmedis">Non-Medis</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-secondary border">-</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td class="text-start"><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($row['lokasi']) ?></td>
                                                <td><?= date('d-m-Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                                <td><?= (!empty($row['tanggal_kembali']) && $row['tanggal_kembali'] != '0000-00-00') ? date('d-m-Y', strtotime($row['tanggal_kembali'])) : '-' ?></td>

                                                <td>
                                                    <span class="badge <?= $row['status_pinjam'] == 'Dipinjam' ? 'bg-warning text-dark' : 'bg-success' ?>">
                                                        <?= $row['status_pinjam'] ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <div class="d-flex gap-1 justify-content-center">
                                                        <?php if ($row['status_pinjam'] == 'Dipinjam'): ?>
                                                            <a href="peminjaman_kembali.php?id=<?= $row['id_pinjam'] ?>" class="btn btn-success btn-sm" style="border-radius: 6px;" title="Kembalikan Alat">
                                                                <i class="bi bi-arrow-return-left"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="peminjaman_edit.php?id=<?= $row['id_pinjam'] ?>" class="btn btn-warning btn-sm text-white" style="border-radius: 6px;" title="Edit">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <a href="peminjaman_hapus.php?id=<?= $row['id_pinjam'] ?>" class="btn btn-danger btn-sm" style="border-radius: 6px;" title="Hapus" onclick="return confirm('Hapus peminjaman ini?')">
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

                        <div class="card-footer bg-white d-flex justify-content-end py-3 border-top-0">
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" style="border-radius: 6px 0 0 6px;" href="?page=<?= $page - 1 ?>&kategori=<?= $kategori_filter ?>&search=<?= urlencode($search) ?>">Prev</a>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link bg-light text-dark fw-bold"><?= $page ?> / <?= max(1, $totalPage) ?></span>
                                    </li>
                                    <li class="page-item <?= ($page >= $totalPage) ? 'disabled' : '' ?>">
                                        <a class="page-link" style="border-radius: 0 6px 6px 0;" href="?page=<?= $page + 1 ?>&kategori=<?= $kategori_filter ?>&search=<?= urlencode($search) ?>">Next</a>
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